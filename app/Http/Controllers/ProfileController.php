<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class ProfileController extends Controller
{
    public function updatePhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:2048', // Maksimal 2MB
        ]);

        $user = $request->user();

        // 1. Hapus foto lama di Cloudinary jika sudah pernah upload
        if ($user->profile_photo_public_id) {
            Cloudinary::destroy($user->profile_photo_public_id);
        }

        // 2. Upload foto baru dengan penyesuaian ukuran & crop ke wajah
        $uploadedFile = Cloudinary::upload($request->file('photo')->getRealPath(), [
            'folder' => 'app_sales/profiles',
            'transformation' => [
                'width' => 400,
                'height' => 400,
                'crop' => 'fill',
                'gravity' => 'face'
            ]
        ]);

        // 3. Simpan URL dan Public ID ke database
        $user->update([
            'profile_photo_url'       => $uploadedFile->getSecurePath(),
            'profile_photo_public_id' => $uploadedFile->getPublicId(),
        ]);

        return response()->json([
            'message' => 'Foto profil berhasil diperbarui',
            'data'    => [
                'profile_photo_url'       => $user->profile_photo_url,
                'profile_photo_public_id' => $user->profile_photo_public_id,
            ]
        ], 200);
    }
}