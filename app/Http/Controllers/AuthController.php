<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    //Login user melalui API.
    public function login(Request $request)
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Username atau password salah',
            ], 401);
        }

        if ($user->status === 'suspended') {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda sedang ditangguhkan. Silakan hubungi administrator.',
            ], 403);
        }

        if ($user->status === 'inactive') {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda sudah tidak aktif. Silakan hubungi administrator.',
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil',
            'data' => $user,
            'token' => $token,
        ]);
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Sesi login tidak valid atau token kedaluwarsa.',
            ], 401);
        }

        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8'],
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Kata sandi saat ini tidak sesuai.',
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kata sandi berhasil diperbarui.',
        ], 200);
    }
}

