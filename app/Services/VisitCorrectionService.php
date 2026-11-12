<?php

namespace App\Services;

use App\Models\StoreVisit;
use App\Models\User;
use App\Models\VisitCorrection;
use App\Models\VisitReport;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VisitCorrectionService
{
    public function submit(
        User $user,
        int $visitId,
        array $validated
    ): array {
        $requestHash = $this->requestHash($visitId, $validated);

        return DB::transaction(function () use (
            $user,
            $visitId,
            $validated,
            $requestHash
        ): array {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            $visit = StoreVisit::query()
                ->whereKey($visitId)
                ->where('sales_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (! $visit) {
                abort(404, 'Kunjungan tidak ditemukan atau Anda tidak memiliki akses.');
            }

            $existingCorrection = VisitCorrection::query()
                ->where('user_id', $user->id)
                ->where('idempotency_key', $validated['idempotency_key'])
                ->first();

            if ($existingCorrection) {
                return [
                    'correction' => $existingCorrection,
                    'conflict' => ! hash_equals(
                        $existingCorrection->idempotency_hash,
                        $requestHash
                    ),
                    'replayed' => true,
                    'deadline' => $this->deadlineFor($visit),
                ];
            }

            if ($visit->status !== 'COMPLETED' || ! $visit->check_out_at) {
                throw ValidationException::withMessages([
                    'visit' => 'Koreksi hanya dapat diajukan untuk kunjungan yang sudah selesai.',
                ]);
            }

            $deadline = $this->deadlineFor($visit);
            if (CarbonImmutable::now(config('app.timezone'))->gt($deadline)) {
                throw ValidationException::withMessages([
                    'visit' => 'Batas waktu koreksi 12 jam sejak kunjungan selesai telah berakhir.',
                ]);
            }

            $report = $visit->report()->lockForUpdate()->first();
            if (! $report) {
                throw ValidationException::withMessages([
                    'visit' => 'Laporan kunjungan tidak tersedia untuk dikoreksi.',
                ]);
            }

            $previousData = $report->effectiveData();
            $changes = array_intersect_key(
                $validated,
                array_flip(VisitReport::CORRECTABLE_FIELDS)
            );
            $correctionData = array_replace($previousData, $changes);

            $correction = VisitCorrection::create([
                'store_visit_id' => $visit->id,
                'visit_report_id' => $report->id,
                'user_id' => $user->id,
                'previous_data' => $previousData,
                'correction_data' => $correctionData,
                'reason' => $validated['reason'],
                'status' => 'approved',
                'approved_at' => CarbonImmutable::now(config('app.timezone')),
                'idempotency_key' => $validated['idempotency_key'],
                'idempotency_hash' => $requestHash,
            ]);

            return [
                'correction' => $correction,
                'conflict' => false,
                'replayed' => false,
                'deadline' => $deadline,
            ];
        });
    }

    public function deadlineFor(StoreVisit $visit): ?CarbonImmutable
    {
        return $visit->check_out_at
            ? CarbonImmutable::instance($visit->check_out_at)->addHours(12)
            : null;
    }

    public function canCorrect(StoreVisit $visit): bool
    {
        $deadline = $this->deadlineFor($visit);

        return $visit->status === 'COMPLETED'
            && $deadline !== null
            && CarbonImmutable::now(config('app.timezone'))->lte($deadline);
    }

    private function requestHash(int $visitId, array $validated): string
    {
        $payload = ['visit_id' => $visitId];

        foreach (VisitReport::CORRECTABLE_FIELDS as $field) {
            if (array_key_exists($field, $validated)) {
                $payload[$field] = $validated[$field];
            }
        }

        $payload['reason'] = $validated['reason'];

        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
    }
}
