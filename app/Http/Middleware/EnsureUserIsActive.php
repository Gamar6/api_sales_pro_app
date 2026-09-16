<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Pastikan user yang sedang login masih aktif.
     */
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        if ($user->status !== 'active') {
            /*
             * Web session.
             */
            if ($request->hasSession()) {
                auth()->logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            /*
             * API / Flutter.
             */
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Akun Anda sudah tidak aktif. Silakan hubungi administrator.',
                ], 403);
            }

            abort(
                403,
                'Akun Anda sudah tidak aktif. Silakan hubungi administrator.'
            );
        }

        return $next($request);
    }
}
