<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        \Log::info('Password reset config diagnostic', [
            'app_key_set' => filled(config('app.key')),
            'app_key_type' => gettype(config('app.key')),
            'app_key_length' => strlen((string) config('app.key')),
        ]);

        $status = Password::sendResetLink(
                    $request->only('email')
                );

        return response()->json([
            'message' => 'Jika email terdaftar, instruksi pemulihan password telah dikirim.'
        ], 200);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Password berhasil diubah. Silakan login kembali.'
            ], 200);
        }

        return response()->json([
            'message' => 'Gagal merubah password, token tidak valid atau sudah kedaluwarsa.'
        ], 400);
    }
}
