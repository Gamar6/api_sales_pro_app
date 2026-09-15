<?php

namespace App\Http\Controllers\Admin;

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

class DashboardController extends Controller
{
    /**
     * Dashboard utama.
     */
    public function index(
        Request $request,
        OdooService $odooService
    ) {
        /*
        |--------------------------------------------------------------------------
        | Dashboard Filter
        |--------------------------------------------------------------------------
        |
        | Default:
        |   All Time
        |
        | Optional:
        |   ?date_from=2026-09-01&date_to=2026-09-15
        |
        */

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $dateFrom = $dateFrom
            ? Carbon::parse($dateFrom)->startOfDay()
            : null;

        $dateTo = $dateTo
            ? Carbon::parse($dateTo)->endOfDay()
            : null;

        /*
        |--------------------------------------------------------------------------
        | Today's Check In
        |--------------------------------------------------------------------------
        */

        $totalCheckInsToday = StoreVisit::checkedInToday()->count();

        /*
        |--------------------------------------------------------------------------
        | Average Visit Duration
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Latest Retention Snapshot
        |--------------------------------------------------------------------------
        |
        | Snapshot Excel adalah MASTER POPULATION toko.
        |
        | Artinya:
        |
        |   totalStores
        |       =
        |   seluruh toko yang ada di snapshot
        |
        | Kota juga mengikuti snapshot Excel.
        |
        */

        $stores = $this->loadRetentionSnapshot(
            $odooService
        );

        /*
        |--------------------------------------------------------------------------
        | Order Visit Reports
        |--------------------------------------------------------------------------
        |
        | Rule:
        |
        |   visit_reports.activities contains "Order"
        |
        | Satu store bisa mempunyai beberapa Order.
        |
        | Untuk metric utama:
        |
        |   unique store = dihitung 1 kali
        |
        | Untuk metric sekunder:
        |
        |   order_events = jumlah event Order sebenarnya
        |
        */

        $orderVisitReports = $this->getOrderVisitReports(
            $dateFrom,
            $dateTo
        );

        /*
        |--------------------------------------------------------------------------
        | Odoo Partner Data
        |--------------------------------------------------------------------------
        |
        | Ambil partner Odoo berdasarkan partner yang ditemukan
        | dari StoreVisit.
        |
        | Odoo digunakan sebagai sumber data partner/enrichment.
        |
        | Kota untuk performance area tetap menggunakan snapshot
        | karena snapshot adalah master population.
        |
        */

        $orderedPartnerIds = $orderVisitReports
            ->map(fn ($report) => $report->visit?->odoo_partner_id)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $odooPartners = $this->loadOdooPartners(
            $odooService,
            $orderedPartnerIds->all()
        );

        /*
        |--------------------------------------------------------------------------
        | Sales Order Performance
        |--------------------------------------------------------------------------
        */

        $salesOrderChart = $this->buildSalesOrderChart(
            $orderVisitReports
        );

        /*
        |--------------------------------------------------------------------------
        | Area / City Order Performance
        |--------------------------------------------------------------------------
        |
        | Denominator:
        |   SEMUA toko di snapshot Excel terbaru.
        |
        | Numerator:
        |   UNIQUE toko yang melakukan Order.
        |
        | Secondary:
        |   jumlah raw order event.
        |
        | Reps:
        |   UNIQUE sales yang melakukan Order.
        |
        */

        $areaOrderPerformance = $this->buildAreaOrderPerformance(
            $stores,
            $orderVisitReports,
            $odooPartners
        );

        /*
        |--------------------------------------------------------------------------
        | Dashboard Response
        |--------------------------------------------------------------------------
        */

        return Inertia::render(
            'Dashboard/Main_dashboard',
            [
                'dashboardStats' => [
                    'totalCheckInsToday' => $totalCheckInsToday,
                    'averageVisitDuration' => $averageVisitDuration,

                    /*
                    |--------------------------------------------------------------------------
                    | Global Order Summary
                    |--------------------------------------------------------------------------
                    */

                    'uniqueOrderStores' => $orderVisitReports
                        ->map(
                            fn ($report) =>
                                $report->visit?->odoo_partner_id
                        )
                        ->filter()
                        ->unique()
                        ->count(),

                    'orderEvents' => $orderVisitReports->count(),

                    'totalSnapshotStores' => $stores->count(),
                ],

                'salesOrderChart' => $salesOrderChart,

                /*
                |--------------------------------------------------------------------------
                | Area Performance
                |--------------------------------------------------------------------------
                */

                'stores' => $areaOrderPerformance,

                /*
                |--------------------------------------------------------------------------
                | Filters
                |--------------------------------------------------------------------------
                |
                | Tetap kirim month/year untuk kompatibilitas
                | frontend lama, tetapi filter utama sekarang
                | date_from/date_to.
                |
                */

                'filters' => [
                    'date_from' => $dateFrom?->format('Y-m-d'),
                    'date_to' => $dateTo?->format('Y-m-d'),

                    'month' => $dateFrom
                        ? $dateFrom->month
                        : null,

                    'year' => $dateFrom
                        ? $dateFrom->year
                        : null,
                ],
            ]
        );
    }

    /**
     * =========================================================================
     * LOAD RETENTION SNAPSHOT
     * =========================================================================
     *
     * Snapshot Excel terbaru adalah master population.
     */
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

        /*
        |--------------------------------------------------------------------------
        | Ambil file terbaru berdasarkan modified time
        |--------------------------------------------------------------------------
        */

        usort(
            $files,
            function ($a, $b) {
                return File::lastModified($b)
                    <=> File::lastModified($a);
            }
        );

        $latestFile = $files[0];

        /*
        |--------------------------------------------------------------------------
        | Cache Snapshot
        |--------------------------------------------------------------------------
        */

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

                    /*
                    |--------------------------------------------------------------------------
                    | Partner IDs dari snapshot
                    |--------------------------------------------------------------------------
                    */

                    $partnerIds = $excelData
                        ->pluck('partner_id')
                        ->filter()
                        ->map(
                            fn ($id) => (int) $id
                        )
                        ->unique()
                        ->values()
                        ->toArray();

                    /*
                    |--------------------------------------------------------------------------
                    | Odoo Partner
                    |--------------------------------------------------------------------------
                    |
                    | Batch request agar tidak melakukan query
                    | satu partner satu request.
                    |
                    */

                    $odooStores = collect();

                    foreach (
                        array_chunk($partnerIds, 500)
                        as $partnerChunk
                    ) {
                        $odooStoresRaw = $odooService
                            ->execute_kw(
                                'res.partner',
                                'search_read',
                                [
                                    [
                                        [
                                            'id',
                                            'in',
                                            $partnerChunk
                                        ]
                                    ]
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
                                    ]
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

                    /*
                    |--------------------------------------------------------------------------
                    | Normalize Snapshot
                    |--------------------------------------------------------------------------
                    */

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

                                /*
                                |--------------------------------------------------------------------------
                                | Normalize last order date
                                |--------------------------------------------------------------------------
                                */

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
                                            ]->format(
                                                'Y-m-d'
                                            );
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
                                            $row[
                                                'partner_name'
                                            ] ?? ''
                                        ),

                                    /*
                                    |--------------------------------------------------------------------------
                                    | KOTA MASTER
                                    |--------------------------------------------------------------------------
                                    */

                                    'kota' =>
                                        trim(
                                            (string) (
                                                $row['kota']
                                                ?? ''
                                            )
                                        ),

                                    'sales_name' =>
                                        (string) (
                                            $row[
                                                'sales_name'
                                            ] ?? 'Unassigned'
                                        ),

                                    'phone' =>
                                        (string) (
                                            $row[
                                                'phone_clean'
                                            ] ?? ''
                                        ),

                                    'last_order_date' =>
                                        $lastOrderDate,

                                    'days_since' =>
                                        (int) (
                                            $row[
                                                'days_since'
                                            ] ?? 0
                                        ),

                                    'weeks_since' =>
                                        (int) (
                                            $row[
                                                'weeks_since'
                                            ] ?? 0
                                        ),

                                    'retensi_status' =>
                                        (string) (
                                            $row[
                                                'retensi_status'
                                            ] ?? 'DEAD ZONE'
                                        ),

                                    'aktif_group' =>
                                        (string) (
                                            $row[
                                                'aktif_group'
                                            ] ?? 'NON_AKTIF'
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
                                            $row[
                                                'priority'
                                            ] ?? 99
                                        ),

                                    /*
                                    |--------------------------------------------------------------------------
                                    | Odoo enrichment
                                    |--------------------------------------------------------------------------
                                    */

                                    'alamat' =>
                                        $storeDetail[
                                            'street'
                                        ] ?? null,

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
                                        $storeDetail[
                                            'email'
                                        ] ?? null,
                                ];
                            }
                        )
                        ->filter()
                        ->values()
                        ->all();
                }
            )
        )
            /*
            |--------------------------------------------------------------------------
            | Snapshot validation
            |--------------------------------------------------------------------------
            */

            ->filter(
                fn ($store) =>
                    !empty($store['partner_id'])
                    &&
                    !empty($store['kota'])
            )

            /*
            |--------------------------------------------------------------------------
            | Satu partner hanya satu kali
            |--------------------------------------------------------------------------
            */

            ->unique('partner_id')
            ->values();
    }

    /**
     * =========================================================================
     * GET ORDER VISIT REPORTS
     * =========================================================================
     */
    private function getOrderVisitReports(
        ?Carbon $dateFrom,
        ?Carbon $dateTo
    ): Collection {
        $query = VisitReport::query()
            /*
            |--------------------------------------------------------------------------
            | activities adalah JSON array
            |--------------------------------------------------------------------------
            |
            | Contoh:
            |
            | ["Visit", "Order", "Payment"]
            |
            | Selama mengandung "Order", report dihitung.
            |
            */
            ->whereJsonContains(
                'activities',
                'Order'
            )

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

            ->with(
                [
                    'visit' => function ($query) {
                        $query->select(
                            [
                                'id',
                                'odoo_partner_id',
                                'sales_id',
                                'visit_date',
                            ]
                        );
                    },
                ]
            );

        return $query
            ->get()
            ->filter(
                fn ($report) =>
                    $report->visit
                    &&
                    !empty(
                        $report->visit->odoo_partner_id
                    )
            )
            ->values();
    }

    /**
     * =========================================================================
     * LOAD ODOO PARTNERS
     * =========================================================================
     *
     * Odoo tetap diakses menggunakan OdooService XML-RPC.
     */
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
                            $partnerChunk
                        ]
                    ]
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

    /**
     * =========================================================================
     * SALES ORDER CHART
     * =========================================================================
     *
     * Metric utama:
     *   orders = unique stores
     *
     * Metric sekunder:
     *   orderEvents = raw order events
     */
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

                    /*
                    |--------------------------------------------------------------------------
                    | Unique stores
                    |--------------------------------------------------------------------------
                    */

                    $uniqueStoreIds = $reports
                        ->map(
                            fn ($report) =>
                                $report->visit
                                    ?->odoo_partner_id
                        )
                        ->filter()
                        ->map(
                            fn ($id) => (int) $id
                        )
                        ->unique()
                        ->values();

                    /*
                    |--------------------------------------------------------------------------
                    | Weekly data
                    |--------------------------------------------------------------------------
                    |
                    | Weekly menggunakan unique store per minggu.
                    |
                    | Jadi:
                    |
                    | Store A order minggu 1
                    | Store A order minggu 2
                    |
                    | akan dihitung di masing-masing minggu.
                    |
                    */

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

                        if (
                            !isset(
                                $weeklyStoreIds[$key]
                            )
                        ) {
                            continue;
                        }

                        $partnerId = $visit->odoo_partner_id;

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
                        'salesId' =>
                            $user->id,

                        'name' =>
                            $user->name
                            ?? 'Unknown Sales',

                        /*
                        |--------------------------------------------------------------------------
                        | MAIN METRIC
                        |--------------------------------------------------------------------------
                        */

                        'orders' =>
                            $uniqueStoreIds->count(),

                        /*
                        |--------------------------------------------------------------------------
                        | SECONDARY METRIC
                        |--------------------------------------------------------------------------
                        */

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

    /**
     * =========================================================================
     * AREA ORDER PERFORMANCE
     * =========================================================================
     */
    private function buildAreaOrderPerformance(
        Collection $stores,
        Collection $orderVisitReports,
        Collection $odooPartners
    ): Collection {
        /*
        |--------------------------------------------------------------------------
        | Snapshot menjadi master partner → kota
        |--------------------------------------------------------------------------
        */

        $snapshotByPartner = $stores
            ->keyBy(
                fn ($store) =>
                    (int) $store['partner_id']
            );

        /*
        |--------------------------------------------------------------------------
        | Order events dikelompokkan berdasarkan partner
        |--------------------------------------------------------------------------
        */

        $orderVisitsByPartner = $orderVisitReports
            ->filter(
                fn ($report) =>
                    $report->visit
                    &&
                    !empty(
                        $report->visit->odoo_partner_id
                    )
            )
            ->groupBy(
                fn ($report) =>
                    (int) $report->visit->odoo_partner_id
            );

        /*
        |--------------------------------------------------------------------------
        | Hanya partner yang:
        |
        | 1. Ada di snapshot
        | 2. Memiliki Order
        |
        | yang masuk numerator.
        |--------------------------------------------------------------------------
        */

        $orderedSnapshotPartners = $orderVisitsByPartner
            ->filter(
                fn ($reports, $partnerId) =>
                    $snapshotByPartner->has(
                        (int) $partnerId
                    )
            );

        /*
        |--------------------------------------------------------------------------
        | Group semua snapshot stores berdasarkan kota
        |--------------------------------------------------------------------------
        */

        return $stores
            ->groupBy(
                fn ($store) =>
                    trim(
                        (string) (
                            $store['kota']
                            ?? ''
                        )
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
                    /*
                    |--------------------------------------------------------------------------
                    | ALL STORES
                    |--------------------------------------------------------------------------
                    |
                    | Denominator.
                    |--------------------------------------------------------------------------
                    */

                    $allPartnerIds = $cityStores
                        ->pluck('partner_id')
                        ->map(
                            fn ($id) => (int) $id
                        )
                        ->unique()
                        ->values();

                    $totalStores =
                        $allPartnerIds->count();

                    /*
                    |--------------------------------------------------------------------------
                    | UNIQUE ORDERED STORES
                    |--------------------------------------------------------------------------
                    |
                    | Satu store order 5 kali:
                    |
                    | tetap dihitung 1.
                    |--------------------------------------------------------------------------
                    */

                    $orderedPartnerIds = $allPartnerIds
                        ->filter(
                            fn ($partnerId) =>
                                $orderedSnapshotPartners
                                    ->has(
                                        $partnerId
                                    )
                        )
                        ->values();

                    $uniqueOrders =
                        $orderedPartnerIds->count();

                    /*
                    |--------------------------------------------------------------------------
                    | RAW ORDER EVENTS
                    |--------------------------------------------------------------------------
                    |
                    | Kalau Store A order 5 kali:
                    |
                    | order_events = 5
                    |--------------------------------------------------------------------------
                    */

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

                    /*
                    |--------------------------------------------------------------------------
                    | UNIQUE SALES / REPS
                    |--------------------------------------------------------------------------
                    */

                    $salesIds = $orderedPartnerIds
                        ->flatMap(
                            function ($partnerId)
                                use (
                                    $orderVisitsByPartner
                                ) {
                                    return
                                        $orderVisitsByPartner
                                            ->get(
                                                $partnerId,
                                                collect()
                                            )
                                            ->map(
                                                fn ($report) =>
                                                    $report
                                                        ->visit
                                                        ?->sales_id
                                            );
                                }
                        )
                        ->filter()
                        ->map(
                            fn ($id) => (int) $id
                        )
                        ->unique()
                        ->values();

                    $reps = $salesIds->count();

                    /*
                    |--------------------------------------------------------------------------
                    | PERFORMANCE
                    |--------------------------------------------------------------------------
                    */

                    $percentage =
                        $totalStores > 0
                            ? round(
                                (
                                    $uniqueOrders
                                    / $totalStores
                                ) * 100
                            )
                            : 0;

                    /*
                    |--------------------------------------------------------------------------
                    | Odoo partner count
                    |--------------------------------------------------------------------------
                    |
                    | Ini hanya enrichment/validasi.
                    | Tidak menentukan denominator.
                    |--------------------------------------------------------------------------
                    */

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

                        /*
                        |--------------------------------------------------------------------------
                        | Unique sales
                        |--------------------------------------------------------------------------
                        */

                        'reps' =>
                            $reps,

                        /*
                        |--------------------------------------------------------------------------
                        | MAIN METRIC
                        |--------------------------------------------------------------------------
                        */

                        'visited' =>
                            $uniqueOrders,

                        'unique_orders' =>
                            $uniqueOrders,

                        /*
                        |--------------------------------------------------------------------------
                        | SECONDARY METRIC
                        |--------------------------------------------------------------------------
                        */

                        'order_events' =>
                            $orderEvents,

                        /*
                        |--------------------------------------------------------------------------
                        | ALL STORES IN SNAPSHOT
                        |--------------------------------------------------------------------------
                        */

                        'total' =>
                            $totalStores,

                        /*
                        |--------------------------------------------------------------------------
                        | PERFORMANCE %
                        |--------------------------------------------------------------------------
                        */

                        'percentage' =>
                            $percentage,

                        /*
                        |--------------------------------------------------------------------------
                        | Odoo enrichment info
                        |--------------------------------------------------------------------------
                        */

                        'odoo_matched_orders' =>
                            $odooMatchedOrders,
                    ];
                }
            )
            ->sort(
                function ($a, $b) {
                    /*
                    |--------------------------------------------------------------------------
                    | Primary sort:
                    | unique stores ordered
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $a['visited']
                        !==
                        $b['visited']
                    ) {
                        return $b['visited']
                            <=>
                            $a['visited'];
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Secondary sort:
                    | percentage
                    |--------------------------------------------------------------------------
                    */

                    return $b['percentage']
                        <=>
                        $a['percentage'];
                }
            )
            ->values();
    }
}
