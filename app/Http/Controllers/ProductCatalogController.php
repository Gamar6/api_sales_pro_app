<?php

namespace App\Http\Controllers;

use App\Services\Odoo\OdooProductService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductCatalogController extends Controller
{
    public function __construct(
        protected OdooProductService $odoo
    ) {}

    public function index(Request $request): Response
    {
        $companyId = 1;

        $rawProducts = $this->odoo->getSoldProductsWithStock(
            companyId: $companyId,
            limit: 500
        );

        $products = array_map(function (array $product) {
            $qty = (float) ($product['raw_qty'] ?? 0);
            $price = (float) ($product['price'] ?? 0);
            $weight = (float) ($product['weight'] ?? 0);

            if ($qty <= 0) {
                $status = 'Out of Stock';
                $statusType = 'danger';
            } elseif ($qty < 20) {
                $status = 'Low Stock';
                $statusType = 'warning';
            } elseif ($qty > 100) {
                $status = 'Healthy';
                $statusType = 'success';
            } else {
                $status = 'In Stock';
                $statusType = 'success';
            }

            $title = $product['title'] ?? 'Produk Tanpa Nama';

            /*
             * getSoldProductsWithStock() saat ini belum mengambil:
             * brand, barcode, reserved, reorder level, bin,
             * packaging dari Odoo secara dinamis.
             *
             * Jadi kita gunakan fallback yang aman.
             */
            return [
                'id' => (int) ($product['id'] ?? 0),

                'sku' => $product['default_code']
                    ?? ('ODOO-' . ($product['id'] ?? '0')),

                'name' => $this->cleanProductName($title),

                'fullName' => $title,

                'brand' => 'Fiva Food',

                'category' => 'Finished Goods',

                'subcategory' => 'Saleable Product',

                'barcode' => null,

                'image' => $product['imageUrl'] ?? null,

                'price' => $price,

                'unit' => $product['unit'] ?? 'pcs',

                'packaging' => $this->buildPackagingLabel($product),

                'stock' => $qty,

                'available' => $qty,

                'reserved' => 0,

                'reorderLevel' => 20,

                'weight' => $weight,

                'weightUnit' => $product['weight_unit'] ?? 'kg',

                'status' => $status,

                'statusType' => $statusType,

                'location' => $product['subtitle']
                    ?? 'Lokasi: CV. Fiva Food Meat & Supply',

                'warehouse' => 'Main Distribution Center',

                'bin' => null,

                'incentive' => null,

                'priority' => $qty > 0 && $qty < 20,

                'rawQty' => $qty,
            ];
        }, $rawProducts);

        /*
         * Hilangkan produk duplikat berdasarkan ID.
         */
        $products = collect($products)
            ->unique('id')
            ->values()
            ->all();

        $totalSkus = count($products);

        $healthyCount = count(
            array_filter(
                $products,
                fn ($product) =>
                    $product['statusType'] === 'success'
            )
        );

        $lowStockCount = count(
            array_filter(
                $products,
                fn ($product) =>
                    $product['statusType'] === 'warning'
            )
        );

        $outOfStockCount = count(
            array_filter(
                $products,
                fn ($product) =>
                    $product['statusType'] === 'danger'
            )
        );

        $totalStock = array_sum(
            array_column($products, 'stock')
        );

        $totalValuation = array_sum(
            array_map(
                fn ($product) =>
                    $product['stock'] * $product['price'],
                $products
            )
        );

        $healthIndex = $totalSkus > 0
            ? round(($healthyCount / $totalSkus) * 100, 1)
            : 0;

        $categories = collect($products)
            ->groupBy('category')
            ->map(fn ($items) => count($items))
            ->sortDesc()
            ->map(
                fn ($count, $category) => [
                    'name' => $category,
                    'count' => $count,
                ]
            )
            ->values()
            ->all();

        return Inertia::render('ProductCatalog/main_ProductCatalog', [
            'products' => $products,

            'categories' => $categories,

            'warehouses' => [
                [
                    'id' => 1,
                    'name' => 'Main Distribution Center',
                    'shortName' => 'Main DC',
                ],
            ],

            'summary' => [
                'totalSkus' => $totalSkus,
                'totalStock' => $totalStock,
                'totalValuation' => $totalValuation,
                'healthIndex' => $healthIndex,
                'healthyCount' => $healthyCount,
                'lowStockCount' => $lowStockCount,
                'outOfStockCount' => $outOfStockCount,
            ],

            'sync' => [
                'status' => 'Connected',
                'database' => 'Odoo ERP',
                'latency' => null,
            ],
        ]);
    }

    protected function cleanProductName(string $name): string
    {
        $name = preg_replace(
            '/^\s*\[PRODUK JADI\]\s*/i',
            '',
            $name
        );

        return trim($name);
    }

    protected function buildPackagingLabel(array $product): string
    {
        $packs = (int) ($product['packs_per_package'] ?? 1);
        $packageUnit = $product['package_unit'] ?? 'karton';

        if ($packs > 1) {
            return "{$packs}x " . ucfirst($packageUnit);
        }

        return ucfirst($packageUnit);
    }
}
