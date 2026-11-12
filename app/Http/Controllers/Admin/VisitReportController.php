<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StoreVisit;
use App\Models\User;
use App\Services\OdooService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Exports\VisitReportExport;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class VisitReportController extends Controller
{
    public function index(Request $request, OdooService $odooService)
    {
        //Filters

        $quickFilter = $request->input('quick_filter', 'today');

        $date = $request->input('date');

        if ($quickFilter === 'today' && !$request->has('date')) {
            $date = today()->toDateString();
        }

        $filters = [
            'date' => $date,
            'sales_id' => $request->input('sales_id'),
            'status' => $request->input('status'),
            'search' => trim($request->input('search', '')),
            'quick_filter' => $quickFilter,
        ];

        //Base Visit Query

        $visitsQuery = StoreVisit::query()
            ->with([
                'sales:id,name,username,profile_photo_url',
                'report.corrections.user:id,name',
                'fieldStore',
            ])
            ->when(
                $filters['date'],
                fn ($query, $date) =>
                    $query->whereDate('visit_date', $date)
            )
            ->when(
                $filters['sales_id'],
                fn ($query, $salesId) =>
                    $query->where('sales_id', $salesId)
            )
            ->when(
                $filters['status'],
                fn ($query, $status) =>
                    $query->where('status', $status)
            );

        //Quick Filters

        switch ($quickFilter) {
            case 'flagged':
                $visitsQuery->whereHas('report', function ($query) {
                    $query->where('is_outside_radius', true);
                });
                break;

            case 'active':
                $visitsQuery->where('status', 'IN_VISIT');
                break;

            case 'all':
            case 'today':
            default:
                break;
        }

        //Get Visits

        $allVisits = $visitsQuery
            ->latest('check_in_at')
            ->get();

        $partnerMap = $this->loadPartnerMap(
            $allVisits,
            $odooService
        );

        //Odoo Partner Data

        $partnerIds = $allVisits
            ->pluck('odoo_partner_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $odooPartners = [];

        if (!empty($partnerIds)) {
            $odooPartners = $odooService
                ->getCompleteStoreData($partnerIds);
        }

        $partnerMap = collect($odooPartners)
            ->keyBy('id');

        //Search

        if ($filters['search'] !== '') {
            $search = mb_strtolower(
                $filters['search']
            );

            $allVisits = $allVisits
                ->filter(function ($visit) use (
                    $search,
                    $partnerMap
                ) {
                    $salesName = mb_strtolower(
                        $visit->sales?->name ?? ''
                    );

                    $partner = $partnerMap->get(
                        $visit->odoo_partner_id
                    );

                    $storeName = mb_strtolower(
                        $visit->store_type === 'field'
                            ? ($visit->fieldStore?->store_name ?? '')
                            : ($partner['name']
                                ?? $partner['display_name']
                                ?? '')
                    );

                    return str_contains(
                        $salesName,
                        $search
                    ) || str_contains(
                        $storeName,
                        $search
                    );
                })
                ->values();
        }

        //Statistics

        $totalVisits = $allVisits->count();

        $completedVisits = $allVisits
            ->where('status', 'COMPLETED')
            ->count();

        $activeVisits = $allVisits
            ->where('status', 'IN_VISIT')
            ->count();

        $cancelledVisits = $allVisits
            ->where('status', 'CANCELLED')
            ->count();

        $correctedVisits = $allVisits
            ->filter(fn ($visit) => $visit->report?->corrections
                ->contains('status', 'approved'))
            ->count();

        $averageDurationSeconds = $allVisits
            ->filter(function ($visit) {
                return $visit->check_in_at
                    && $visit->check_out_at;
            })
            ->map(function ($visit) {
                return $visit->check_in_at
                    ->diffInSeconds(
                        $visit->check_out_at
                    );
            })
            ->average();

        $averageDuration = '0m 00s';

        if ($averageDurationSeconds !== null) {
            $totalSeconds = (int) round(
                $averageDurationSeconds
            );

            $minutes = intdiv(
                $totalSeconds,
                60
            );

            $seconds = $totalSeconds % 60;

            $averageDuration = sprintf(
                '%dm %02ds',
                $minutes,
                $seconds
            );
        }

        //Manual Pagination

        $perPage = 10;

        $currentPage = max(
            1,
            (int) $request->input('page', 1)
        );

        $totalFilteredVisits = $allVisits->count();

        $lastPage = max(
            1,
            (int) ceil(
                $totalFilteredVisits / $perPage
            )
        );

        //Prevent requesting a page that no longer exists after filtering.
        if ($currentPage > $lastPage) {
            $currentPage = $lastPage;
        }

        $paginatedVisits = $allVisits
            ->slice(
                ($currentPage - 1) * $perPage,
                $perPage
            )
            ->values();

        //Transform Visit Data

        $visitData = $paginatedVisits
            ->map(function ($visit) use ($partnerMap) {
                $durationSeconds = null;
                $originalReportData = $visit->report?->originalData();
                $effectiveReportData = $visit->report?->effectiveData();

                if (
                    $visit->check_in_at
                    && $visit->check_out_at
                ) {
                    $durationSeconds = $visit->check_in_at
                        ->diffInSeconds(
                            $visit->check_out_at
                        );
                }

                $isFieldStore = ($visit->store_type ?? 'regular') === 'field';
                $partner = $isFieldStore
                    ? [
                        'id' => $visit->fieldStore?->id,
                        'name' => $visit->fieldStore?->store_name ?? 'Unknown Field Store',
                        'display_name' => $visit->fieldStore?->store_name ?? 'Unknown Field Store',
                        'street' => $visit->fieldStore?->address,
                        'phone' => $visit->fieldStore?->phone_number,
                        'owner_name' => $visit->fieldStore?->owner_name,
                        'photo_url' => $visit->fieldStore?->photo_url,
                        'latitude' => $visit->fieldStore?->latitude,
                        'longitude' => $visit->fieldStore?->longitude,
                    ]
                    : $partnerMap->get($visit->odoo_partner_id);

                return [
                    'id' => $visit->id,

                    'store_type' => $visit->store_type ?? 'regular',

                    'store_id' => $isFieldStore
                        ? $visit->field_store_id
                        : $visit->odoo_partner_id,

                    'odoo_partner_id' =>
                        $visit->odoo_partner_id,

                    'visit_date' => $visit->visit_date
                        ? $visit->visit_date->format('Y-m-d')
                        : null,

                    'activities' =>
                        $effectiveReportData['activities'] ?? [],

                    'status' =>
                        $visit->status,

                    'check_in_at' =>
                        $visit->check_in_at
                            ? $visit->check_in_at->format('H:i:s')
                            : null,

                    'check_out_at' =>
                        $visit->check_out_at
                            ? $visit->check_out_at->format('H:i:s')
                            : null,

                    'duration_seconds' =>
                        $durationSeconds,

                    'sales' => [
                        'id' =>
                            $visit->sales?->id,

                        'name' =>
                            $visit->sales?->name
                            ?? 'Unknown Sales',

                        'username' =>
                            $visit->sales?->username,

                        'profile_photo_url' =>
                            $visit->sales?->profile_photo_url,
                    ],

                    'partner' => [
                        'id' =>
                            is_array($partner)
                                ? ($partner['id'] ?? $visit->odoo_partner_id)
                                : $visit->odoo_partner_id,

                        'name' =>
                            is_array($partner)
                                ? ($partner['name'] ?? $partner['display_name'] ?? 'Unknown Store')
                                : 'Unknown Store',

                        'display_name' =>
                            is_array($partner) ? ($partner['display_name'] ?? null) : null,

                        'street' =>
                            is_array($partner) ? ($partner['street'] ?? null) : null,

                        'street2' =>
                            is_array($partner) ? ($partner['street2'] ?? null) : null,

                        'city' =>
                            is_array($partner) ? ($partner['city'] ?? null) : null,

                        'phone' =>
                            is_array($partner) ? ($partner['phone'] ?? null) : null,

                        'mobile' =>
                            is_array($partner) ? ($partner['mobile'] ?? null) : null,

                        'email' =>
                            is_array($partner) ? ($partner['email'] ?? null) : null,

                        'vat' =>
                            is_array($partner) ? ($partner['vat'] ?? null) : null,

                        'state' =>
                            is_array($partner) && isset($partner['state_id'][1])
                                ? $partner['state_id'][1]
                                : null,

                        'country' =>
                            is_array($partner) && isset($partner['country_id'][1])
                                ? $partner['country_id'][1]
                                : null,

                        'owner_name' =>
                            is_array($partner) ? ($partner['owner_name'] ?? null) : null,

                        'photo_url' =>
                            is_array($partner) ? ($partner['photo_url'] ?? null) : null,

                        'latitude' =>
                            is_array($partner) ? ($partner['latitude'] ?? null) : null,

                        'longitude' =>
                            is_array($partner) ? ($partner['longitude'] ?? null) : null,
                    ],

                    'report' => $visit->report
                        ? [
                            'id' =>
                                $visit->report->id,

                            'pic_name' =>
                                $effectiveReportData['pic_name'],

                            'activities' =>
                                $effectiveReportData['activities'] ?? [],

                            'stock_percentage' =>
                                $effectiveReportData['stock_percentage'],

                            'stock_pcs' =>
                                $effectiveReportData['stock_pcs'],

                            'notes' =>
                                $effectiveReportData['notes'],

                            'original' =>
                                $originalReportData,

                            'correction_count' =>
                                $visit->report->corrections
                                    ->where('status', 'approved')
                                    ->count(),

                            'corrections' =>
                                $visit->report->corrections
                                    ->map(fn ($correction) => [
                                        'id' => $correction->id,
                                        'reason' => $correction->reason,
                                        'status' => $correction->status,
                                        'approved_at' => $correction->approved_at
                                            ?->toDateTimeString(),
                                        'previous_data' => $correction->previous_data,
                                        'correction_data' => $correction->correction_data,
                                        'user' => [
                                            'id' => $correction->user?->id,
                                            'name' => $correction->user?->name
                                                ?? 'Unknown Sales',
                                        ],
                                    ])
                                    ->values(),

                            'photos' =>
                                $visit->report->photos
                                ?? ($visit->report->check_in_photo
                                    ? [$visit->report->check_in_photo]
                                    : []),

                            'sales_latitude' =>
                                $visit->report->sales_latitude
                                ?? $visit->report->check_in_lat,

                            'sales_longitude' =>
                                $visit->report->sales_longitude
                                ?? $visit->report->check_in_lng,

                            'sales_accuracy' =>
                                $visit->report->sales_accuracy,

                            'distance_from_store' =>
                                $visit->report->distance_from_store,

                            'is_outside_radius' =>
                                $visit->report->is_outside_radius,

                            'location_captured_at' =>
                                $visit->report->location_captured_at
                                    ? $visit->report
                                        ->location_captured_at
                                        ->toDateTimeString()
                                    : ($visit->report->visited_at
                                        ? $visit->report->visited_at->toDateTimeString()
                                        : null),

                            'check_in_photo' =>
                                $visit->report->check_in_photo,

                            'check_in_lat' =>
                                $visit->report->check_in_lat,

                            'check_in_lng' =>
                                $visit->report->check_in_lng,
                        ]
                        : null,
                ];
            })
            ->values();

        //Sales Filter Options

        $sales = User::query()
            ->where('role', 'sales')
            ->orderBy('name')
            ->get([
                'id',
                'name',
            ]);

        //Return Inertia

        return Inertia::render(
            'Visit_Reports/main_visitreport',
            [
                'visits' => [
                    'data' => $visitData,

                    'current_page' =>
                        $currentPage,

                    'last_page' =>
                        $lastPage,

                    'per_page' =>
                        $perPage,

                    'total' =>
                        $totalFilteredVisits,

                    'from' =>
                        $totalFilteredVisits > 0
                            ? (($currentPage - 1) * $perPage) + 1
                            : null,

                    'to' =>
                        min(
                            $currentPage * $perPage,
                            $totalFilteredVisits
                        ),
                ],

                'sales' => $sales,

                'filters' => $filters,

                'statistics' => [
                    'total_visits' =>
                        $totalVisits,

                    'completed_visits' =>
                        $completedVisits,

                    'corrected_visits' =>
                        $correctedVisits,

                    'active_visits' =>
                        $activeVisits,

                    'cancelled_visits' =>
                        $cancelledVisits,

                    'average_duration' =>
                        $averageDuration,
                ],
            ]
        );
    }

    public function export(
        Request $request,
        OdooService $odooService
    )
    {
        $filters = $this->resolveFilters($request);

        $visits = $this->buildVisitsQuery($filters)
            ->latest('check_in_at')
            ->get();

        $partnerMap = $this->loadPartnerMap(
            $visits,
            $odooService
        );

        if ($filters['search'] !== '') {
            $search = mb_strtolower(
                $filters['search']
            );

            $visits = $visits
                ->filter(function ($visit) use (
                    $search,
                    $partnerMap
                ) {
                    $salesName = mb_strtolower(
                        $visit->sales?->name ?? ''
                    );

                    $partner = $partnerMap->get(
                        $visit->odoo_partner_id
                    );

                    $storeName = mb_strtolower(
                        $visit->store_type === 'field'
                            ? ($visit->fieldStore?->store_name ?? '')
                            : ($partner['name']
                                ?? $partner['display_name']
                                ?? '')
                    );

                    return str_contains(
                        $salesName,
                        $search
                    ) || str_contains(
                        $storeName,
                        $search
                    );
                })
                ->values();
        }

        $exportData = $visits->map(
            function ($visit) use ($partnerMap) {
            $isFieldStore = ($visit->store_type ?? 'regular') === 'field';
            $partner = $isFieldStore
                ? [
                    'name' => $visit->fieldStore?->store_name,
                ]
                : $partnerMap->get($visit->odoo_partner_id);

                $duration = '-';

                if (
                    $visit->check_in_at &&
                    $visit->check_out_at
                ) {
                    $seconds = $visit->check_in_at
                        ->diffInSeconds(
                            $visit->check_out_at
                        );

                    $minutes = intdiv($seconds, 60);
                    $remainingSeconds = $seconds % 60;

                    $duration = sprintf(
                        '%dm %02ds',
                        $minutes,
                        $remainingSeconds
                    );
                }

                $effectiveReportData = $visit->report?->effectiveData() ?? [];
                $activities = $effectiveReportData['activities'] ?? [];

                if (is_array($activities)) {
                    $activities = implode(
                        ', ',
                        $activities
                    );
                }

                return [
                    'date' => $visit->visit_date?->format('Y-m-d'),

                    'sales' => $visit->sales?->name
                        ?? 'Unknown Sales',

                    'username' => $visit->sales?->username,

                    'store' => $partner['name']
                        ?? $partner['display_name']
                        ?? 'Unknown Store',

                    'status' => $visit->status,

                    'check_in' => $visit->check_in_at
                        ? $visit->check_in_at->format('H:i:s')
                        : null,

                    'check_out' => $visit->check_out_at
                        ? $visit->check_out_at->format('H:i:s')
                        : null,

                    'duration' => $duration,

                    'activities' => $activities,

                    'pic_name' => $effectiveReportData['pic_name'] ?? null,

                    'stock_percentage' =>
                        $effectiveReportData['stock_percentage'] ?? null,

                    'stock_pcs' =>
                        $effectiveReportData['stock_pcs'] ?? null,

                    'distance' =>
                        $visit->report?->distance_from_store,

                    'geofence' =>
                        $visit->report?->is_outside_radius
                            ? 'Outside Radius'
                            : 'Normal',

                    'latitude' =>
                        $visit->report?->sales_latitude,

                    'longitude' =>
                        $visit->report?->sales_longitude,

                    'location_captured_at' =>
                        $visit->report?->location_captured_at
                            ? $visit->report
                                ->location_captured_at
                                ->toDateTimeString()
                            : null,

                    'notes' =>
                        $effectiveReportData['notes'] ?? null,
                ];
            }
        )->values()->all();

        $filename = sprintf(
            'visit-reports-%s.xlsx',
            now()->format('Y-m-d_H-i-s')
        );

        $path = storage_path(
            "app/{$filename}"
        );

        (new VisitReportExport(
            $exportData
        ))->download($path);

        return response()
            ->download($path, $filename)
            ->deleteFileAfterSend(true);
    }

    private function resolveFilters(Request $request): array
    {
        $quickFilter = $request->input(
            'quick_filter',
            'today'
        );

        $date = $request->input('date');

        if (
            $quickFilter === 'today' &&
            !$request->has('date')
        ) {
            $date = today()->toDateString();
        }

        return [
            'date' => $date,
            'sales_id' => $request->input('sales_id'),
            'status' => $request->input('status'),
            'search' => trim(
                $request->input('search', '')
            ),
            'quick_filter' => $quickFilter,
        ];
    }

    private function buildVisitsQuery(array $filters)
    {
        $query = StoreVisit::query()
            ->with([
                'sales:id,name,username,profile_photo_url',
                'report.corrections',
                'fieldStore',
            ])
            ->when(
                $filters['date'],
                fn ($query, $date) =>
                    $query->whereDate(
                        'visit_date',
                        $date
                    )
            )
            ->when(
                $filters['sales_id'],
                fn ($query, $salesId) =>
                    $query->where(
                        'sales_id',
                        $salesId
                    )
            )
            ->when(
                $filters['status'],
                fn ($query, $status) =>
                    $query->where(
                        'status',
                        $status
                    )
            );

        switch ($filters['quick_filter']) {
            case 'flagged':
                $query->whereHas('report', function ($query) {
                    $query->where(
                        'is_outside_radius',
                        true
                    );
                });
                break;

            case 'active':
                $query->where(
                    'status',
                    'IN_VISIT'
                );
                break;

            case 'all':
            case 'today':
            case 'custom_date':
            default:
                break;
        }

        return $query;
    }

    private function loadPartnerMap(
        $visits,
        OdooService $odooService
    )
    {
        $partnerIds = $visits
            ->pluck('odoo_partner_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($partnerIds)) {
            return collect();
        }

        return collect(
            $odooService->getCompleteStoreData(
                $partnerIds
            )
        )->keyBy('id');
    }
}
