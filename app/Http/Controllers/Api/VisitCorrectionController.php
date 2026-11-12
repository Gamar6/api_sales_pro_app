<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitVisitCorrectionRequest;
use App\Models\StoreVisit;
use App\Services\VisitCorrectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VisitCorrectionController extends Controller
{
    public function store(
        SubmitVisitCorrectionRequest $request,
        int $visit,
        VisitCorrectionService $service
    ): JsonResponse {
        $result = $service->submit(
            $request->user(),
            $visit,
            $request->validated()
        );

        if ($result['conflict']) {
            return response()->json([
                'status' => 'error',
                'message' => 'Idempotency-Key sudah digunakan untuk koreksi yang berbeda.',
            ], 409);
        }

        $response = response()->json([
            'status' => 'success',
            'message' => 'Koreksi kunjungan berhasil disetujui secara otomatis.',
            'data' => [
                'correction' => $result['correction'],
                'effective_data' => $result['correction']->correction_data,
                'correction_deadline_at' => $result['deadline']?->toDateTimeString(),
            ],
        ], 201);

        if ($result['replayed']) {
            $response->header('Idempotent-Replayed', 'true');
        }

        return $response;
    }

    public function history(
        Request $request,
        int $visit,
        VisitCorrectionService $service
    ): JsonResponse {
        $storeVisit = StoreVisit::query()
            ->whereKey($visit)
            ->where('sales_id', $request->user()->id)
            ->with(['report.corrections'])
            ->first();

        if (! $storeVisit) {
            abort(404, 'Kunjungan tidak ditemukan atau Anda tidak memiliki akses.');
        }

        if (! $storeVisit->report) {
            abort(404, 'Laporan kunjungan tidak ditemukan.');
        }

        $deadline = $service->deadlineFor($storeVisit);

        return response()->json([
            'status' => 'success',
            'data' => [
                'visit_id' => $storeVisit->id,
                'status' => $storeVisit->status,
                'completed_at' => $storeVisit->check_out_at?->toDateTimeString(),
                'correction_window' => [
                    'deadline_at' => $deadline?->toDateTimeString(),
                    'can_correct' => $service->canCorrect($storeVisit),
                ],
                'original_data' => $storeVisit->report->originalData(),
                'effective_data' => $storeVisit->report->effectiveData(),
                'corrections' => $storeVisit->report->corrections
                    ->map(fn ($correction) => $correction->makeHidden([
                        'idempotency_key',
                        'idempotency_hash',
                    ])->toArray())
                    ->values(),
            ],
        ]);
    }
}
