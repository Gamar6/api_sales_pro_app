<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StoreVisit;
use App\Models\User;
use App\Models\VisitReport;
use App\Services\OdooService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VisitExceptionController extends Controller
{
    public function __construct(
        protected OdooService $odooService
    ) {}

    public function index(Request $request): Response
    {
        $date = $request->input('date');
        $salesId = $request->input('sales_id');
        $search = $request->input('search');
        $status = $request->input('status', 'all');

        $query = VisitReport::query()
            ->with([
                'visit.sales:id,name,username,profile_photo_url',
            ])
            ->where('is_outside_radius', true)
            ->whereHas('visit', function ($visitQuery) {
                $visitQuery->where('status', 'completed');
            })
            ->latest('location_captured_at');

        if ($date) {
            $query->whereDate('location_captured_at', $date);
        }

        if ($salesId) {
            $query->whereHas('visit', function ($visitQuery) use ($salesId) {
                $visitQuery->where('sales_id', $salesId);
            });
        }

        if ($status !== 'all') {
            $query->whereHas('visit', function ($visitQuery) use ($status) {
                $visitQuery->where('status', $status);
            });
        }

        if ($search) {
            $matchingPartnerIds = $this->odooService
                ->searchPartnerIdsByName($search);

            $query->whereHas('visit', function ($visitQuery) use (
                $search,
                $matchingPartnerIds
            ) {
                $visitQuery->where(function ($query) use (
                    $search,
                    $matchingPartnerIds
                ) {
                    $query->whereHas('sales', function ($salesQuery) use ($search) {
                        $salesQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%");
                    });

                    if (!empty($matchingPartnerIds)) {
                        $query->orWhereIn(
                            'odoo_partner_id',
                            $matchingPartnerIds
                        );
                    }
                });
            });
        }

        $reports = $query->get();

        $partnerIds = $reports
            ->map(fn ($report) => $report->visit?->odoo_partner_id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $partners = [];

        if (!empty($partnerIds)) {
            $partnerData = $this->odooService->getCompleteStoreData(
                $partnerIds
            );

            foreach ($partnerData as $partner) {
                $partners[$partner['id']] = $partner;
            }
        }

        $exceptions = $reports->map(function (VisitReport $report) use ($partners) {
            $visit = $report->visit;
            $sales = $visit?->sales;

            $partnerId = $visit?->odoo_partner_id;
            $store = $partners[$partnerId] ?? [];

            return [
                'id' => $report->id,
                'store_visit_id' => $report->store_visit_id,

                'sales' => [
                    'id' => $sales?->id,
                    'name' => $sales?->name ?? '-',
                    'username' => $sales?->username ?? '-',
                    'profile_photo_url' => $sales?->profile_photo_url,
                ],

                'store' => [
                    'id' => $partnerId,

                    'name' => $store['name']
                        ?? $store['display_name']
                        ?? 'Unknown Store',

                    'city' => $store['city'] ?? '-',
                    'address' => $store['street']
                        ?? $store['address']
                        ?? '-',

                    'latitude' => isset($store['partner_latitude'])
                        && $store['partner_latitude'] !== false
                        ? (float) $store['partner_latitude']
                        : null,

                    'longitude' => isset($store['partner_longitude'])
                        && $store['partner_longitude'] !== false
                        ? (float) $store['partner_longitude']
                        : null,
                ],

                'visit_status' => $visit?->status,

                'sales_latitude' => $report->sales_latitude,
                'sales_longitude' => $report->sales_longitude,
                'sales_accuracy' => $report->sales_accuracy,
                'distance_from_store' => $report->distance_from_store,

                'location_captured_at' => $report->location_captured_at
                    ? $report->location_captured_at->toDateTimeString()
                    : null,

                'check_in_at' => $visit?->check_in_at,
                'check_out_at' => $visit?->check_out_at,

                'is_outside_radius' => (bool) $report->is_outside_radius,
            ];
        });

        $statistics = [
            'total' => $exceptions->count(),

            'critical' => $exceptions
                ->filter(fn ($item) => $item['distance_from_store'] >= 1000)
                ->count(),

            'warning' => $exceptions
                ->filter(function ($item) {
                    $distance = $item['distance_from_store'];

                    return $distance >= 100
                        && $distance < 1000;
                })
                ->count(),

            'average_distance' => round(
                $exceptions->avg('distance_from_store') ?? 0,
                2
            ),
        ];

        $sales = User::query()
            ->whereIn('role', ['sales', 'user'])
            ->select([
                'id',
                'name',
                'username',
            ])
            ->orderBy('name')
            ->get();

        return Inertia::render('Visit_Exceptions/index', [
            'exceptions' => [
                'data' => $exceptions->values(),
                'total' => $exceptions->count(),
            ],

            'sales' => $sales,

            'filters' => [
                'date' => $date,
                'sales_id' => $salesId,
                'search' => $search,
                'status' => $status,
            ],

            'statistics' => $statistics,
        ]);
    }
}
