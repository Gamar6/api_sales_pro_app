<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OdooService;
use App\Models\StoreModel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Rap2hpoutre\FastExcel\FastExcel;
use DateTimeInterface;

class SyncOdooStoresCommand extends Command
{
    protected $signature = 'odoo:sync-stores';
    protected $description = 'Sinkronisasi data retensi dari Excel (Python) + Koordinat Odoo ke MySQL';

    public function handle(OdooService $odooService)
    {
        $this->info('1. Membaca file Excel retensi hasil Python...');

        $folderPath = storage_path('app/private/retensi');
        $files = File::glob("{$folderPath}/*_retensi_jabodetabek.xlsx");

        if (empty($files)) {
            $this->error('File snapshot retensi tidak ditemukan.');
            return Command::FAILURE;
        }

        rsort($files);
        $latestFile = $files[0];
        $excelData = (new FastExcel)->import($latestFile);

        if ($excelData->isEmpty()) {
            $this->warn('File Excel kosong.');
            return Command::SUCCESS;
        }

        $uniqueExcelStores = $excelData->filter(fn($row) => !empty($row['partner_id']))
            ->keyBy('partner_id');

        $partnerIds = $uniqueExcelStores->keys()->map(fn($id) => (int) $id)->toArray();

        $this->info('2. Mengambil koordinat & email dari Odoo via XML-RPC...');
        $odooStoresRaw = $odooService->execute_kw('res.partner', 'search_read', [
            [['id', 'in', $partnerIds]]
        ], [
            'fields' => ['id', 'street', 'partner_latitude', 'partner_longitude', 'email']
        ]);

        $odooStores = collect($odooStoresRaw)->keyBy('id');

        $this->info('3. Menyiapkan data untuk upsert ke MySQL...');
        $records = $uniqueExcelStores->map(function ($row, $partnerId) use ($odooStores) {
            $storeDetail = $odooStores->get($partnerId);

            $lastOrderDate = null;
            if (!empty($row['last_order_date'])) {
                $lastOrderDate = ($row['last_order_date'] instanceof DateTimeInterface)
                    ? $row['last_order_date']->format('Y-m-d')
                    : substr((string) $row['last_order_date'], 0, 10);
            }

            $lat = (isset($storeDetail['partner_latitude']) && $storeDetail['partner_latitude'] !== false)
                ? (float) $storeDetail['partner_latitude'] : null;
            $lng = (isset($storeDetail['partner_longitude']) && $storeDetail['partner_longitude'] !== false)
                ? (float) $storeDetail['partner_longitude'] : null;

            $email = $storeDetail['email'] ?? null;

            return [
                'odoo_id'          => (int) $partnerId,
                'name'             => (string) ($row['partner_name'] ?? ''),
                'address'          => $storeDetail['street'] ?? $row['alamat'] ?? null,
                'city'             => (string) ($row['kota'] ?? ''),
                'phone'            => (string) ($row['phone_clean'] ?? ''),
                'email'            => is_string($email) ? $email : null,
                'sales_name'       => (string) ($row['sales_name'] ?? 'Unassigned'),
                'latitude'         => $lat,
                'longitude'        => $lng,
                'last_order_date'  => $lastOrderDate,
                'days_since'       => (int) ($row['days_since'] ?? 0),
                'weeks_since'      => (int) ($row['weeks_since'] ?? 0),
                'retensi_status'   => (string) ($row['retensi_status'] ?? 'DEAD ZONE'),
                'aktif_group'      => (string) ($row['aktif_group'] ?? 'NON_AKTIF'),
                'avg_retensi_weeks'=> (float) ($row['avg_retensi_weeks'] ?? 0),
                'gap_vs_average'   => (float) ($row['gap_vs_average'] ?? 0),
                'total_sales'      => (float) ($row['total_sales_2024_plus'] ?? 0),
                'priority'         => (int) ($row['priority'] ?? 99),
                'updated_at'       => now(),
            ];
        })->values()->toArray();

        StoreModel::upsert(
            $records,
            ['odoo_id'], 
            [
                'name', 'address', 'city', 'phone', 'email', 'sales_name',
                'latitude', 'longitude', 'last_order_date', 'days_since',
                'weeks_since', 'retensi_status', 'aktif_group',
                'avg_retensi_weeks', 'gap_vs_average', 'total_sales',
                'priority', 'updated_at'
            ]
        );

        $this->info('Sinkronisasi Berhasil! ' . count($records) . ' toko tersimpan di MySQL.');
        return Command::SUCCESS;
    }
}