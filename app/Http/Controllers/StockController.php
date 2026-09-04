<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Odoo\OdooProductService;
use Illuminate\Http\Request;

class StockController extends Controller
{
    protected $odoo;

    public function __construct(OdooProductService $odoo)
    {
        $this->odoo = $odoo;
    }

    public function index(Request $request)
    {
        // ============================================================
        // Company ID dikunci ke Company 1
        // ============================================================
        $companyId = 1;

        // ============================================================
        // Ambil semua produk yang pernah dijual + stok
        // + harga + berat dari Odoo
        // ============================================================
        $rawProducts = $this->odoo->getSoldProductsWithStock($companyId);

        // ============================================================
        // Format data produk
        // ============================================================
        $products = array_map(function ($item) {

            $qty = (float) ($item['raw_qty'] ?? 0);

            // Format level stok
            $item['level'] = number_format($qty, 0, ',', '.');

            // Status stok
            if ($qty <= 0) {
                $item['status'] = 'Out of Stock';
            } elseif ($qty < 10) {
                $item['status'] = 'Low Stock';
            } else {
                $item['status'] = 'In Stock';
            }

            // Pastikan tipe data konsisten
            $item['raw_qty'] = $qty;
            $item['price'] = (float) ($item['price'] ?? 0);
            $item['weight'] = (float) ($item['weight'] ?? 0);

            return $item;

        }, $rawProducts);

        // ============================================================
        // Summary statistik
        // ============================================================
        $totalSkus = count($products);

        $lowStockCount = count(
            array_filter(
                $products,
                fn($p) =>
                    $p['status'] === 'Low Stock' ||
                    $p['status'] === 'Out of Stock'
            )
        );

        // ============================================================
        // Response JSON
        // ============================================================
        return response()->json([
            'status' => 'success',
            'total_skus' => $totalSkus,
            'low_stock_count' => $lowStockCount,
            'data' => $products,
        ]);
    }
}