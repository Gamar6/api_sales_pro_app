<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StoreModel;
use App\Models\StoreAssignment;
use App\Helpers\GeoHelper;
use Illuminate\Support\Facades\Validator;

class StoreController extends Controller
{
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

        $userLat = $request->filled('latitude') ? (float) $request->input('latitude') : null;
        $userLng = $request->filled('longitude') ? (float) $request->input('longitude') : null;
        $areaFilter = $request->input('area');

        $stores = StoreModel::with(['activeClaim.user'])
            ->when($areaFilter, function ($query, $area) {
                return $query->whereRaw('LOWER(city) = ?', [strtolower($area)]);
            })
            ->get()
            ->map(function ($store) use ($userLat, $userLng) {
                $storeLat = (float) $store->latitude;
                $storeLng = (float) $store->longitude;

                $distanceInKm = null;
                $distanceLabel = '-';

                if ($userLat !== null && $userLng !== null && $storeLat != 0 && $storeLng != 0) {
                    $distanceInKm = GeoHelper::calculateHaversineDistance($userLat, $userLng, $storeLat, $storeLng);
                    $distanceLabel = GeoHelper::formatDistanceLabel($distanceInKm);
                    
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
                    'distance'          => $distanceInKm, 
                    'distance_label'    => $distanceLabel, 
                    'retensi_status'    => $store->retensi_status,
                    'avg_retensi_weeks' => $store->avg_retensi_weeks,
                    'total_sales'       => (float) $store->total_sales,
                    'priority'          => $store->priority,
                    'is_claimed'        => (bool) $activeClaim,
                    'claimed_by_name'   => $activeClaim?->user?->name,
                    'status'            => $activeClaim?->status ?? 'available',
                ];
            });

        if ($userLat !== null && $userLng !== null) {
            $stores = $stores->sortBy('distance');
        } else {
            $stores = $stores->sortByDesc('priority');
        }

        return response()->json([
            'success' => true,
            'data'    => $stores->values()->all() 
        ]);
    }

    public function claimStore(Request $request)
    {
        $request->validate([
            'store_id' => 'required|integer',
            'sales_id' => 'required|exists:users,id',
        ]);

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
