<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Rap2hpoutre\FastExcel\FastExcel;

class ReadRetentionExcelController extends Controller
{
    public function getRetentionStores(Request $request)
    {
        $folderPath = storage_path('app/private/retensi');
        $files = File::glob("{$folderPath}/*_retensi_jabodetabek.xlsx");

        if (empty($files)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'File snapshot retensi tidak ditemukan.'
            ], 404);
        }

        rsort($files);
        $latestFile = $files[0];

        $rawRows = (new FastExcel)->import($latestFile);

        $processedData = $rawRows->map(function (array $row) {
            return [
                'partner_id'        => (int) ($row['partner_id'] ?? 0),
                'partner_name'      => (string) ($row['partner_name'] ?? ''),
                'kota'              => (string) ($row['kota'] ?? ''),
                'delivery_route'    => (string) ($row['delivery_route'] ?? ''),
                'sales_name'        => (string) ($row['sales_name'] ?? 'Unassigned'),
                'phone'             => (string) ($row['phone_clean'] ?? ''),
                'last_order_date'   => isset($row['last_order_date']) ? substr((string)$row['last_order_date'], 0, 10) : null,
                'days_since'        => (int) ($row['days_since'] ?? 0),
                'weeks_since'       => (int) ($row['weeks_since'] ?? 0),
                'retensi_status'    => (string) ($row['retensi_status'] ?? 'DEAD ZONE'),
                'aktif_group'       => (string) ($row['aktif_group'] ?? 'NON_AKTIF'),
                'avg_retensi_weeks' => (float) ($row['avg_retensi_weeks'] ?? 0),
                'gap_vs_average'    => (float) ($row['gap_vs_average'] ?? 0),
                'total_sales'       => (float) ($row['total_sales_2024_plus'] ?? 0),
                'priority'          => (int) ($row['priority'] ?? 99),
            ];
        });

        if ($request->has('status')) {
            $statusFilter = strtoupper($request->query('status'));
            $processedData = $processedData->where('retensi_status', $statusFilter)->values();
        }

        if ($request->has('sales_id')) {
            $salesFilter = (int) $request->query('sales_id');
            $processedData = $processedData->where('sales_id', $salesFilter)->values();
        }

        return response()->json([
            'status'     => 'success',
            'file_used'  => basename($latestFile),
            'total_rows' => $processedData->count(),
            'data'       => $processedData
        ]);
    }
}