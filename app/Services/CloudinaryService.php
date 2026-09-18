<?php

namespace App\Services;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\UploadedFile;

class CloudinaryService
{
    /**
     * Upload image to Cloudinary.
     */
    public function uploadImage(
        UploadedFile $file,
        string $folder
    ): array {
        $uploaded = Cloudinary::upload(
            $file->getRealPath(),
            [
                'folder' => $folder,
                'resource_type' => 'image',
            ]
        );

        return [
            'url' => $uploaded->getSecurePath(),
            'public_id' => $uploaded->getPublicId(),
        ];
    }

    /**
     * Delete image to Cloudinary.
     */
    public function deleteImage(?string $publicId): void
    {
        if (!$publicId) {
            return;
        }

        Cloudinary::destroy($publicId);
    }
}
