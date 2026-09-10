<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

        $salesOrderChart = VisitReport::query()
            ->whereJsonContains('activities', 'Cek')
            ->whereHas('visit', function ($query) use (
                $selectedMonth,
                $selectedYear
            ) {
                $query
                    ->whereYear('visit_date', $selectedYear)
                    ->whereMonth('visit_date', $selectedMonth);
            })
            ->with([
                'visit.sales:id,name',
            ])
            ->get()
            ->groupBy(function ($report) {
                return $report->visit?->sales_id;
            })
            ->map(function ($reports) {

                $firstReport = $reports->first();

                return [
                    'salesId' => $firstReport->visit?->sales_id,

                    'name' => $firstReport->visit?->sales?->name
                        ?? 'Unknown Sales',

                    'orders' => $reports->count(),
                ];
            })
            ->filter(fn ($sales) => $sales['salesId'] !== null)
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
