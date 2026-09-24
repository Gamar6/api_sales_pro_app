<?php

namespace App\Exports;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DashboardSummaryExport
{
    public function __construct(
        protected Carbon $dateFrom,
        protected Carbon $dateTo,
        protected array $summary,
        protected array $salesPerformance,
        protected array $areaPerformance,
        protected array $radiusExceptions,
    ) {}

    public function download(string $filename): void
    {
        $spreadsheet = new Spreadsheet();

        $this->buildSummarySheet(
            $spreadsheet->getActiveSheet()
        );

        $this->buildSalesPerformanceSheet(
            $spreadsheet->createSheet()
        );

        $this->buildAreaPerformanceSheet(
            $spreadsheet->createSheet()
        );

        $this->buildRadiusExceptionsSheet(
            $spreadsheet->createSheet()
        );

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);
        $writer->save($filename);

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
    }

    //Summary

    protected function buildSummarySheet($sheet): void
    {
        $sheet->setTitle('Summary');

        $sheet->fromArray([
            ['DASHBOARD SUMMARY'],
            ['Period', $this->formatPeriod()],
            [],
            ['Metric', 'Value'],
            ['Total Check-ins', $this->summary['total_check_ins'] ?? 0],
            ['Active Sales', $this->summary['active_sales'] ?? 0],
            ['Target Check-ins', $this->summary['target_check_ins'] ?? 0],
            [
                'Average Visit Duration',
                $this->summary['average_visit_duration'] ?? '0m 00s',
            ],
            ['Ordered Stores', $this->summary['ordered_stores'] ?? 0],
            ['Order Events', $this->summary['order_events'] ?? 0],
            [
                'Store Coverage',
                ($this->summary['store_coverage'] ?? 0) . '%',
            ],
        ]);

        $this->styleTitle($sheet, 'A1:B1');
        $this->styleHeader($sheet, 'A4:B4');

        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(25);

        $sheet->getStyle('A1:B11')->getAlignment()->setVertical(
            Alignment::VERTICAL_CENTER
        );
    }

    //Sales Performance

    protected function buildSalesPerformanceSheet($sheet): void
    {
        $sheet->setTitle('Sales Performance');

        $headers = [
            'Sales',
            'Unique Ordered Stores',
            'Order Events',
            'Week 1',
            'Week 2',
            'Week 3',
            'Week 4',
            'Week 5',
        ];

        $sheet->fromArray([$headers], null, 'A1');

        $row = 2;

        foreach ($this->salesPerformance as $sales) {
            $weekly = $sales['weekly'] ?? [];

            $sheet->fromArray([
                $sales['name'] ?? '-',
                $sales['orders'] ?? 0,
                $sales['orderEvents'] ?? 0,
                $weekly[1] ?? 0,
                $weekly[2] ?? 0,
                $weekly[3] ?? 0,
                $weekly[4] ?? 0,
                $weekly[5] ?? 0,
            ], null, "A{$row}");

            $row++;
        }

        $this->styleHeader($sheet, 'A1:H1');

        $this->autoSizeColumns(
            $sheet,
            range('A', 'H')
        );

        $this->applyBorders(
            $sheet,
            "A1:H" . max($row - 1, 1)
        );
    }

    //Area Performance

    protected function buildAreaPerformanceSheet($sheet): void
    {
        $sheet->setTitle('Area Performance');

        $headers = [
            'Area',
            'Total Stores',
            'Ordered Stores',
            'Order Events',
            'Coverage %',
            'Sales',
            'Odoo Matched Orders',
        ];

        $sheet->fromArray([$headers], null, 'A1');

        $row = 2;

        foreach ($this->areaPerformance as $area) {
            $sheet->fromArray([
                $area['name'] ?? '-',
                $area['total'] ?? 0,
                $area['unique_orders'] ?? 0,
                $area['order_events'] ?? 0,
                ($area['percentage'] ?? 0) . '%',
                $area['reps'] ?? 0,
                $area['odoo_matched_orders'] ?? 0,
            ], null, "A{$row}");

            $row++;
        }

        $this->styleHeader($sheet, 'A1:G1');

        $this->autoSizeColumns(
            $sheet,
            range('A', 'G')
        );

        $this->applyBorders(
            $sheet,
            "A1:G" . max($row - 1, 1)
        );
    }

    //Radius Exceptions

    protected function buildRadiusExceptionsSheet($sheet): void
    {
        $sheet->setTitle('Radius Exceptions');

        $headers = [
            'Date',
            'Sales',
            'Username',
            'Store',
            'Distance (m)',
            'Latitude',
            'Longitude',
            'Captured At',
        ];

        $sheet->fromArray([$headers], null, 'A1');

        $row = 2;

        foreach ($this->radiusExceptions as $exception) {
            $sheet->fromArray([
                $exception['date'] ?? '-',
                $exception['sales'] ?? '-',
                $exception['username'] ?? '-',
                $exception['store'] ?? '-',
                $exception['distance'] ?? 0,
                $exception['latitude'] ?? null,
                $exception['longitude'] ?? null,
                $exception['captured_at'] ?? '-',
            ], null, "A{$row}");

            $row++;
        }

        $this->styleHeader($sheet, 'A1:H1');

        $this->autoSizeColumns(
            $sheet,
            range('A', 'H')
        );

        $this->applyBorders(
            $sheet,
            "A1:H" . max($row - 1, 1)
        );
    }

    //Styling

    protected function styleTitle($sheet, string $range): void
    {
        $sheet->mergeCells($range);

        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(28);
    }

    protected function styleHeader($sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'E5E7EB',
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);
    }

    protected function applyBorders($sheet, string $range): void
    {
        $sheet->getStyle($range)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
    }

    protected function autoSizeColumns($sheet, array $columns): void
    {
        foreach ($columns as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    //Helpers

    protected function formatPeriod(): string
    {
        if ($this->dateFrom->isSameDay($this->dateTo)) {
            return $this->dateFrom->format('d M Y');
        }

        return sprintf(
            '%s - %s',
            $this->dateFrom->format('d M Y'),
            $this->dateTo->format('d M Y')
        );
    }
}
