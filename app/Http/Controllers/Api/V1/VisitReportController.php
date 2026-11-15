<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FieldStore;
use App\Models\StoreModel;
use App\Models\StoreVisit;
use App\Models\User;
use App\Models\VisitReport;
use Carbon\CarbonImmutable;
use App\Services\CloudinaryService;
use App\Services\Odoo\OdooStoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

class VisitReportController extends Controller
{
    public function store(
        Request $request,
        CloudinaryService $cloudinary,
        OdooStoreService $odooStore
    ): JsonResponse {
        $storeType = $request->input('store_type');
        $request->merge([
            'idempotency_key' => $request->header(
                'Idempotency-Key',
                $request->input('idempotency_key')
            ),
        ]);

        $validated = $request->validate([
            'store_type' => ['required', Rule::in(['regular', 'field'])],
            'store_id' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'check_in_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'check_in_lat' => ['required', 'numeric', 'between:-90,90'],
            'check_in_lng' => ['required', 'numeric', 'between:-180,180'],
            'sales_accuracy' => ['nullable', 'numeric', 'min:0'],
            'location_captured_at' => ['nullable', 'date'],
            'visited_at' => ['required', 'date'],
            'pic_name' => ['nullable', 'string', 'max:255'],
            'activities' => ['nullable', 'array'],
            'activities.*' => ['string', 'max:255'],
            'stock_percentage' => ['nullable', 'integer', 'between:0,100'],
            'stock_pcs' => ['nullable', 'integer', 'min:0'],
            'photos' => ['nullable', 'array', 'max:4'],
            'photos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'idempotency_key' => ['nullable', 'string', 'max:128'],
        ]);

        $userId = $request->user()->id;
        $storeExists = $validated['store_type'] === 'field'
            ? FieldStore::query()
                ->whereKey($validated['store_id'])
                ->where('user_id', $userId)
                ->exists()
            : StoreModel::query()
                ->where('odoo_id', $validated['store_id'])
                ->exists();

        if (!$storeExists) {
            return response()->json([
                'message' => 'Toko tidak ditemukan atau Anda tidak memiliki akses.',
                'errors' => [
                    'store_id' => ['Toko tidak ditemukan atau Anda tidak memiliki akses.'],
                ],
            ], 422);
        }

        $visitedAt = CarbonImmutable::parse($validated['visited_at'])
            ->setTimezone(config('app.timezone'));
        $checkInLatitude = (float) $validated['check_in_lat'];
        $checkInLongitude = (float) $validated['check_in_lng'];
        $salesAccuracy = isset($validated['sales_accuracy'])
            ? (float) $validated['sales_accuracy']
            : null;
        $idempotencyKey = $request->header('Idempotency-Key')
            ?? ($validated['idempotency_key'] ?? null);
        $idempotencyHash = $idempotencyKey === null
            ? null
            : $this->requestHash($request, $validated);

        if ($idempotencyKey !== null) {
            $existingReport = VisitReport::query()
                ->where('user_id', $userId)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existingReport) {
                return $this->idempotentResponse($existingReport, $idempotencyHash);
            }
        }

        $stockPercentage = $validated['stock_percentage'] ?? null;
        if (isset($validated['stock_pcs'])) {
            $stockPercentage = null;
            if ($validated['store_type'] === 'regular') {
                $stockPercentage = $odooStore->calculateStockPercentage(
                    (int) $validated['store_id'],
                    (int) $validated['stock_pcs']
                );
            }
        }

        $uploadedPhotos = [];
        $photoUrls = [];
        $checkInPhoto = $request->file('check_in_photo');
        $checkInPhotoHash = $checkInPhoto
            ? $this->uploadedFileHash($checkInPhoto)
            : null;

        try {
            foreach ($request->file('photos', []) as $photo) {
                $photoHash = $this->uploadedFileHash($photo);
                if (!array_key_exists($photoHash, $uploadedPhotos)) {
                    $uploadedPhotos[$photoHash] = $this->uploadPhoto(
                        $cloudinary,
                        $photo
                    );
                }
                $photoUrls[] = $uploadedPhotos[$photoHash]['url'];
            }

            if ($checkInPhotoHash !== null
                && !array_key_exists($checkInPhotoHash, $uploadedPhotos)) {
                $uploadedPhotos[$checkInPhotoHash] = $this->uploadPhoto(
                    $cloudinary,
                    $checkInPhoto
                );
            }
        } catch (Throwable $exception) {
            Log::error('Visit report photo upload failed.', [
                'user_id' => $userId,
                'exception' => $exception,
            ]);
            $this->cleanupUploadedPhotos($cloudinary, $uploadedPhotos, $userId);

            return response()->json([
                'status' => 'error',
                'message' => 'Foto laporan gagal diunggah. Silakan coba kembali.',
            ], 502);
        }

        $photoUrl = $checkInPhotoHash !== null
            ? $uploadedPhotos[$checkInPhotoHash]['url']
            : ($photoUrls[0] ?? null);

        if ($photoUrl !== null && $photoUrls === []) {
            $photoUrls[] = $photoUrl;
        }

        try {
            $result = DB::transaction(function () use (
                $userId,
                $validated,
                $visitedAt,
                $photoUrl,
                $photoUrls,
                $checkInLatitude,
                $checkInLongitude,
                $salesAccuracy,
                $stockPercentage,
                $idempotencyKey,
                $idempotencyHash
            ): array {
                if ($idempotencyKey !== null) {
                    User::query()->whereKey($userId)->lockForUpdate()->firstOrFail();

                    $existingReport = VisitReport::query()
                        ->where('user_id', $userId)
                        ->where('idempotency_key', $idempotencyKey)
                        ->first();

                    if ($existingReport) {
                        return [
                            'report' => $existingReport,
                            'replayed' => true,
                            'conflict' => !hash_equals(
                                (string) $existingReport->idempotency_hash,
                                (string) $idempotencyHash
                            ),
                        ];
                    }
                }

                $fieldStore = null;
                $storeVisit = null;
                $distanceFromStore = null;
                $isOutsideRadius = null;
                $checkOutAt = null;

                if ($validated['store_type'] === 'field') {
                    $fieldStore = FieldStore::query()
                        ->whereKey($validated['store_id'])
                        ->where('user_id', $userId)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $distanceFromStore = $this->calculateDistanceInMeters(
                        $checkInLatitude,
                        $checkInLongitude,
                        (float) $fieldStore->latitude,
                        (float) $fieldStore->longitude
                    );
                    $isOutsideRadius = $distanceFromStore > 100;
                    $checkOutAt = now();

                    $storeVisit = StoreVisit::create([
                        'store_type' => 'field',
                        'field_store_id' => $fieldStore->id,
                        'odoo_partner_id' => null,
                        'sales_id' => $userId,
                        'visit_date' => $visitedAt
                            ->setTimezone('Asia/Jakarta')
                            ->toDateString(),
                        'status' => 'COMPLETED',
                        'active_store_key' => null,
                        'active_sales_key' => null,
                        'check_in_at' => $visitedAt,
                        'check_out_at' => $checkOutAt,
                    ]);
                }

                $report = VisitReport::create([
                        'user_id' => $userId,
                        'store_type' => $validated['store_type'],
                        'store_id' => $validated['store_id'],
                        'store_visit_id' => $storeVisit?->id,
                        'pic_name' => $validated['pic_name'] ?? null,
                        'activities' => $validated['activities'] ?? null,
                        'stock_percentage' => $stockPercentage,
                        'stock_pcs' => $validated['stock_pcs'] ?? null,
                        'notes' => $validated['notes'] ?? null,
                        'check_in_photo' => $photoUrl,
                        'check_in_lat' => $checkInLatitude,
                        'check_in_lng' => $checkInLongitude,
                        'sales_latitude' => $checkInLatitude,
                        'sales_longitude' => $checkInLongitude,
                        'sales_accuracy' => $salesAccuracy,
                        'distance_from_store' => $distanceFromStore,
                        'is_outside_radius' => $isOutsideRadius,
                        'location_captured_at' => $validated['location_captured_at']
                            ?? $visitedAt,
                        'photos' => $photoUrls,
                        'visited_at' => $visitedAt,
                        'idempotency_key' => $idempotencyKey,
                        'idempotency_hash' => $idempotencyHash,
                    ]);

                return [
                    'report' => $report,
                    'store_visit' => $storeVisit,
                    'distance_from_store' => $distanceFromStore,
                    'is_outside_radius' => $isOutsideRadius,
                    'check_out_at' => $checkOutAt,
                    'replayed' => false,
                    'conflict' => false,
                ];
            });
        } catch (Throwable $exception) {
            $this->cleanupUploadedPhotos($cloudinary, $uploadedPhotos, $userId);
            throw $exception;
        }

        if ($result['replayed']) {
            $this->cleanupUploadedPhotos($cloudinary, $uploadedPhotos, $userId);

            return $this->idempotentResponse(
                $result['report'],
                $idempotencyHash,
                $result['conflict']
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Laporan kunjungan berhasil dicatat.',
            'data' => $result['report']->makeHidden([
                'idempotency_key',
                'idempotency_hash',
            ]),
            'store_visit' => $result['store_visit'] ? [
                'id' => $result['store_visit']->id,
                'status' => $result['store_visit']->status,
                'check_out_at' => $result['check_out_at'],
            ] : null,
            'distance_from_store' => $result['distance_from_store'],
            'is_outside_radius' => $result['is_outside_radius'],
        ], 201);
    }

    private function requestHash(Request $request, array $validated): string
    {
        $photoHashes = [];
        foreach ($request->file('photos', []) as $photo) {
            $photoHashes[] = $this->uploadedFileHash($photo);
        }
        if ($request->hasFile('check_in_photo')) {
            $photoHashes[] = $this->uploadedFileHash(
                $request->file('check_in_photo')
            );
        }

        return hash('sha256', json_encode([
            'store_type' => $validated['store_type'],
            'store_id' => (int) $validated['store_id'],
            'notes' => $validated['notes'] ?? null,
            'check_in_lat' => (string) $validated['check_in_lat'],
            'check_in_lng' => (string) $validated['check_in_lng'],
            'visited_at' => (string) $validated['visited_at'],
            'pic_name' => $validated['pic_name'] ?? null,
            'activities' => $validated['activities'] ?? null,
            'stock_percentage' => $validated['stock_percentage'] ?? null,
            'stock_pcs' => $validated['stock_pcs'] ?? null,
            'sales_accuracy' => $validated['sales_accuracy'] ?? null,
            'location_captured_at' => $validated['location_captured_at'] ?? null,
            'photo_hashes' => $photoHashes,
        ], JSON_THROW_ON_ERROR));
    }

    private function uploadPhoto(
        CloudinaryService $cloudinary,
        \Illuminate\Http\UploadedFile $photo
    ): array {
        $uploaded = $cloudinary->uploadImage(
            $photo,
            'app_sales/visit_reports/' . now()->format('Y/m')
        );

        if (empty($uploaded['url'])) {
            throw new \RuntimeException('Cloudinary did not return a photo URL.');
        }

        return $uploaded;
    }

    private function uploadedFileHash(\Illuminate\Http\UploadedFile $file): string
    {
        $hash = hash_file('sha256', $file->getRealPath());
        if ($hash === false) {
            throw new \RuntimeException('Unable to fingerprint uploaded photo.');
        }

        return $hash;
    }

    private function calculateDistanceInMeters(
        float $latitudeA,
        float $longitudeA,
        float $latitudeB,
        float $longitudeB
    ): float {
        $latitudeDelta = deg2rad($latitudeB - $latitudeA);
        $longitudeDelta = deg2rad($longitudeB - $longitudeA);

        $a = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($latitudeA))
            * cos(deg2rad($latitudeB))
            * sin($longitudeDelta / 2) ** 2;

        return round(6_371_000 * 2 * atan2(sqrt($a), sqrt(1 - $a)), 2);
    }

    private function idempotentResponse(
        VisitReport $report,
        ?string $requestHash,
        ?bool $conflict = null
    ): JsonResponse {
        $conflict ??= !hash_equals((string) $report->idempotency_hash, (string) $requestHash);

        if ($conflict) {
            return response()->json([
                'status' => 'error',
                'message' => 'Idempotency-Key sudah digunakan untuk laporan yang berbeda.',
            ], 409);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Laporan kunjungan berhasil dicatat.',
            'data' => $report->makeHidden([
                'idempotency_key',
                'idempotency_hash',
            ]),
        ], 201)->header('Idempotent-Replayed', 'true');
    }

    private function cleanupUploadedPhotos(
        CloudinaryService $cloudinary,
        array $uploadedPhotos,
        int $userId
    ): void {
        foreach ($uploadedPhotos as $uploadedPhoto) {
            try {
                $cloudinary->deleteImage($uploadedPhoto['public_id'] ?? null);
            } catch (Throwable $exception) {
                Log::error('Failed to clean up an unreferenced visit report photo.', [
                    'user_id' => $userId,
                    'exception' => $exception,
                ]);
            }
        }
    }
}
