<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\StoreVisit;
use App\Models\VisitReport;
use App\Services\OdooService;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Rap2hpoutre\FastExcel\FastExcel;

class DashboardController extends Controller
{
    public function index(
        Request $request,
        OdooService $odooService
    ) {
        /*
        |--------------------------------------------------------------------------
        | Dashboard Filter
        |--------------------------------------------------------------------------
        */

        $selectedMonth = (int) $request->input(
            'month',
            now()->month
        );

        $selectedYear = (int) $request->input(
            'year',
            now()->year
        );

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
        | Visit Reports - Order
        |--------------------------------------------------------------------------
        |
        | Ambil hanya laporan yang memiliki aktivitas "Order"
        | pada bulan/tahun yang sedang dipilih.
        |
        */

        $visitReports = VisitReport::query()
            ->whereJsonContains('activities', 'Order')
            ->whereHas('visit', function ($query) use (
                $selectedMonth,
                $selectedYear
            ) {
                $query
                    ->whereYear(
                        'visit_date',
                        $selectedYear
                    )
                    ->whereMonth(
                        'visit_date',
                        $selectedMonth
                    );
            })
            ->with('visit')
            ->get()
            ->groupBy(
                fn ($report) => $report->visit?->sales_id
            );

        /*
        |--------------------------------------------------------------------------
        | Sales Order Chart
        |--------------------------------------------------------------------------
        */

        $salesOrderChart = User::query()
            ->where('role', 'sales')
            ->get()
            ->map(function ($user) use ($visitReports) {
                $reports = $visitReports->get(
                    $user->id,
                    collect()
                );

                $weeklyData = [
                    'week_1' => 0,
                    'week_2' => 0,
                    'week_3' => 0,
                    'week_4' => 0,
                    'week_5' => 0,
                ];

                foreach ($reports as $report) {
                    if ($report->visit?->visit_date) {
                        $visitDate = Carbon::parse(
                            $report->visit->visit_date
                        );

                        $weekNumber = $visitDate->weekOfMonth;
                        $key = "week_{$weekNumber}";

                        if (isset($weeklyData[$key])) {
                            $weeklyData[$key]++;
                        }
                    }
                }

                return [
                    'salesId' => $user->id,
                    'name'    => $user->name ?? 'Unknown Sales',
                    'orders'  => $reports->count(),
                    'weekly'  => $weeklyData,
                ];
            })
            ->sortByDesc('orders')
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Load Latest Retention Snapshot
        |--------------------------------------------------------------------------
        |
        | Sumber toko berasal dari file Excel hasil Python.
        | Tidak mengubah atau menulis ulang logic Python.
        |
        */

        $folderPath = storage_path(
            'app/private/retensi'
        );

        $files = File::glob(
            "{$folderPath}/*_retensi_jabodetabek.xlsx"
        );

        $stores = collect();

        if (!empty($files)) {
            rsort($files);

            $latestFile = $files[0];

            $cacheKey = 'dashboard:retention:stores:' . hash(
                'sha256',
                basename($latestFile) . ':' .
                File::lastModified($latestFile)
            );

            $retentionData = Cache::remember(
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
                    | Partner IDs dari Excel
                    |--------------------------------------------------------------------------
                    */

                    $partnerIds = $excelData
                        ->pluck('partner_id')
                        ->filter()
                        ->map(fn ($id) => (int) $id)
                        ->unique()
                        ->values()
                        ->toArray();

                    /*
                    |--------------------------------------------------------------------------
                    | Lengkapi data dari Odoo
                    |--------------------------------------------------------------------------
                    */

                    $odooStoresRaw = $odooService->execute_kw(
                        'res.partner',
                        'search_read',
                        [
                            [
                                [
                                    'id',
                                    'in',
                                    $partnerIds
                                ]
                            ]
                        ],
                        [
                            'fields' => [
                                'id',
                                'street',
                                'partner_latitude',
                                'partner_longitude',
                                'email',
                            ]
                        ]
                    );

                    $odooStores = collect(
                        $odooStoresRaw
                    )->keyBy('id');

                    /*
                    |--------------------------------------------------------------------------
                    | Normalize Snapshot
                    |--------------------------------------------------------------------------
                    */

                    return $excelData
                        ->map(function (array $row) use (
                            $odooStores
                        ) {
                            $partnerId = (int) (
                                $row['partner_id'] ?? 0
                            );

                            $storeDetail = $odooStores->get(
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
                                        $row['last_order_date']
                                            ->format('Y-m-d');
                                } else {
                                    $lastOrderDate = substr(
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

                                'retensi_status' =>
                                    (string) (
                                        $row[
                                            'retensi_status'
                                        ] ?? 'DEAD ZONE'
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
                                        ? (float) $storeDetail[
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
                                        ? (float) $storeDetail[
                                            'partner_longitude'
                                        ]
                                        : null,

                                'email' =>
                                    $storeDetail['email']
                                    ?? null,
                            ];
                        })
                        ->values()
                        ->all();
                }
            );

            $stores = collect($retentionData)
                ->filter(
                    fn ($store) =>
                        !empty($store['partner_id'])
                        &&
                        !empty($store['kota'])
                )
                ->unique('partner_id')
                ->values();
        }

        /*
        |--------------------------------------------------------------------------
        | Order Visits
        |--------------------------------------------------------------------------
        |
        | Sekarang ambil seluruh StoreVisit yang mempunyai
        | VisitReport dengan aktivitas "Order".
        |
        */

        $orderVisitReports = VisitReport::query()
            ->whereJsonContains(
                'activities',
                'Order'
            )
            ->whereHas('visit', function ($query) use (
                $selectedMonth,
                $selectedYear
            ) {
                $query
                    ->whereYear(
                        'visit_date',
                        $selectedYear
                    )
                    ->whereMonth(
                        'visit_date',
                        $selectedMonth
                    );
            })
            ->with([
                'visit' => function ($query) {
                    $query->select([
                        'id',
                        'odoo_partner_id',
                        'sales_id',
                        'visit_date',
                    ]);
                },
            ])
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Order Visits Indexed By Partner ID
        |--------------------------------------------------------------------------
        |
        | Satu toko bisa memiliki lebih dari satu VisitReport.
        | Kita deduplicate berdasarkan odoo_partner_id.
        |
        */

        $orderVisitsByPartner = $orderVisitReports
            ->filter(
                fn ($report) =>
                    $report->visit?->odoo_partner_id
            )
            ->groupBy(
                fn ($report) =>
                    $report->visit->odoo_partner_id
            );

        /*
        |--------------------------------------------------------------------------
        | Area / City Order Performance
        |--------------------------------------------------------------------------
        |
        | Denominator:
        |   Semua toko yang ada di kota tersebut.
        |
        | Numerator:
        |   Toko unik yang memiliki aktivitas "Order"
        |   pada periode yang dipilih.
        |
        | Reps:
        |   Sales unik yang melakukan Order di kota tersebut.
        |
        */

        $areaOrderPerformance = $stores
            ->groupBy('kota')
            ->map(function ($cityStores, $city) use (
                $orderVisitsByPartner
            ) {
                /*
                |--------------------------------------------------------------------------
                | Semua toko di kota
                |--------------------------------------------------------------------------
                */

                $allPartnerIds = $cityStores
                    ->pluck('partner_id')
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();

                $totalStores = $allPartnerIds->count();

                /*
                |--------------------------------------------------------------------------
                | Toko yang melakukan Order
                |--------------------------------------------------------------------------
                */

                $visitedPartnerIds = $allPartnerIds
                    ->filter(
                        fn ($partnerId) =>
                            $orderVisitsByPartner->has(
                                $partnerId
                            )
                    )
                    ->values();

                $visitedStores = $visitedPartnerIds->count();

                /*
                |--------------------------------------------------------------------------
                | Sales / Reps unik
                |--------------------------------------------------------------------------
                */

                $salesIds = $visitedPartnerIds
                    ->flatMap(
                        fn ($partnerId) =>
                            $orderVisitsByPartner
                                ->get($partnerId, collect())
                                ->pluck(
                                    'visit.sales_id'
                                )
                        )
                    ->filter()
                    ->unique()
                    ->values();

                $reps = $salesIds->count();

                /*
                |--------------------------------------------------------------------------
                | Completion Percentage
                |--------------------------------------------------------------------------
                */

                $percentage = $totalStores > 0
                    ? round(
                        (
                            $visitedStores
                            / $totalStores
                        ) * 100
                    )
                    : 0;

                return [
                    'name'       => $city,
                    'reps'       => $reps,
                    'visited'    => $visitedStores,
                    'total'      => $totalStores,
                    'percentage' => $percentage,
                ];
            })
            ->sortByDesc('visited')
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Inertia Response
        |--------------------------------------------------------------------------
        */

        return Inertia::render('Dashboard/Main_dashboard', [
            'dashboardStats' => [
                'totalCheckInsToday' => $totalCheckInsToday,
                'averageVisitDuration' => $averageVisitDuration,
            ],

            'salesOrderChart' => $salesOrderChart,

            'stores' => $areaOrderPerformance,

            'filters' => [
                'month' => $selectedMonth,
                'year' => $selectedYear,
            ],
        ]);
    }
}
