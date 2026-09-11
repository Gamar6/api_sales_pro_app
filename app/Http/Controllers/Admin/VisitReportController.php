<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StoreVisit;
use App\Models\User;
use App\Services\OdooService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VisitReportController extends Controller
{
    public function index(Request $request, OdooService $odooService)
    {
        $filters = [
            'date' => $request->input('date'),

            'sales_id' => $request->input('sales_id'),

            'status' => $request->input('status'),

            'search' => $request->input('search'),

            'quick_filter' => $request->input('quick_filter', 'today'),
        ];

        $visitsQuery = StoreVisit::query()
            ->with([
                'sales:id,name,username,profile_photo_url',
                'report',
            ])
            ->when(
                $filters['date'],
                function ($query, $date) {
                    $query->whereDate('visit_date', $date);
                }
            )
            ->when(
                $filters['sales_id'],
                function ($query, $salesId) {
                    $query->where('sales_id', $salesId);
                }
            )
            ->when(
                $filters['status'],
                function ($query, $status) {
                    $query->where('status', $status);
                }
            )
            ->when(
                $filters['search'],
                function ($query, $search) {

                    $query->whereHas(
                        'sales',
                        function ($salesQuery) use ($search) {
                            $salesQuery->where(
                                'name',
                                'like',
                                "%{$search}%"
                            );
                        }
                    );
                }
            );

        $statisticsQuery = clone $visitsQuery;

        $totalVisits = (clone $statisticsQuery)->count();

        $completedVisits = (clone $statisticsQuery)
            ->where('status', 'COMPLETED')
            ->count();

        $activeVisits = (clone $statisticsQuery)
            ->where('status', 'IN_VISIT')
            ->count();

        $cancelledVisits = (clone $statisticsQuery)
            ->where('status', 'CANCELLED')
            ->count();

        $completedVisitsData = (clone $statisticsQuery)
            ->whereNotNull('check_in_at')
            ->whereNotNull('check_out_at')
            ->get([
                'check_in_at',
                'check_out_at',
            ]);

        $averageDurationSeconds = $completedVisitsData
            ->map(function ($visit) {
                return $visit->check_in_at
                    ->diffInSeconds($visit->check_out_at);
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

        $allVisits = (clone $visitsQuery)
            ->latest('check_in_at')
            ->get();

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

        if (!empty($filters['search'])) {

            $search = mb_strtolower(
                trim($filters['search'])
            );

            $allVisits = $allVisits
                ->filter(function ($visit) use (
                    $search,
                    $partnerMap
                ) {

                    $salesName = mb_strtolower(
                        $visit->sales?->name ?? ''
                    );

                    $salesMatches = str_contains(
                        $salesName,
                        $search
                    );

                    $partner = $partnerMap->get(
                        $visit->odoo_partner_id
                    );

                    $storeName = mb_strtolower(
                        $partner['name']
                            ?? $partner['display_name']
                            ?? ''
                    );

                    $storeMatches = str_contains(
                        $storeName,
                        $search
                    );

                    return $salesMatches || $storeMatches;
                })
                ->values();
        }

        $perPage = 10;

        $currentPage = max(
            1,
            (int) $request->input('page', 1)
        );

        $totalFilteredVisits = $allVisits->count();

        $paginatedVisits = $allVisits
            ->slice(
                ($currentPage - 1) * $perPage,
                $perPage
            )
            ->values();

        $visitData = $paginatedVisits
            ->map(function ($visit) use ($partnerMap) {

                $durationSeconds = null;

                if (
                    $visit->check_in_at &&
                    $visit->check_out_at
                ) {
                    $durationSeconds = $visit->check_in_at
                        ->diffInSeconds(
                            $visit->check_out_at
                        );
                }

                $partner = $partnerMap->get(
                    $visit->odoo_partner_id
                );

                return [

                    'id' => $visit->id,

                    'odoo_partner_id' =>
                        $visit->odoo_partner_id,

                    'visit_date' => $visit->visit_date
                        ? $visit->visit_date
                            ->format('Y-m-d')
                        : null,

                    'activities' => $visit->report?->activities ?? [],

                    'status' => $visit->status,

                    'check_in_at' => $visit->check_in_at
                        ? $visit->check_in_at
                            ->format('H:i:s')
                        : null,

                    'check_out_at' => $visit->check_out_at
                        ? $visit->check_out_at
                            ->format('H:i:s')
                        : null,

                    'duration_seconds' =>
                        $durationSeconds,

                    'sales' => [

                        'id' => $visit->sales?->id,

                        'name' => $visit->sales?->name
                            ?? 'Unknown Sales',

                        'username' =>
                            $visit->sales?->username,

                        'profile_photo_url' =>
                            $visit->sales?->profile_photo_url,
                    ],

                    'partner' => [

                        'id' => $partner['id']
                            ?? $visit->odoo_partner_id,

                        'name' => $partner['name']
                            ?? $partner['display_name']
                            ?? 'Unknown Store',

                        'display_name' =>
                            $partner['display_name']
                            ?? null,

                        'street' =>
                            $partner['street']
                            ?? null,

                        'street2' =>
                            $partner['street2']
                            ?? null,

                        'city' =>
                            $partner['city']
                            ?? null,

                        'phone' =>
                            $partner['phone']
                            ?? null,

                        'mobile' =>
                            $partner['mobile']
                            ?? null,

                        'email' =>
                            $partner['email']
                            ?? null,

                        'vat' =>
                            $partner['vat']
                            ?? null,

                        'state' => isset(
                            $partner['state_id'][1]
                        )
                            ? $partner['state_id'][1]
                            : null,

                        'country' => isset(
                            $partner['country_id'][1]
                        )
                            ? $partner['country_id'][1]
                            : null,
                    ],

                    'report' => $visit->report
                        ? [

                            'id' =>
                                $visit->report->id,

                            'pic_name' =>
                                $visit->report->pic_name,

                            'activities' =>
                                $visit->report->activities,

                            'stock_percentage' =>
                                $visit->report->stock_percentage,

                            'stock_pcs' =>
                                $visit->report->stock_pcs,

                            'notes' =>
                                $visit->report->notes,

                            'photos' =>
                                $visit->report->photos,
                        ]
                        : null,
                ];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Pagination Structure For Vue
        |--------------------------------------------------------------------------
        */

        $lastPage = max(
            1,
            (int) ceil(
                $totalFilteredVisits / $perPage
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Sales Filter Options
        |--------------------------------------------------------------------------
        */

        $sales = User::query()
            ->where('role', 'sales')
            ->orderBy('name')
            ->get([
                'id',
                'name',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Return Inertia
        |--------------------------------------------------------------------------
        */

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

                /*
                |--------------------------------------------------------------------------
                | Filter Options
                |--------------------------------------------------------------------------
                */

                'sales' => $sales,

                'filters' => $filters,

                /*
                |--------------------------------------------------------------------------
                | Statistics
                |--------------------------------------------------------------------------
                */

                'statistics' => [

                    'total_visits' =>
                        $totalVisits,

                    'completed_visits' =>
                        $completedVisits,

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
}
