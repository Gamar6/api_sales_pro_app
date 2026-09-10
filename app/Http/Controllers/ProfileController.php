<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function updatePhoto(Request $request){
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
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


    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
