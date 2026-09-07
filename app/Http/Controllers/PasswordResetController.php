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
    // 1. Endpoint untuk mengirim email/token lupa password
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // Mengirim token reset menggunakan broker bawaan Laravel
        $status = Password::sendResetLink(
            $request->only('email')
        );

        // Selalu kembalikan respons sukses untuk mencegah user enumeration (keamanan)
        return response()->json([
            'message' => 'Jika email terdaftar, instruksi pemulihan password telah dikirim.'
        ], 200);
    }

    // 2. Endpoint untuk memproses password baru
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed', // butuh input password_confirmation
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

        // Cek status apakah berhasil atau gagal (token invalid/expired)
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
