<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClaimStoreRequest;
use App\Http\Requests\SubmitVisitReportRequest;
use App\Models\StoreVisit;
use App\Models\VisitReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Exception;
use App\Services\Odoo\OdooClient;

class StoreVisitController extends Controller
{
    public function claim(ClaimStoreRequest $request)
    {
        $salesId = $request->user()->id;
        $partnerId = $request->odoo_partner_id;
        $today = today()->toDateString();

        return DB::transaction(function () use ($salesId, $partnerId, $today) {
            $existingVisit = StoreVisit::with('sales')
                ->where('odoo_partner_id', $partnerId)
                ->whereDate('visit_date', $today)
                ->lockForUpdate()
                ->first();

            if ($existingVisit) {
                if ($existingVisit->status === 'COMPLETED') {
                    return response()->json([
                        'status'  => 'error',
                        'message' => "Toko ini sudah selesai dikunjungi hari ini oleh {$existingVisit->sales->name}.",
                    ], 422);
                }

                if ($existingVisit->status === 'IN_VISIT') {
                    $salesName = $existingVisit->sales_id === $salesId ? 'Anda' : $existingVisit->sales->name;
                    return response()->json([
                        'status'  => 'error',
                        'message' => "Toko ini sedang dikunjungi oleh {$salesName}.",
                    ], 422);
                }
            }

            $visit = StoreVisit::create([
                'odoo_partner_id' => $partnerId,
                'sales_id'        => $salesId,
                'visit_date'      => $today,
                'status'          => 'IN_VISIT',
                'check_in_at'     => now(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Berhasil melakukan klaim toko.',
                'data'    => [
                    'store_visit_id' => $visit->id,
                    'check_in_at'    => $visit->check_in_at->toDateTimeString(),
                ]
            ], 201);
        });
    }

    public function submitReport(SubmitVisitReportRequest $request, $visitId)
    {
        $salesId = $request->user()->id;

        $visit = StoreVisit::where('id', $visitId)
            ->where('sales_id', $salesId)
            ->first();

        if (!$visit) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data kunjungan tidak ditemukan atau Anda tidak memiliki akses.',
            ], 440);
        }

        if ($visit->status === 'COMPLETED') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Laporan kunjungan toko ini sudah pernah dikirim sebelumnya.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $photoPaths = [];
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photo) {
                    $path = $photo->store('visit_reports/' . date('Y/m'), 'public');
                    $photoPaths[] = Storage::url($path);
                }
            }

            VisitReport::create([
                'store_visit_id'   => $visit->id,
                'pic_name'         => $request->pic_name,
                'activities'       => $request->activities,
                'stock_percentage' => $request->stock_percentage,
                'stock_pcs'        => $request->stock_pcs,
                'notes'            => $request->notes,
                'photos'           => $photoPaths,
            ]);

            $visit->update([
                'status'       => 'COMPLETED',
                'check_out_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Laporan kunjungan berhasil dikirim.',
                'data'    => [
                    'store_visit_id' => $visit->id,
                    'check_out_at'   => $visit->check_out_at->toDateTimeString(),
                ]
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menyimpan laporan kunjungan: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getActiveVisit(Request $request)
    {
        $user = $request->user();

        $activeVisit = StoreVisit::where('sales_id', $user->id)
            ->where('status', 'IN_VISIT')
            ->latest('check_in_at')
            ->first();

        if (!$activeVisit) {
            return response()->json([
                'success' => true,
                'message' => 'Tidak ada kunjungan aktif',
                'data' => null
            ], 200);
        }

        return response()->json([
            'success' => true,
            'message' => 'Kunjungan aktif ditemukan',
            'data' => [
                'visit_id'        => $activeVisit->id,
                'odoo_partner_id' => $activeVisit->odoo_partner_id,
                'status'          => $activeVisit->status,
            ]
        ], 200);
    }

        protected OdooClient $odooClient;

        // Inject OdooClient lewat constructor
        public function __construct(OdooClient $odooClient)
        {
            $this->odooClient = $odooClient;
        }

        public function history(Request $request)
    {
        $salesId = $request->user()->id;

        // STEP 1: Ambil data riwayat kunjungan & laporan dari MySQL (Database Utama)
        $visits = StoreVisit::with(['report'])
            ->where('sales_id', $salesId)
            ->orderBy('visit_date', 'desc')
            ->orderBy('check_in_at', 'desc')
            ->get();

        // STEP 2: Kumpulkan semua odoo_partner_id dari MySQL (pastikan di-cast ke Integer)
        $partnerIds = $visits->pluck('odoo_partner_id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();

        // STEP 3: Cari detail nama & alamat toko ke Odoo (via XML-RPC)
        $partnersMap = collect();

        if (!empty($partnerIds)) {
            $odooPartners = $this->odooClient->executeKw(
                'res.partner',
                'search_read',
                [[['id', 'in', $partnerIds]]],                 // Filter domain berdasarkan ID
                ['fields' => ['id', 'name', 'street', 'city']] // Ambil kolom nama & alamat saja
            );

            // Jika Odoo mengembalikan data, kelompokkan key berdasarkan ID partner
            if (is_array($odooPartners)) {
                $partnersMap = collect($odooPartners)->keyBy('id');
            }
        }

        // STEP 4: Gabungkan data MySQL + data Odoo menjadi 1 objek JSON untuk Flutter
        $data = $visits->map(function ($visit) use ($partnersMap) {
            $partnerId = (int) $visit->odoo_partner_id;
            $partner = $partnersMap->get($partnerId);

            return [
                'id'           => $visit->id,
                'sales_id'     => $visit->sales_id,
                'visit_date'   => $visit->visit_date,
                'status'       => $visit->status,
                'check_in_at'  => $visit->check_in_at,
                'check_out_at' => $visit->check_out_at,
                'report'       => $visit->report,
                'partner'      => [
                    // Ambil 'name' dari Odoo. Jika Odoo gagal/kosong, baru fallback ke ID
                    'name'   => $partner['name'] ?? ('Toko #' . $visit->odoo_partner_id),
                    'street' => $partner['street'] ?? '-',
                    'city'   => $partner['city'] ?? '-',
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $data
        ], 200);
    }
}