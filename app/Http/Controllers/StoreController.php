<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\StoreModel;
use App\Models\StoreAssignment;

class StoreController extends Controller
{
    // 1. Ambil daftar toko terdekat + filter area + status klaim
    public function getNearbyStores(Request $request)
    {
        $userLat = $request->input('latitude');
        $userLng = $request->input('longitude');
        $areaFilter = $request->input('area');

        if (!$userLat || !$userLng) {
            return response()->json([
                'success' => false,
                'message' => 'Koordinat latitude dan longitude wajib diisi.'
            ], 400);
        }

        $stores = StoreModel::select(
            'id',
            'name',
            'address',
            'area',
            'latitude',
            'longitude',
            DB::raw("(6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(?) ) + 
                sin( radians(?) ) * sin( radians( latitude ) ) ) ) AS distance")
        )
        ->setBindings([$userLat, $userLng, $userLat])
        ->when($areaFilter, function ($query, $area) {
            return $query->where('area', $area);
        })
        ->orderBy('distance', 'asc')
        ->get();

        $formattedStores = $stores->map(function ($store) {
            $distanceInKm = $store->distance;
            if ($distanceInKm < 1) {
                $store->distance_label = round($distanceInKm * 1000) . ' m away';
            } else {
                $store->distance_label = round($distanceInKm, 1) . ' km away';
            }

            $activeClaim = StoreAssignment::with('user')
                ->where('store_id', $store->id)
                ->whereIn('status', ['claimed', 'onprogress'])
                ->first();

            $store->is_claimed = $activeClaim ? true : false;
            $store->claimed_by_name = $activeClaim ? $activeClaim->user->name : null;
            $store->status = $activeClaim ? $activeClaim->status : 'available';

            return $store;
        });

        return response()->json([
            'success' => true,
            'data' => $formattedStores
        ]);
    }

    // 2. Fungsi untuk Klaim Toko
    public function claimStore(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'sales_id' => 'required|exists:users,id',
        ]);

        // Cek apakah toko sedang diklaim/dikunjungi sales lain
        $isAlreadyClaimed = StoreAssignment::where('store_id', $request->store_id)
            ->whereIn('status', ['claimed', 'onprogress'])
            ->exists();

        if ($isAlreadyClaimed) {
            return response()->json([
                'success' => false,
                'message' => 'Toko ini sedang dikunjungi oleh sales lain!',
            ], 400);
        }

        // Simpan klaim baru
        $assignment = StoreAssignment::create([
            'store_id' => $request->store_id,
            'claimed_by' => $request->sales_id,
            'status' => 'claimed',
            'claimed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Toko berhasil diklaim',
            'data' => $assignment
        ]);
    }
}