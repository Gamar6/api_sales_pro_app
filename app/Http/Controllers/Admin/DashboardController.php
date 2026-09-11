<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Models\User;
use App\Models\StoreVisit;
use App\Models\VisitReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {

        $selectedMonth = (int) $request->input(
            'month',
            now()->month
        );

        $selectedYear = (int) $request->input(
            'year',
            now()->year
        );


        $totalCheckInsToday = StoreVisit::checkedInToday()->count();


        $averageVisitDurationSeconds = StoreVisit::query()
            ->whereNotNull('check_in_at')
            ->whereNotNull('check_out_at')
            ->whereDate('check_in_at', today())
            ->average(
                DB::raw(
                    'TIMESTAMPDIFF(SECOND, check_in_at, check_out_at)'
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

        $visitReports = VisitReport::query()
        ->whereJsonContains('activities', 'Pemasangan Stiker')
        ->whereHas('visit', function ($query) use ($selectedMonth, $selectedYear) {
            $query->whereYear('visit_date', $selectedYear)
                ->whereMonth('visit_date', $selectedMonth);
        })
        ->with('visit')
        ->get()
        ->groupBy(fn ($report) => $report->visit?->sales_id);

        // 2. Query dari Model User agar semua sales tetap muncul
        $salesOrderChart = User::query() 
            ->where('role', 'sales')

            ->get()
            ->map(function ($user) use ($visitReports) {
                // Ambil laporan milik sales ini (jika tidak ada, berikan collection kosong)
                $reports = $visitReports->get($user->id, collect());

                // Inisialisasi template mingguan (Minggu 1 - 5)
                $weeklyData = [
                    'week_1' => 0,
                    'week_2' => 0,
                    'week_3' => 0,
                    'week_4' => 0,
                    'week_5' => 0,
                ];

                // Kelompokkan data ke masing-masing minggu
                foreach ($reports as $report) {
                    if ($report->visit?->visit_date) {
                        $visitDate = Carbon::parse($report->visit->visit_date);
                        
                        // Menentukan minggu ke-berapa dalam bulan tersebut (1 - 5)
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

        return Inertia::render(
            'Dashboard/Main_dashboard',
            [
                'dashboardStats' => [
                    'totalCheckInsToday' => $totalCheckInsToday,

                    'averageVisitDuration' =>
                        $averageVisitDuration,
                ],

                'salesOrderChart' => $salesOrderChart,

                'filters' => [
                    'month' => $selectedMonth,

                    'year' => $selectedYear,
                ],
            ]
        );
    }
}
