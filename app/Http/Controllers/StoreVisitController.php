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

class StoreVisitController extends Controller
{
    /**
     * Aksi 1: Sales Klaim Toko untuk Mulai Kunjungan
     */
    public function claim(ClaimStoreRequest $request)
    {
        $salesId = $request->user()->id;
        $partnerId = $request->odoo_partner_id;
        $today = today()->toDateString();

        return DB::transaction(function () use ($salesId, $partnerId, $today) {
            // Gunakan lockForUpdate untuk mencegah 2 sales menekan tombol bersamaan (Race Condition)
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

            // Buat record klaim baru
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

    /**
     * Aksi 2: Submit Form Laporan Kunjungan (Check-out)
     */
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
            // Upload foto dokumentasi ke folder storage public
            $photoPaths = [];
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photo) {
                    $path = $photo->store('visit_reports/' . date('Y/m'), 'public');
                    $photoPaths[] = Storage::url($path);
                }
            }

            // Simpan isian laporan
            VisitReport::create([
                'store_visit_id'   => $visit->id,
                'pic_name'         => $request->pic_name,
                'activities'       => $request->activities,
                'stock_percentage' => $request->stock_percentage,
                'stock_pcs'        => $request->stock_pcs,
                'notes'            => $request->notes,
                'photos'           => $photoPaths,
            ]);

            // Ubah status klaim toko menjadi COMPLETED
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
            'visit_id'        => $activeVisit->id, // Pastikan ini ada!
            'odoo_partner_id' => $activeVisit->odoo_partner_id,
            'status'          => $activeVisit->status,
        ]
    ], 200);
}
}