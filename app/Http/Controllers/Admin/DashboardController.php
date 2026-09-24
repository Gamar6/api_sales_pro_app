<?php

namespace App\Http\Controllers\Admin;

use App\Exports\DashboardSummaryExport;
use App\Http\Controllers\Controller;
use App\Models\StoreVisit;
use App\Models\User;
use App\Models\VisitReport;
use App\Services\OdooService;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DashboardController extends Controller
{
    //Dashboard utama.
    public function index(
        Request $request,
        OdooService $odooService
    ) {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $dateFrom = $dateFrom
            ? Carbon::parse($dateFrom)->startOfDay()
            : null;

        $dateTo = $dateTo
            ? Carbon::parse($dateTo)->endOfDay()
            : null;

        $totalCheckInsToday = StoreVisit::checkedInToday()->count();

        $activeSalesCount = User::query()
            ->where('role', 'sales')
            ->where('status', 'active')
            ->count();

        $checkInTargetPerSales = 5;
        $checkInTargetToday =
            $activeSalesCount * $checkInTargetPerSales;

        $averageVisitDurationSeconds = StoreVisit::query()
            ->whereNotNull('check_in_at')
            ->whereNotNull('check_out_at')
            ->whereDate('check_in_at', today())
            ->average(
                DB::raw(
                    'TIMESTAMPDIFF(
                        SECOND,
                        check_in_at,
                        check_out_at
                    )'
                )
            );

        $averageVisitDuration = '0m 00s';

        if ($averageVisitDurationSeconds !== null) {
            $totalSeconds = (int) round(
                $averageVisitDurationSeconds
            );

            $minutes = intdiv($totalSeconds, 60);
            $seconds = $totalSeconds % 60;

            $averageVisitDuration = sprintf(
                '%dm %02ds',
                $minutes,
                $seconds
            );
        }

        $stores = $this->loadRetentionSnapshot(
            $odooService
        );

        $outsideRadiusQuery = VisitReport::query()
            ->with([
                'visit.sales:id,name,username',
            ])
            ->where('is_outside_radius', true)
            ->whereHas('visit', function ($query) use (
                $dateFrom,
                $dateTo
            ) {
                $query->where('status', 'COMPLETED');

                if ($dateFrom) {
                    $query->where(
                        'visit_date',
                        '>=',
                        $dateFrom->toDateString()
                    );
                }

                if ($dateTo) {
                    $query->where(
                        'visit_date',
                        '<=',
                        $dateTo->toDateString()
                    );
                }
            })
            ->latest('location_captured_at')
            ->limit(5)
            ->get();

        $outsideRadiusPartnerIds = $outsideRadiusQuery
            ->map(
                fn ($report) =>
                    $report->visit?->odoo_partner_id
            )
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $outsideRadiusPartners = $this->loadOdooPartners(
            $odooService,
            $outsideRadiusPartnerIds->all()
        );

        $outsideRadiusAlerts = $outsideRadiusQuery
            ->map(function ($report) use ($outsideRadiusPartners) {
                $visit = $report->visit;
                $partnerId = (int) (
                    $visit?->odoo_partner_id ?? 0
                );

                $partner = $outsideRadiusPartners->get(
                    $partnerId
                );

                return [
                    'id' => $report->id,
                    'store_visit_id' => $report->store_visit_id,
                    'sales' => [
                        'id' => $visit?->sales_id,
                        'name' => $visit?->sales?->name
                            ?? 'Unknown Sales',
                    ],
                    'store' => [
                        'id' => $partnerId,
                        'name' => $partner['name']
                            ?? "Toko #{$partnerId}",
                        'city' => $partner['city'] ?? '-',
                        'street' => $partner['street'] ?? '-',
                    ],
                    'distance_from_store' =>
                        $report->distance_from_store,
                    'sales_accuracy' =>
                        $report->sales_accuracy,
                    'location_captured_at' =>
                        $report->location_captured_at
                            ?->toDateTimeString(),
                    'visit_date' => $visit?->visit_date,
                ];
            })
            ->values();

        $orderVisitReports = $this->getOrderVisitReports(
            $dateFrom,
            $dateTo
        );

        $orderedPartnerIds = $orderVisitReports
            ->map(
                fn ($report) =>
                    $report->visit?->odoo_partner_id
            )
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $odooPartners = $this->loadOdooPartners(
            $odooService,
            $orderedPartnerIds->all()
        );

        $salesOrderChart = $this->buildSalesOrderChart(
            $orderVisitReports
        );

        $areaOrderPerformance =
            $this->buildAreaOrderPerformance(
                $stores,
                $orderVisitReports,
                $odooPartners
            );

        return Inertia::render(
            'Dashboard/Main_dashboard',
            [
                'dashboardStats' => [
                    'totalCheckInsToday' =>
                        $totalCheckInsToday,

                    'activeSalesCount' =>
                        $activeSalesCount,

                    'checkInTargetPerSales' =>
                        $checkInTargetPerSales,

                    'checkInTargetToday' =>
                        $checkInTargetToday,

                    'averageVisitDuration' =>
                        $averageVisitDuration,

                    'uniqueOrderStores' =>
                        $orderVisitReports
                            ->map(
                                fn ($report) =>
                                    $report->visit
                                        ?->odoo_partner_id
                            )
                            ->filter()
                            ->unique()
                            ->count(),

                    'orderEvents' =>
                        $orderVisitReports->count(),

                    'totalSnapshotStores' =>
                        $stores->count(),
                ],

                'salesOrderChart' =>
                    $salesOrderChart,

                'stores' =>
                    $areaOrderPerformance,

                'outsideRadiusAlerts' =>
                    $outsideRadiusAlerts,

                'filters' => [
                    'date_from' =>
                        $dateFrom?->format('Y-m-d'),

                    'date_to' =>
                        $dateTo?->format('Y-m-d'),

                    'month' =>
                        $dateFrom
                            ? $dateFrom->month
                            : null,

                    'year' =>
                        $dateFrom
                            ? $dateFrom->year
                            : null,
                ],
            ]
        );
    }

    //Load the latest retention snapshot.
    private function loadRetentionSnapshot(
        OdooService $odooService
    ): Collection {
        $folderPath = storage_path(
            'app/private/retensi'
        );

        $files = File::glob(
            "{$folderPath}/*_retensi_jabodetabek.xlsx"
        );

        if (empty($files)) {
            return collect();
        }

        usort(
            $files,
            fn ($a, $b) =>
                File::lastModified($b)
                <=>
                File::lastModified($a)
        );

        $latestFile = $files[0];

        $cacheKey = 'dashboard:retention:stores:' . hash(
            'sha256',
            basename($latestFile) . ':' .
            File::lastModified($latestFile)
        );

        return collect(
            Cache::remember(
                $cacheKey,
                now()->addMinutes(15),
                function () use (
                    $latestFile,
                    $odooService
                ) {
                    $excelData = (new FastExcel)
                        ->import($latestFile);

                    if ($excelData->isEmpty()) {
                        return [];
                    }

                    $partnerIds = $excelData
                        ->pluck('partner_id')
                        ->filter()
                        ->map(fn ($id) => (int) $id)
                        ->unique()
                        ->values()
                        ->toArray();

                    $odooStores = collect();

                    foreach (
                        array_chunk($partnerIds, 500)
                        as $partnerChunk
                    ) {
                        $odooStoresRaw =
                            $odooService->execute_kw(
                                'res.partner',
                                'search_read',
                                [
                                    [
                                        [
                                            'id',
                                            'in',
                                            $partnerChunk,
                                        ],
                                    ],
                                ],
                                [
                                    'fields' => [
                                        'id',
                                        'name',
                                        'display_name',
                                        'street',
                                        'partner_latitude',
                                        'partner_longitude',
                                        'email',
                                    ],
                                ]
                            );

                        $odooStores = $odooStores->merge(
                            collect($odooStoresRaw)
                        );
                    }

                    $odooStores = $odooStores->keyBy(
                        fn ($store) =>
                            (int) $store['id']
                    );

                    return $excelData
                        ->map(
                            function (array $row) use (
                                $odooStores
                            ) {
                                $partnerId = (int) (
                                    $row['partner_id'] ?? 0
                                );

                                if ($partnerId <= 0) {
                                    return null;
                                }

                                $storeDetail =
                                    $odooStores->get(
                                        $partnerId
                                    );

                                $lastOrderDate = null;

                                if (
                                    !empty(
                                        $row['last_order_date']
                                    )
                                ) {
                                    if (
                                        $row['last_order_date']
                                        instanceof DateTimeInterface
                                    ) {
                                        $lastOrderDate =
                                            $row[
                                                'last_order_date'
                                            ]->format('Y-m-d');
                                    } else {
                                        $lastOrderDate =
                                            substr(
                                                (string) $row[
                                                    'last_order_date'
                                                ],
                                                0,
                                                10
                                            );
                                    }
                                }

                                return [
                                    'partner_id' =>
                                        $partnerId,

                                    'partner_name' =>
                                        (string) (
                                            $row['partner_name']
                                            ?? ''
                                        ),

                                    'kota' =>
                                        trim(
                                            (string) (
                                                $row['kota'] ?? ''
                                            )
                                        ),

                                    'sales_name' =>
                                        (string) (
                                            $row['sales_name']
                                            ?? 'Unassigned'
                                        ),

                                    'phone' =>
                                        (string) (
                                            $row['phone_clean']
                                            ?? ''
                                        ),

                                    'last_order_date' =>
                                        $lastOrderDate,

                                    'days_since' =>
                                        (int) (
                                            $row['days_since'] ?? 0
                                        ),

                                    'weeks_since' =>
                                        (int) (
                                            $row['weeks_since'] ?? 0
                                        ),

                                    'retensi_status' =>
                                        (string) (
                                            $row['retensi_status']
                                            ?? 'DEAD ZONE'
                                        ),

                                    'aktif_group' =>
                                        (string) (
                                            $row['aktif_group']
                                            ?? 'NON_AKTIF'
                                        ),

                                    'avg_retensi_weeks' =>
                                        (float) (
                                            $row[
                                                'avg_retensi_weeks'
                                            ] ?? 0
                                        ),

                                    'gap_vs_average' =>
                                        (float) (
                                            $row[
                                                'gap_vs_average'
                                            ] ?? 0
                                        ),

                                    'total_sales' =>
                                        (float) (
                                            $row[
                                                'total_sales_2024_plus'
                                            ] ?? 0
                                        ),

                                    'priority' =>
                                        (int) (
                                            $row['priority'] ?? 99
                                        ),

                                    'alamat' =>
                                        $storeDetail['street']
                                        ?? null,

                                    'latitude' =>
                                        isset(
                                            $storeDetail[
                                                'partner_latitude'
                                            ]
                                        )
                                        &&
                                        $storeDetail[
                                            'partner_latitude'
                                        ] !== false
                                            ? (float)
                                                $storeDetail[
                                                    'partner_latitude'
                                                ]
                                            : null,

                                    'longitude' =>
                                        isset(
                                            $storeDetail[
                                                'partner_longitude'
                                            ]
                                        )
                                        &&
                                        $storeDetail[
                                            'partner_longitude'
                                        ] !== false
                                            ? (float)
                                                $storeDetail[
                                                    'partner_longitude'
                                                ]
                                            : null,

                                    'email' =>
                                        $storeDetail['email']
                                        ?? null,
                                ];
                            }
                        )
                        ->filter()
                        ->values()
                        ->all();
                }
            )
        )
            ->filter(
                fn ($store) =>
                    !empty($store['partner_id'])
                    && !empty($store['kota'])
            )
            ->unique('partner_id')
            ->values();
    }

    //Get visit reports containing Order activity.
    private function getOrderVisitReports(
        ?Carbon $dateFrom,
        ?Carbon $dateTo
    ): Collection {
        $query = VisitReport::query()
            ->whereJsonContains('activities', 'Order')
            ->whereHas(
                'visit',
                function ($query) use (
                    $dateFrom,
                    $dateTo
                ) {
                    if ($dateFrom) {
                        $query->where(
                            'visit_date',
                            '>=',
                            $dateFrom
                        );
                    }

                    if ($dateTo) {
                        $query->where(
                            'visit_date',
                            '<=',
                            $dateTo
                        );
                    }
                }
            )
            ->with([
                'visit' => function ($query) {
                    $query->select([
                        'id',
                        'odoo_partner_id',
                        'sales_id',
                        'visit_date',
                    ]);
                },
            ]);

        return $query
            ->get()
            ->filter(
                fn ($report) =>
                    $report->visit
                    && !empty(
                        $report->visit->odoo_partner_id
                    )
            )
            ->values();
    }

    //Load Odoo partners by IDs.
    private function loadOdooPartners(
        OdooService $odooService,
        array $partnerIds
    ): Collection {
        if (empty($partnerIds)) {
            return collect();
        }

        $partners = collect();

        foreach (
            array_chunk(
                array_values(
                    array_unique(
                        array_map(
                            'intval',
                            $partnerIds
                        )
                    )
                ),
                500
            ) as $partnerChunk
        ) {
            $result = $odooService->execute_kw(
                'res.partner',
                'search_read',
                [
                    [
                        [
                            'id',
                            'in',
                            $partnerChunk,
                        ],
                    ],
                ],
                [
                    'fields' => [
                        'id',
                        'name',
                        'display_name',
                        'street',
                        'city',
                        'state_id',
                        'country_id',
                        'phone',
                        'mobile',
                        'email',
                    ],
                ]
            );

            $partners = $partners->merge(
                collect($result)
            );
        }

        return $partners->keyBy(
            fn ($partner) =>
                (int) $partner['id']
        );
    }

    //Build sales order performance.
    private function buildSalesOrderChart(
        Collection $orderVisitReports
    ): Collection {
        $salesUsers = User::query()
            ->where('role', 'sales')
            ->get();

        return $salesUsers
            ->map(
                function ($user) use (
                    $orderVisitReports
                ) {
                    $reports = $orderVisitReports
                        ->filter(
                            fn ($report) =>
                                (int) (
                                    $report->visit?->sales_id
                                )
                                ===
                                (int) $user->id
                        )
                        ->values();

                    $uniqueStoreIds = $reports
                        ->map(
                            fn ($report) =>
                                $report->visit
                                    ?->odoo_partner_id
                        )
                        ->filter()
                        ->map(fn ($id) => (int) $id)
                        ->unique()
                        ->values();

                    $weeklyData = [
                        'week_1' => 0,
                        'week_2' => 0,
                        'week_3' => 0,
                        'week_4' => 0,
                        'week_5' => 0,
                    ];

                    $weeklyStoreIds = [
                        'week_1' => collect(),
                        'week_2' => collect(),
                        'week_3' => collect(),
                        'week_4' => collect(),
                        'week_5' => collect(),
                    ];

                    foreach ($reports as $report) {
                        $visit = $report->visit;

                        if (!$visit?->visit_date) {
                            continue;
                        }

                        $weekNumber = Carbon::parse(
                            $visit->visit_date
                        )->weekOfMonth;

                        $key = "week_{$weekNumber}";

                        if (!isset($weeklyStoreIds[$key])) {
                            continue;
                        }

                        $partnerId =
                            $visit->odoo_partner_id;

                        if ($partnerId) {
                            $weeklyStoreIds[$key]->push(
                                (int) $partnerId
                            );
                        }
                    }

                    foreach (
                        $weeklyStoreIds
                        as $week => $partnerIds
                    ) {
                        $weeklyData[$week] =
                            $partnerIds
                                ->unique()
                                ->count();
                    }

                    return [
                        'salesId' => $user->id,
                        'name' =>
                            $user->name
                            ?? 'Unknown Sales',
                        'orders' =>
                            $uniqueStoreIds->count(),
                        'orderEvents' =>
                            $reports->count(),
                        'weekly' =>
                            $weeklyData,
                    ];
                }
            )
            ->sortByDesc('orders')
            ->values();
    }

    //Build area order performance.
    private function buildAreaOrderPerformance(
        Collection $stores,
        Collection $orderVisitReports,
        Collection $odooPartners
    ): Collection {
        $snapshotByPartner = $stores->keyBy(
            fn ($store) =>
                (int) $store['partner_id']
        );

        $orderVisitsByPartner = $orderVisitReports
            ->filter(
                fn ($report) =>
                    $report->visit
                    && !empty(
                        $report->visit->odoo_partner_id
                    )
            )
            ->groupBy(
                fn ($report) =>
                    (int) $report->visit->odoo_partner_id
            );

        $orderedSnapshotPartners = $orderVisitsByPartner
            ->filter(
                fn ($reports, $partnerId) =>
                    $snapshotByPartner->has(
                        (int) $partnerId
                    )
            );

        return $stores
            ->groupBy(
                fn ($store) =>
                    trim(
                        (string) ($store['kota'] ?? '')
                    )
            )
            ->filter(
                fn ($cityStores, $city) =>
                    $city !== ''
            )
            ->map(
                function (
                    Collection $cityStores,
                    $city
                ) use (
                    $orderedSnapshotPartners,
                    $orderVisitsByPartner,
                    $odooPartners
                ) {
                    $allPartnerIds = $cityStores
                        ->pluck('partner_id')
                        ->map(fn ($id) => (int) $id)
                        ->unique()
                        ->values();

                    $totalStores =
                        $allPartnerIds->count();

                    $orderedPartnerIds = $allPartnerIds
                        ->filter(
                            fn ($partnerId) =>
                                $orderedSnapshotPartners->has(
                                    $partnerId
                                )
                        )
                        ->values();

                    $uniqueOrders =
                        $orderedPartnerIds->count();

                    $orderEvents = $orderedPartnerIds
                        ->sum(
                            fn ($partnerId) =>
                                $orderVisitsByPartner
                                    ->get(
                                        $partnerId,
                                        collect()
                                    )
                                    ->count()
                        );

                    $salesIds = $orderedPartnerIds
                        ->flatMap(
                            function ($partnerId) use (
                                $orderVisitsByPartner
                            ) {
                                return $orderVisitsByPartner
                                    ->get(
                                        $partnerId,
                                        collect()
                                    )
                                    ->map(
                                        fn ($report) =>
                                            $report->visit
                                                ?->sales_id
                                    );
                            }
                        )
                        ->filter()
                        ->map(fn ($id) => (int) $id)
                        ->unique()
                        ->values();

                    $reps = $salesIds->count();

                    $percentage = $totalStores > 0
                        ? round(
                            ($uniqueOrders / $totalStores) * 100
                        )
                        : 0;

                    $odooMatchedOrders =
                        $orderedPartnerIds
                            ->filter(
                                fn ($partnerId) =>
                                    $odooPartners->has(
                                        $partnerId
                                    )
                            )
                            ->count();

                    return [
                        'name' =>
                            $city,
                        'reps' =>
                            $reps,
                        'visited' =>
                            $uniqueOrders,
                        'unique_orders' =>
                            $uniqueOrders,
                        'order_events' =>
                            $orderEvents,
                        'total' =>
                            $totalStores,
                        'percentage' =>
                            $percentage,
                        'odoo_matched_orders' =>
                            $odooMatchedOrders,
                    ];
                }
            )
            ->sort(
                function ($a, $b) {
                    if (
                        $a['visited']
                        !==
                        $b['visited']
                    ) {
                        return $b['visited']
                            <=>
                            $a['visited'];
                    }

                    return $b['percentage']
                        <=>
                        $a['percentage'];
                }
            )
            ->values();
    }

    //Export dashboard summary to Excel.
    public function exportSummary(
        Request $request,
        OdooService $odooService
    ): BinaryFileResponse  {
        $dateFrom = $request->filled('date_from')
            ? Carbon::parse(
                $request->date_from
            )->startOfDay()
            : now()->startOfDay();

        $dateTo = $request->filled('date_to')
            ? Carbon::parse(
                $request->date_to
            )->endOfDay()
            : now()->endOfDay();

        if ($dateFrom->gt($dateTo)) {
            abort(422, 'Invalid date range.');
        }

        $checkIns = StoreVisit::query()
            ->whereBetween('visit_date', [
                $dateFrom->toDateString(),
                $dateTo->toDateString(),
            ])
            ->whereNotNull('check_in_at')
            ->count();

        $activeSales = User::query()
            ->where('role', 'sales')
            ->where('status', 'active')
            ->count();

        $targetPerSales = 5;
        $targetCheckIns =
            $activeSales * $targetPerSales;

        $averageSeconds = StoreVisit::query()
            ->whereBetween('visit_date', [
                $dateFrom->toDateString(),
                $dateTo->toDateString(),
            ])
            ->whereNotNull('check_in_at')
            ->whereNotNull('check_out_at')
            ->selectRaw(
                'AVG(
                    TIMESTAMPDIFF(
                        SECOND,
                        check_in_at,
                        check_out_at
                    )
                ) as average_seconds'
            )
            ->value('average_seconds');

        $averageSeconds = (int) round(
            (float) ($averageSeconds ?? 0)
        );

        $hours = intdiv(
            $averageSeconds,
            3600
        );

        $minutes = intdiv(
            $averageSeconds % 3600,
            60
        );

        $seconds = $averageSeconds % 60;

        $averageVisitDuration = $hours > 0
            ? sprintf(
                '%dh %02dm %02ds',
                $hours,
                $minutes,
                $seconds
            )
            : sprintf(
                '%dm %02ds',
                $minutes,
                $seconds
            );

        $orderReports = $this->getOrderVisitReports(
            $dateFrom,
            $dateTo
        );

        $uniqueOrderedStores = $orderReports
            ->pluck('visit.odoo_partner_id')
            ->filter()
            ->unique()
            ->count();

        $orderEvents = $orderReports->count();

        $snapshot = $this->loadRetentionSnapshot(
            $odooService
        );

        $totalSnapshotStores =
            $snapshot->count();

        $storeCoverage = $totalSnapshotStores > 0
            ? round(
                ($uniqueOrderedStores / $totalSnapshotStores) * 100,
                1
            )
            : 0;

        $salesPerformance = $this->buildSalesOrderChart(
                $orderReports
            )->values()->all();

        $orderedPartnerIds = $orderReports
            ->pluck('visit.odoo_partner_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $odooPartners = $this->loadOdooPartners(
            $odooService,
            $orderedPartnerIds
        );

        $areaPerformance = $this->buildAreaOrderPerformance(
                $snapshot,
                $orderReports,
                $odooPartners
            )->values()->all();

        $radiusExceptions = VisitReport::query()
            ->with([
                'visit.sales:id,name,username',
                'visit:id,odoo_partner_id,sales_id,visit_date',
            ])
            ->where('is_outside_radius', true)
            ->whereHas(
                'visit',
                function ($query) use (
                    $dateFrom,
                    $dateTo
                ) {
                    $query->whereBetween(
                        'visit_date',
                        [
                            $dateFrom->toDateString(),
                            $dateTo->toDateString(),
                        ]
                    );
                }
            )
            ->latest('location_captured_at')
            ->get();

        $radiusPartnerIds = $radiusExceptions
            ->pluck('visit.odoo_partner_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $radiusPartners = $this->loadOdooPartners(
            $odooService,
            $radiusPartnerIds
        );

        $radiusData = $radiusExceptions
            ->map(
                function (
                    VisitReport $report
                ) use (
                    $radiusPartners
                ) {
                    $visit = $report->visit;

                    $partner = $radiusPartners->get(
                        (int) $visit->odoo_partner_id
                    );

                    return [
                        'date' =>
                            $visit->visit_date
                                ?->format('Y-m-d'),

                        'sales' =>
                            $visit->sales?->name ?? '-',

                        'username' =>
                            $visit->sales?->username ?? '-',

                        'store' =>
                            $partner['name']
                            ?? $partner['display_name']
                            ?? "Partner #{$visit->odoo_partner_id}",

                        'distance' =>
                            $report->distance_from_store,

                        'latitude' =>
                            $report->sales_latitude,

                        'longitude' =>
                            $report->sales_longitude,

                        'captured_at' =>
                            $report->location_captured_at
                                ?->format(
                                    'Y-m-d H:i:s'
                                ),
                    ];
                }
            )
            ->values()
            ->all();

        $filename = sprintf(
            'dashboard-summary-%s-%s.xlsx',
            $dateFrom->format('Ymd'),
            $dateTo->format('Ymd')
        );

        $tempPath = storage_path(
            'app/private/' . $filename
        );

        $export = new DashboardSummaryExport(
            dateFrom: $dateFrom,
            dateTo: $dateTo,
            summary: [
                'total_check_ins' =>
                    $checkIns,

                'active_sales' =>
                    $activeSales,

                'target_check_ins' =>
                    $targetCheckIns,

                'average_visit_duration' =>
                    $averageVisitDuration,

                'ordered_stores' =>
                    $uniqueOrderedStores,

                'order_events' =>
                    $orderEvents,

                'store_coverage' =>
                    $storeCoverage,
            ],
            salesPerformance:
                $salesPerformance,
            areaPerformance:
                $areaPerformance,
            radiusExceptions:
                $radiusData,
        );

        $export->download($tempPath);

        return response()
            ->download($tempPath, $filename)
            ->deleteFileAfterSend(true);
    }
}
