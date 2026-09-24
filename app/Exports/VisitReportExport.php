<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class VisitReportExport
{
    public function __construct(
        protected array $visits,
    ) {}

    public function download(string $filename): void
    {
        $spreadsheet = new Spreadsheet();

        $this->buildVisitReportsSheet(
            $spreadsheet->getActiveSheet()
        );

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);
        $writer->save($filename);

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
    }

    protected function buildVisitReportsSheet($sheet): void
    {
        $sheet->setTitle('Visit Reports');

        $headers = [
            'Date',
            'Sales',
            'Username',
            'Store',
            'Status',
            'Check In',
            'Check Out',
            'Duration',
            'Activities',
            'PIC',
            'Stock %',
            'Stock (pcs)',
            'Distance (m)',
            'Geofence',
            'Latitude',
            'Longitude',
            'Location Captured At',
            'Notes',
        ];

        $sheet->fromArray([$headers], null, 'A1');

        $row = 2;

        foreach ($this->visits as $visit) {
            $sheet->fromArray([
                $visit['date'] ?? '-',
                $visit['sales'] ?? '-',
                $visit['username'] ?? '-',
                $visit['store'] ?? '-',
                $visit['status'] ?? '-',
                $visit['check_in'] ?? '-',
                $visit['check_out'] ?? '-',
                $visit['duration'] ?? '-',
                $visit['activities'] ?? '-',
                $visit['pic_name'] ?? '-',
                $visit['stock_percentage'] ?? 0,
                $visit['stock_pcs'] ?? 0,
                $visit['distance'] ?? 0,
                $visit['geofence'] ?? '-',
                $visit['latitude'] ?? null,
                $visit['longitude'] ?? null,
                $visit['location_captured_at'] ?? '-',
                $visit['notes'] ?? '-',
            ], null, "A{$row}");

            $row++;
        }

        $lastRow = max($row - 1, 1);

        $this->styleHeader(
            $sheet,
            'A1:R1'
        );

        $this->applyBorders(
            $sheet,
            "A1:R{$lastRow}"
        );

        $this->autoSizeColumns(
            $sheet,
            range('A', 'R')
        );

        $sheet->freezePane('A2');

        $sheet->getStyle("A1:R{$lastRow}")
            ->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getStyle("I2:I{$lastRow}")
            ->getAlignment()
            ->setWrapText(true);

        $sheet->getStyle("R2:R{$lastRow}")
            ->getAlignment()
            ->setWrapText(true);
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
        $sheet->getStyle($range)
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(
                Border::BORDER_THIN
            );
    }

    protected function autoSizeColumns($sheet, array $columns): void
    {
        foreach ($columns as $column) {
            $sheet->getColumnDimension($column)
                ->setAutoSize(true);
        }
    }
}
