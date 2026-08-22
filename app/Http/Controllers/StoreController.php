<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StoreAssignment;
use App\Services\Odoo\OdooStoreService;

class StoreController extends Controller
{
    protected OdooStoreService $odooStoreService;

    public function __construct(OdooStoreService $odooStoreService)
    {
        $this->odooStoreService = $odooStoreService;
    }

    // 1. Ambil daftar toko terdekat + filter area + status klaim
    public function getNearbyStores(Request $request)
    {
        $userLat = (float) $request->input('latitude');
        $userLng = (float) $request->input('longitude');
        $areaFilter = $request->input('area');

        if (!$userLat || !$userLng) {
            return response()->json([
                'success' => false,
                'message' => 'Koordinat latitude dan longitude wajib diisi.'
            ], 400);
        }

        // 1. Tarik data toko dari Odoo via Service
        $odooStores = $this->odooStoreService->getStores(); // Ambil semua data toko

        if (empty($odooStores)) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        // 2. Ambil ID semua toko dari Odoo untuk cek klaim lokal secara sekaligus (mencegah N+1 query)
        $storeIds = array_column($odooStores, 'id');
        $activeClaims = StoreAssignment::with('user')
            ->whereIn('store_id', $storeIds)
            ->whereIn('status', ['claimed', 'onprogress'])
            ->get()
            ->keyBy('store_id');

        // 3. Olah data toko (Hitung Haversine + Mapping + Filtering)
        $formattedStores = collect($odooStores)
            ->filter(function ($store) use ($areaFilter) {
                // Filter area (berdasarkan kota) jika diisi
                if ($areaFilter && strtolower($store['city'] ?? '') !== strtolower($areaFilter)) {
                    return false;
                }
                return true;
            })
            ->map(function ($store) use ($userLat, $userLng, $activeClaims) {
                $storeLat = (float) ($store['partner_latitude'] ?? 0);
                $storeLng = (float) ($store['partner_longitude'] ?? 0);

                // Hitung Jarak Haversine
                $distanceInKm = ($storeLat != 0 && $storeLng != 0) 
                    ? $this->calculateHaversineDistance($userLat, $userLng, $storeLat, $storeLng)
                    : 999999; // Default jika koordinat toko tidak ada di Odoo

                // Format Label Jarak
                if ($distanceInKm < 1) {
                    $distanceLabel = round($distanceInKm * 1000) . ' m away';
                } else {
                    $distanceLabel = round($distanceInKm, 1) . ' km away';
                }

                // Cek status klaim dari database lokal
                $storeId = $store['id'];
                $activeClaim = $activeClaims->get($storeId);

                return [
                    'id' => $storeId,
                    'name' => $store['name'] ?? $store['display_name'],
                    'address' => $store['street'] ?? '',
                    'area' => $store['city'] ?? '',
                    'latitude' => $storeLat,
                    'longitude' => $storeLng,
                    'distance' => round($distanceInKm, 2),
                    'distance_label' => $distanceLabel,
                    'is_claimed' => $activeClaim ? true : false,
                    'claimed_by_name' => $activeClaim ? $activeClaim->user->name : null,
                    'status' => $activeClaim ? $activeClaim->status : 'available',
                ];
            })
            ->sortBy('distance') // Urutkan toko dari yang terdekat
            ->values();

        return response()->json([
            'success' => true,
            'data' => $formattedStores
        ]);
    }

    // 2. Fungsi untuk Klaim Toko
    public function claimStore(Request $request)
    {
        $request->validate([
            'store_id' => 'required|integer', // Menggunakan integer karena ID merujuk ke res.partner Odoo
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

        // Simpan klaim baru di DB lokal (store_id diisi ID Odoo)
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

    // Helper: Algoritma Haversine untuk kalkulasi jarak koordinat dalam kilometer
    private function calculateHaversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Jari-jari bumi dalam KM

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}