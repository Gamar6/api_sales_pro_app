<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;

class ContactPersonController extends Controller
{
    public function index(): JsonResponse
    {
        $contactPersons = User::query()
            ->where('role', 'admin')
            ->where('as_cp', true)
            ->where('status', 'active')
            ->select([
                'id',
                'name',
                'nohp',
            ])
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $contactPersons,
        ]);
    }
}
