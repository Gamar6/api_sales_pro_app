<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StoreModel;
use App\Models\StoreAssignment;
use App\Helpers\GeoHelper;
use Illuminate\Support\Facades\Validator;

class StoreController extends Controller
{
    // 1. Ambil daftar toko terdekat + filter area + status klaim
    public function getNearbyStores(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude'  => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'area'      => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors()
            ], 422);
        }

        // Ambil data yang sudah tervalidasi
        $userLat = $request->filled('latitude') ? (float) $request->input('latitude') : null;
        $userLng = $request->filled('longitude') ? (float) $request->input('longitude') : null;
        $areaFilter = $request->input('area');

        // Ambil toko dari MySQL beserta relasi activeClaim
        $stores = StoreModel::with(['activeClaim.user'])
            ->when($areaFilter, function ($query, $area) {
                return $query->whereRaw('LOWER(city) = ?', [strtolower($area)]);
            })
            ->get()
            ->map(function ($store) use ($userLat, $userLng) {
                $storeLat = (float) $store->latitude;
                $storeLng = (float) $store->longitude;

                // Default nilai jika koordinat user atau toko tidak valid
                $distanceInKm = null;
                $distanceLabel = '-';

                // Hitung Haversine HANYA jika koordinat user DAN koordinat toko tersedia
                if ($userLat !== null && $userLng !== null && $storeLat != 0 && $storeLng != 0) {
                    $distanceInKm = GeoHelper::calculateHaversineDistance($userLat, $userLng, $storeLat, $storeLng);
                    $distanceLabel = GeoHelper::formatDistanceLabel($distanceInKm);
                    
                    // Lakukan pembulatan jarak jika tipenya angka
                    $distanceInKm = round($distanceInKm, 2);
                }

                $activeClaim = $store->activeClaim;

                return [
                    'id'                => $store->odoo_id,
                    'name'              => $store->name,
                    'address'           => $store->address ?? '',
                    'area'              => $store->city ?? '',
                    'phone'             => $store->phone,
                    'sales_name'        => $store->sales_name,
                    'latitude'          => $storeLat,
                    'longitude'         => $storeLng,
                    'distance'          => $distanceInKm, // Menghasilkan float atau null
                    'distance_label'    => $distanceLabel, // Menghasilkan string (misal: "1.2 km" atau "-")
                    'retensi_status'    => $store->retensi_status,
                    'avg_retensi_weeks' => $store->avg_retensi_weeks,
                    'total_sales'       => (float) $store->total_sales,
                    'priority'          => $store->priority,
                    'is_claimed'        => (bool) $activeClaim,
                    'claimed_by_name'   => $activeClaim?->user?->name,
                    'status'            => $activeClaim?->status ?? 'available',
                ];
            });

        // HANYA lakukan sorting berdasarkan jarak jika user mengirimkan koordinatnya
        if ($userLat !== null && $userLng !== null) {
            $stores = $stores->sortBy('distance');
        } else {
            // Jika tanpa koordinat, urutkan berdasarkan priority atau ID toko sebagai fallback
            $stores = $stores->sortByDesc('priority');
        }

        return response()->json([
            'success' => true,
            'data'    => $stores->values()->all() // Pastikan index array di-reset
        ]);
    }

    // 2. Klaim Toko
    public function claimStore(Request $request)
    {
        $request->validate([
            'store_id' => 'required|integer', // Referensi odoo_id
            'sales_id' => 'required|exists:users,id',
        ]);

        // Cek ketersediaan klaim
        $isAlreadyClaimed = StoreAssignment::where('store_id', $request->store_id)
            ->whereIn('status', ['claimed', 'onprogress'])
            ->exists();

        if ($isAlreadyClaimed) {
            return response()->json([
                'success' => false,
                'message' => 'Toko ini sedang dikunjungi oleh sales lain!',
            ], 400);
        }

        $assignment = StoreAssignment::create([
            'store_id'   => $request->store_id,
            'claimed_by' => $request->sales_id,
            'status'     => 'claimed',
            'claimed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Toko berhasil diklaim',
            'data'    => $assignment
        ]);
    }
}
