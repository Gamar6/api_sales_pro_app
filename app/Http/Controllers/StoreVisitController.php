<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClaimStoreRequest;
use App\Http\Requests\SubmitVisitReportRequest;
use App\Models\StoreVisit;
use App\Models\User;
use App\Models\VisitReport;
use App\Services\Odoo\OdooClient;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StoreVisitController extends Controller
{
    protected OdooClient $odooClient;

    public function __construct(OdooClient $odooClient)
    {
        $this->odooClient = $odooClient;
    }

    public function claim(ClaimStoreRequest $request)
    {
        $salesId = $request->user()->id;
        $partnerId = (int) $request->odoo_partner_id;
        $now = CarbonImmutable::now(config('app.timezone'));
        $today = $now->toDateString();
        $weekStart = $now->startOfWeek(CarbonInterface::MONDAY)->startOfDay();
        $nextWeek = $weekStart->addWeek();

        try {
            return DB::transaction(function () use ($salesId, $partnerId, $today, $now, $weekStart, $nextWeek) {
                // Mengunci row sales agar dua klaim paralel dari sales yang sama
                // tidak dapat membuat dua kunjungan aktif.
                User::query()->whereKey($salesId)->lockForUpdate()->firstOrFail();

                $salesActiveVisit = StoreVisit::query()
                    ->where('sales_id', $salesId)
                    ->where('status', 'IN_VISIT')
                    ->lockForUpdate()
                    ->first();

                if ($salesActiveVisit) {
                    $message = $salesActiveVisit->odoo_partner_id === $partnerId
                        ? 'Anda sudah memiliki kunjungan aktif di toko ini.'
                        : 'Selesaikan atau batalkan kunjungan aktif sebelum mengunjungi toko lain.';

                    return response()->json([
                        'status'  => 'error',
                        'message' => $message,
                        'data'    => [
                            'store_visit_id' => $salesActiveVisit->id,
                            'odoo_partner_id' => $salesActiveVisit->odoo_partner_id,
                        ],
                    ], 422);
                }

                $blockingVisit = StoreVisit::with('sales')
                    ->where('odoo_partner_id', $partnerId)
                    ->where(function ($query) use ($weekStart, $nextWeek) {
                        $query->where('status', 'IN_VISIT')
                            ->orWhere(function ($completedQuery) use ($weekStart, $nextWeek) {
                                $completedQuery->where('status', 'COMPLETED')
                                    ->where('check_out_at', '>=', $weekStart)
                                    ->where('check_out_at', '<', $nextWeek);
                            });
                    })
                    ->orderByRaw("CASE WHEN status = 'IN_VISIT' THEN 0 ELSE 1 END")
                    ->latest('id')
                    ->lockForUpdate()
                    ->first();

                if ($blockingVisit) {
                    $salesName = $blockingVisit->sales_id === $salesId
                        ? 'Anda'
                        : ($blockingVisit->sales?->name ?? 'sales lain');
                    $message = $blockingVisit->status === 'IN_VISIT'
                        ? "Toko ini sedang dikunjungi oleh {$salesName}."
                        : "Toko ini sudah dikunjungi minggu ini oleh {$salesName}.";

                    return response()->json([
                        'status'  => 'error',
                        'message' => $message,
                    ], 422);
                }

                $visit = StoreVisit::create([
                    'odoo_partner_id' => $partnerId,
                    'sales_id'        => $salesId,
                    'visit_date'      => $today,
                    'status'          => 'IN_VISIT',
                    'active_store_key' => $partnerId,
                    'active_sales_key' => $salesId,
                    'check_in_at'     => $now,
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
        } catch (QueryException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Toko atau sales baru saja diklaim pada perangkat lain. Muat ulang data lalu coba lagi.',
                ], 409);
            }

            throw $exception;
        }
    }

    public function submitReport(SubmitVisitReportRequest $request, $visitId)
    {
        $salesId = $request->user()->id;

        try {
            return DB::transaction(function () use ($request, $visitId, $salesId) {
                $visit = StoreVisit::where('id', $visitId)
                    ->where('sales_id', $salesId)
                    ->lockForUpdate()
                    ->first();

                if (!$visit) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Data kunjungan tidak ditemukan atau Anda tidak memiliki akses.',
                    ], 404);
                }

                if ($visit->status !== 'IN_VISIT') {
                    $message = $visit->status === 'COMPLETED'
                        ? 'Laporan kunjungan toko ini sudah pernah dikirim sebelumnya.'
                        : 'Kunjungan ini sudah dibatalkan dan tidak dapat dilaporkan.';

                    return response()->json([
                        'status'  => 'error',
                        'message' => $message,
                    ], 422);
                }

                $photoPaths = [];
                foreach ($request->file('photos', []) as $photo) {
                    $path = $photo->store('visit_reports/' . date('Y/m'), 'public');
                    $photoPaths[] = Storage::url($path);
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
                    'status'           => 'COMPLETED',
                    'active_store_key' => null,
                    'active_sales_key' => null,
                    'check_out_at'     => now(),
                ]);

                return response()->json([
                    'status'  => 'success',
                    'message' => 'Laporan kunjungan berhasil dikirim.',
                    'data'    => [
                        'store_visit_id' => $visit->id,
                        'check_out_at'   => $visit->check_out_at->toDateTimeString(),
                    ]
                ]);
            });
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menyimpan laporan kunjungan: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function cancel(Request $request, $visitId)
    {
        $salesId = $request->user()->id;

        return DB::transaction(function () use ($visitId, $salesId) {
            $visit = StoreVisit::where('id', $visitId)
                ->where('sales_id', $salesId)
                ->lockForUpdate()
                ->first();

            if (!$visit) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Data kunjungan tidak ditemukan atau Anda tidak memiliki akses.',
                ], 404);
            }

            if ($visit->status !== 'IN_VISIT') {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Hanya kunjungan aktif yang dapat dibatalkan.',
                ], 422);
            }

            $visit->update([
                'status'           => 'CANCELLED',
                'active_store_key' => null,
                'active_sales_key' => null,
                'check_out_at'     => now(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Kunjungan dibatalkan. Toko dapat dikunjungi kembali.',
            ]);
        });
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

        $partners = $this->odooClient->executeKw(
            'res.partner',
            'search_read',
            [[['id', '=', (int) $activeVisit->odoo_partner_id]]],
            ['fields' => ['name'], 'limit' => 1]
        );
        $outletName = is_array($partners) && !empty($partners)
            ? ($partners[0]['name'] ?? null)
            : null;

        return response()->json([
            'success' => true,
            'message' => 'Kunjungan aktif ditemukan',
            'data' => [
                'visit_id'        => $activeVisit->id,
                'odoo_partner_id' => $activeVisit->odoo_partner_id,
                'outlet_name'     => $outletName,
                'status'          => $activeVisit->status,
            ]
        ], 200);
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
