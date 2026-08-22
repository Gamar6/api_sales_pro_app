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
        $search = $request->query('search', '');
        $rawProducts = $this->odoo->getProductsWithStock(50, $search);

        // 1. Filter produk agar yang stoknya <= 0 dibuang/dilewati
        $filteredProducts = array_filter($rawProducts, function ($item) {
            $qty = (int) ($item['free_qty'] ?? 0);
            return $qty > 0; // Hanya ambil yang stoknya di atas 0
        });

        // 2. Transformasi data Odoo ke format UI Flutter
        $products = array_map(function ($item) {
            $qty = (int) ($item['free_qty'] ?? 0);
            $unit = is_array($item['uom_name']) ? $item['uom_name'][1] : ($item['uom_name'] ?? 'pcs');

            // Karena yang <= 0 sudah dibuang, status tinggal Low Stock atau In Stock
            if ($qty < 10) {
                $status = 'Low Stock';
            } else {
                $status = 'In Stock';
            }

            return [
                'id' => $item['id'],
                'title' => $item['display_name'],
                'subtitle' => ($item['default_code'] ? '[' . $item['default_code'] . '] ' : '') . 'Rp ' . number_format($item['lst_price'] ?? 0, 0, ',', '.'),
                'level' => number_format($qty, 0, ',', '.'),
                'raw_qty' => $qty,
                'unit' => strtolower($unit),
                'status' => $status,
                'imageUrl' => null,
            ];
        }, $filteredProducts); // <-- Perhatikan: menggunakan $filteredProducts

        // Penting: gunakan array_values() agar format JSON array kembali rapi dari index 0
        $products = array_values($products);

        return response()->json([
            'status' => 'success',
            'total_skus' => count($products),
            'low_stock_count' => count(array_filter($products, fn($p) => $p['raw_qty'] < 10)),
            'data' => $products
        ]);
    }
}