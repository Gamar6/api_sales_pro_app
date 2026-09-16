<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResetUserPasswordRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    /**
     * Display user management page.
     */
    public function index(Request $request): Response
    {
        $query = User::query();

        /*
         * Admin hanya melihat sales.
         * Superadmin dapat melihat semua user.
         */
        if ($request->user()->role === 'admin') {
            $query->where('role', 'sales');
        }

        /*
         * Search.
         */
        if ($request->filled('search')) {
            $search = $request->string('search')->trim();

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('nohp', 'like', "%{$search}%");
            });
        }

        /*
         * Filter role.
         */
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        /*
         * Filter status.
         */
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users = $query
            ->select([
                'id',
                'name',
                'username',
                'email',
                'nohp',
                'role',
                'status',
                'profile_photo_url',
                'created_at',
            ])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Users/index', [
            'users' => $users,

            'filters' => [
                'search' => $request->search,
                'role' => $request->role,
                'status' => $request->status,
            ],

            'currentUser' => [
                'id' => $request->user()->id,
                'role' => $request->user()->role,
            ],
        ]);
    }

    /**
     * Store new user.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        /*
         * Admin hanya boleh membuat sales.
         *
         * Ini sengaja dicek lagi di backend meskipun
         * sudah dibatasi oleh FormRequest.
         */
        if (
            $request->user()->role === 'admin'
            && $validated['role'] !== 'sales'
        ) {
            abort(403, 'Admin hanya dapat membuat user dengan role sales.');
        }

        User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'nohp' => $validated['nohp'],
            'role' => $validated['role'],
            'status' => 'active',
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with(
            'success',
            'User berhasil dibuat.'
        );
    }

    /**
     * Update user.
     */
    public function update(
        UpdateUserRequest $request,
        User $user
    ): RedirectResponse {
        $validated = $request->validated();
        $currentUser = $request->user();

        /*
         * Admin hanya boleh mengedit sales.
         */
        if (
            $currentUser->role === 'admin'
            && $user->role !== 'sales'
        ) {
            abort(403, 'Admin tidak dapat mengelola user ini.');
        }

        /*
         * Admin tidak boleh menaikkan privilege.
         */
        if (
            $currentUser->role === 'admin'
            && $validated['role'] !== 'sales'
        ) {
            abort(403, 'Admin hanya dapat menggunakan role sales.');
        }

        /*
         * Jangan izinkan perubahan role superadmin
         * menjadi role lain jika dia adalah superadmin terakhir.
         */
        if (
            $user->role === 'superadmin'
            && $validated['role'] !== 'superadmin'
            && $this->isLastSuperadmin($user)
        ) {
            return back()->withErrors([
                'role' => 'Superadmin terakhir tidak dapat diturunkan rolenya.',
            ]);
        }

        $user->update([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'nohp' => $validated['nohp'],
            'role' => $validated['role'],
        ]);

        return back()->with(
            'success',
            'Data user berhasil diperbarui.'
        );
    }

    /**
     * Change user status.
     */
    public function updateStatus(
        Request $request,
        User $user
    ): RedirectResponse {
        $request->validate([
            'status' => [
                'required',
                'in:active,suspended,inactive',
            ],
        ]);

        $currentUser = $request->user();
        $newStatus = $request->status;

        /*
         * Admin hanya boleh mengelola sales.
         */
        if (
            $currentUser->role === 'admin'
            && $user->role !== 'sales'
        ) {
            abort(403, 'Admin tidak dapat mengelola user ini.');
        }

        /*
         * Jangan sampai user mengubah dirinya sendiri
         * melalui endpoint status.
         */
        if ($currentUser->id === $user->id) {
            return back()->withErrors([
                'status' => 'Anda tidak dapat mengubah status akun sendiri.',
            ]);
        }

        /*
         * Jangan sampai superadmin terakhir
         * dibuat suspended/inactive.
         */
        if (
            $user->role === 'superadmin'
            && in_array($newStatus, ['suspended', 'inactive'], true)
            && $this->isLastActiveSuperadmin($user)
        ) {
            return back()->withErrors([
                'status' => 'Superadmin aktif terakhir tidak dapat dinonaktifkan.',
            ]);
        }

        DB::transaction(function () use ($user, $newStatus) {
            $user->update([
                'status' => $newStatus,
            ]);

            /*
             * Jika user tidak aktif, cabut seluruh
             * token Sanctum miliknya.
             */
            if (in_array($newStatus, ['suspended', 'inactive'], true)) {
                $user->tokens()->delete();
            }
        });

        $message = match ($newStatus) {
            'active' => 'User berhasil diaktifkan.',
            'suspended' => 'User berhasil disuspend.',
            'inactive' => 'User berhasil dinonaktifkan.',
        };

        return back()->with('success', $message);
    }

    /**
     * Reset password user.
     */
    public function resetPassword(
        ResetUserPasswordRequest $request,
        User $user
    ): RedirectResponse {
        $currentUser = $request->user();

        if (
            $currentUser->role === 'admin'
            && $user->role !== 'sales'
        ) {
            abort(403, 'Admin tidak dapat mereset password user ini.');
        }

        $user->update([
            'password' => Hash::make(
                $request->validated('password')
            ),
        ]);

        /*
         * Reset password juga mencabut seluruh
         * token Flutter yang lama.
         */
        $user->tokens()->delete();

        return back()->with(
            'success',
            'Password user berhasil direset.'
        );
    }

    /**
     * Check whether this is the last superadmin.
     */
    private function isLastSuperadmin(User $user): bool
    {
        return User::where('role', 'superadmin')
            ->whereKeyNot($user->id)
            ->doesntExist();
    }

    /**
     * Check whether this is the last active superadmin.
     */
    private function isLastActiveSuperadmin(User $user): bool
    {
        return User::where('role', 'superadmin')
            ->where('status', 'active')
            ->whereKeyNot($user->id)
            ->doesntExist();
    }
}
