<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FieldStore;
use App\Services\CloudinaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class FieldStoreController extends Controller
{
    public function store(Request $request, CloudinaryService $cloudinary): JsonResponse
    {
        $validated = $request->validate([
            'store_name' => ['required', 'string', 'max:255'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:5000'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $uploadedPhoto = null;
        try {
            $uploadedPhoto = $cloudinary->uploadImage(
                $request->file('photo'),
                'app_sales/field_stores/' . now()->format('Y/m')
            );
            if (empty($uploadedPhoto['url'])) {
                throw new \RuntimeException('Cloudinary did not return a photo URL.');
            }
        } catch (Throwable $exception) {
            Log::error('Field store photo upload failed.', [
                'user_id' => $request->user()->id,
                'exception' => $exception,
            ]);
            if ($uploadedPhoto !== null) {
                try {
                    $cloudinary->deleteImage($uploadedPhoto['public_id'] ?? null);
                } catch (Throwable $cleanupException) {
                    Log::error('Failed to clean up an invalid field store photo upload.', [
                        'user_id' => $request->user()->id,
                        'exception' => $cleanupException,
                    ]);
                }
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Foto toko gagal diunggah. Silakan coba kembali.',
            ], 502);
        }

        try {
            $fieldStore = DB::transaction(fn () => FieldStore::create([
                'user_id' => $request->user()->id,
                'store_name' => $validated['store_name'],
                'owner_name' => $validated['owner_name'] ?? null,
                'phone_number' => $validated['phone_number'] ?? null,
                'address' => $validated['address'],
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'photo_url' => $uploadedPhoto['url'],
            ]));
        } catch (Throwable $exception) {
            try {
                $cloudinary->deleteImage($uploadedPhoto['public_id'] ?? null);
            } catch (Throwable $cleanupException) {
                Log::error('Failed to clean up an unreferenced field store photo.', [
                    'user_id' => $request->user()->id,
                    'exception' => $cleanupException,
                ]);
            }

            throw $exception;
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Toko lapangan berhasil ditambahkan.',
            'data' => $fieldStore,
        ], 201);
    }
}
