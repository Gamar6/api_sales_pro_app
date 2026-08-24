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
        $companyId = 1; // Dikunci khusus Company 1

        // 1. Ambil semua produk yang pernah dijual di Company 1 beserta stoknya
        $rawProducts = $this->odoo->getSoldProductsWithStock($companyId);

        // 2. Format status berdasarkan jumlah stok
        $products = array_map(function ($item) {
            $qty = $item['raw_qty'];
            $item['level'] = number_format($qty, 0, ',', '.');

            if ($qty <= 0) {
                $item['status'] = 'Out of Stock';
            } elseif ($qty < 10) {
                $item['status'] = 'Low Stock';
            } else {
                $item['status'] = 'In Stock';
            }
            return $item;
        }, $rawProducts);

        // 3. Hitung summary statistik
        $totalSkus = count($products);
        $lowStockCount = count(array_filter($products, fn($p) => $p['status'] === 'Low Stock' || $p['status'] === 'Out of Stock'));

        return response()->json([
            'status' => 'success',
            'total_skus' => $totalSkus,
            'low_stock_count' => $lowStockCount,
            'data' => $products
        ]);
    }
}