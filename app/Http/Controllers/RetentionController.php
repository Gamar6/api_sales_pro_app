<?php

namespace App\Http\Controllers;

use App\Services\OdooService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Rap2hpoutre\FastExcel\FastExcel;
use DateTimeInterface;

class RetentionController extends Controller
{
    public function getRetentionStores(Request $request, OdooService $odooService)
    {
        // 1. Ambil file Excel retensi terbaru
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

        // 2. Baca file Excel
        $excelData = (new FastExcel)->import($latestFile);

        if ($excelData->isEmpty()) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        // 3. Kumpulkan semua partner_id unik dari Excel (dipastikan bertipe integer)
        $partnerIds = $excelData->pluck('partner_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();

        // 4. Ambil data lokasi & kontak dari Odoo via XML-RPC
        $odooStoresRaw = $odooService->execute_kw('res.partner', 'search_read', [
            [['id', 'in', $partnerIds]]
        ], [
            'fields' => ['id', 'street', 'partner_latitude', 'partner_longitude', 'email']
        ]);

        // Key-by 'id' agar pencarian O(1) di dalam loop
        $odooStores = collect($odooStoresRaw)->keyBy('id');

        // 5. Gabungkan Data Excel + Data XML-RPC Odoo
        $processedData = $excelData->map(function (array $row) use ($odooStores) {
            $partnerId = (int) ($row['partner_id'] ?? 0);
            
            // $storeDetail berupa Array hasil decode XML-RPC
            $storeDetail = $odooStores->get($partnerId);

            // Handle format tanggal jika berupa object DateTimeImmutable dari FastExcel
            $lastOrderDate = null;
            if (!empty($row['last_order_date'])) {
                if ($row['last_order_date'] instanceof DateTimeInterface) {
                    $lastOrderDate = $row['last_order_date']->format('Y-m-d');
                } else {
                    $lastOrderDate = substr((string) $row['last_order_date'], 0, 10);
                }
            }

            return [
                // --- Data Metrik Retensi (dari Excel Python) ---
                'partner_id'        => $partnerId,
                'partner_name'      => (string) ($row['partner_name'] ?? ''),
                'kota'              => (string) ($row['kota'] ?? ''),
                'sales_name'        => (string) ($row['sales_name'] ?? 'Unassigned'),
                'phone'             => (string) ($row['phone_clean'] ?? ''),
                'last_order_date'   => $lastOrderDate,
                'days_since'        => (int) ($row['days_since'] ?? 0),
                'weeks_since'       => (int) ($row['weeks_since'] ?? 0),
                'retensi_status'    => (string) ($row['retensi_status'] ?? 'DEAD ZONE'),
                'aktif_group'       => (string) ($row['aktif_group'] ?? 'NON_AKTIF'),
                'avg_retensi_weeks' => (float) ($row['avg_retensi_weeks'] ?? 0),
                'gap_vs_average'    => (float) ($row['gap_vs_average'] ?? 0),
                'total_sales'       => (float) ($row['total_sales_2024_plus'] ?? 0),
                'priority'          => (int) ($row['priority'] ?? 99),

                // --- Data Lokasi & Kontak (dari XML-RPC Odoo) ---
                'alamat'            => $storeDetail['street'] ?? null,
                'latitude'          => isset($storeDetail['partner_latitude']) && $storeDetail['partner_latitude'] !== false
                                        ? (float) $storeDetail['partner_latitude'] 
                                        : null,
                'longitude'         => isset($storeDetail['partner_longitude']) && $storeDetail['partner_longitude'] !== false
                                        ? (float) $storeDetail['partner_longitude'] 
                                        : null,
                'email'             => $storeDetail['email'] ?? null,
            ];
        });

        return response()->json([
            'status'     => 'success',
            'file_used'  => basename($latestFile),
            'total_rows' => $processedData->count(),
            'data'       => $processedData->values()
        ]);
    }
}