<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Username atau password salah',
            ], 401);
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

    // Cek jika user tidak terautentikasi / token tidak valid
    if (!$user) {
        return response()->json([
            'message' => 'Sesi login tidak valid atau token kedaluwarsa.'
        ], 401);
    }

    $request->validate([
        'current_password' => ['required', 'string'],
        'new_password'     => ['required', 'string', 'min:8'],
    ]);

    if (!Hash::check($request->current_password, $user->password)) {
        return response()->json([
            'message' => 'Kata sandi saat ini tidak sesuai.'
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