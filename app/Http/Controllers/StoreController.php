<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\StoreModel;

class StoreController extends Controller
{
    public function getNearbyStores(Request $request)
    {
        // Tangkap latitude & longitude yang dikirim dari HP Flutter
        $userLat = $request->input('latitude');
        $userLng = $request->input('longitude');

        // Validasi jika koordinat tidak dikirim
        if (!$userLat || !$userLng) {
            return response()->json([
                'success' => false,
                'message' => 'Koordinat latitude dan longitude wajib diisi.'
            ], 400);
        }

        // Query Haversine Formula untuk menghitung jarak
        $stores = StoreModel::select(
            'id',
            'name',
            'address',
            'latitude',
            'longitude',
            'area',
            DB::raw("(6371 * acos( cos( radians(?) ) * 
                cos( radians( latitude ) ) * 
                cos( radians( longitude ) - radians(?) ) + 
                sin( radians(?) ) * 
                sin( radians( latitude ) ) ) ) AS distance")
        )
        ->setBindings([$userLat, $userLng, $userLat])
        ->orderBy('distance', 'asc') // Urutkan dari yang terdekat
        ->get();

        // Format hasil jarak agar mudah dibaca di Flutter
        $formattedStores = $stores->map(function ($store) {
            $distanceInKm = $store->distance;

            if ($distanceInKm < 1) {
                // Jika kurang dari 1 km, tampilkan dalam meter (cth: "350 m away")
                $store->distance_label = round($distanceInKm * 1000) . ' m away';
            } else {
                // Jika 1 km atau lebih, tampilkan dalam km (cth: "1.2 km away")
                $store->distance_label = round($distanceInKm, 1) . ' km away';
            }

            return $store;
        });

        return response()->json([
            'success' => true,
            'data' => $formattedStores
        ]);
    }
}