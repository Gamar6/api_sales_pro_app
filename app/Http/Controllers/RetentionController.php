<?php

namespace App\Http\Controllers;

use App\Models\StoreVisit;
use App\Services\OdooService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Rap2hpoutre\FastExcel\FastExcel;
use DateTimeInterface;

class RetentionController extends Controller
{
    public function getRetentionStores(Request $request, OdooService $odooService)
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

        $excelData = (new FastExcel)->import($latestFile);

        if ($excelData->isEmpty()) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        $partnerIds = $excelData->pluck('partner_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();

        $odooStoresRaw = $odooService->execute_kw('res.partner', 'search_read', [
            [['id', 'in', $partnerIds]]
        ], [
            'fields' => ['id', 'street', 'partner_latitude', 'partner_longitude', 'email']
        ]);

        $odooStores = collect($odooStoresRaw)->keyBy('id');

        $currentUserId = $request->user() ? $request->user()->id : null;
        
        $todayVisits = StoreVisit::with('sales')
            ->whereIn('odoo_partner_id', $partnerIds)
            ->whereDate('visit_date', today())
            ->get()
            ->keyBy('odoo_partner_id');

        $processedData = $excelData->map(function (array $row) use ($odooStores, $todayVisits, $currentUserId) {
            $partnerId = (int) ($row['partner_id'] ?? 0);
            
            $storeDetail = $odooStores->get($partnerId);
            $activeVisit = $todayVisits->get($partnerId);

            $lastOrderDate = null;
            if (!empty($row['last_order_date'])) {
                if ($row['last_order_date'] instanceof DateTimeInterface) {
                    $lastOrderDate = $row['last_order_date']->format('Y-m-d');
                } else {
                    $lastOrderDate = substr((string) $row['last_order_date'], 0, 10);
                }
            }

            $claimInfo = [
                'status'          => $activeVisit ? $activeVisit->status : 'AVAILABLE',
                'store_visit_id'  => $activeVisit ? $activeVisit->id : null,
                'claimed_by_id'   => $activeVisit ? $activeVisit->sales_id : null,
                'claimed_by_name' => $activeVisit && $activeVisit->sales ? $activeVisit->sales->name : null,
                'is_current_user' => $activeVisit && $currentUserId ? ($activeVisit->sales_id === $currentUserId) : false,
                'check_in_at'     => $activeVisit && $activeVisit->check_in_at ? $activeVisit->check_in_at->toDateTimeString() : null,
                'check_out_at'    => $activeVisit && $activeVisit->check_out_at ? $activeVisit->check_out_at->toDateTimeString() : null,
            ];

            return [
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

                'alamat'            => $storeDetail['street'] ?? null,
                'latitude'          => isset($storeDetail['partner_latitude']) && $storeDetail['partner_latitude'] !== false
                                        ? (float) $storeDetail['partner_latitude'] 
                                        : null,
                'longitude'         => isset($storeDetail['partner_longitude']) && $storeDetail['partner_longitude'] !== false
                                        ? (float) $storeDetail['partner_longitude'] 
                                        : null,
                'email'             => $storeDetail['email'] ?? null,

                'claim_info'        => $claimInfo,
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