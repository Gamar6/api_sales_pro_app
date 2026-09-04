<?php
    namespace App\Services\Odoo;

class OdooProductService
{
    public function __construct(protected OdooClient $client) {}

    /**
     * Ambil sampel data stok produk
     */
    public function getStockProducts(int $limit = 10): array
    {
        $domain = [
            ['quantity', '>', 0]
        ];

        $kwargs = [
            'fields' => ['id', 'product_id', 'location_id', 'quantity'],
            'limit'  => $limit
        ];

        return $this->client->executeKw('stock.quant', 'search_read', [$domain], $kwargs);
    }

    /**
     * Ambil Produk Siap Jual (Saleable) & Stoknya
     */
    public function getProductsWithStock(int $limit = 20, string $search = ''): array
    {
        $domain = [
            ['sale_ok', '=', true],
            ['categ_id', 'child_of', 2], // ID 2 = All / Saleable
        ];

        if (!empty($search)) {
            $domain[] = ['name', 'ilike', $search];
        }

        $kwargs = [
            'fields' => [
                'id', 'display_name', 'default_code', 'lst_price', 
                'qty_available', 'free_qty', 'uom_name', 'categ_id'
            ],
            'limit' => $limit
        ];

        return $this->client->executeKw('product.product', 'search_read', [$domain], $kwargs);
    }

    /**
     * Ambil daftar Kategori Produk
     */
    public function getProductCategories(): array
    {
        $kwargs = [
            'fields' => ['id', 'name', 'complete_name'],
            'limit'  => 50
        ];

        return $this->client->executeKw('product.category', 'search_read', [[]], $kwargs);
    }

    /**
     * Ambil produk yang pernah terjual DAN statusnya masih aktif
     */
    public function getActiveSoldProducts(int $limit = 20): array
    {
        $domain = [
            ['state', 'in', ['sale', 'done']],
            ['product_id.active', '=', true],
        ];

        $kwargs = [
            'fields' => ['id', 'product_id', 'product_uom_qty', 'price_unit', 'order_id'],
            'limit'  => $limit,
        ];

        return $this->client->executeKw('sale.order.line', 'search_read', [$domain], $kwargs);
    }

    /**
     * Ambil produk terjual dan stoknya khusus Perusahaan/CV tertentu
     */
    public function getSoldProductsWithStock(int $companyId = 1, int $limit = 500): array
    {
        // ============================================================
        // 1. Cari semua produk yang pernah terjual di Company 1
        // ============================================================
        $soldLines = $this->client->executeKw(
            'sale.order.line',
            'search_read',
            [
                [
                    ['company_id', '=', $companyId],
                    ['state', 'in', ['sale', 'done']],
                ],
            ],
            [
                'fields' => ['product_id'],
                'limit' => $limit * 5,
            ]
        );

        // ============================================================
        // 2. Buat master list produk
        // ============================================================
        $productMap = [];

        foreach ($soldLines as $line) {
            if (!isset($line['product_id'][0])) {
                continue;
            }

            $id = $line['product_id'][0];
            $name = $line['product_id'][1] ?? 'Produk Tanpa Nama';

            // Hanya PRODUK JADI
            if (!str_contains($name, '[PRODUK JADI]')) {
                continue;
            }

            if (!isset($productMap[$id])) {
                $productMap[$id] = [
                    'id' => $id,
                    'title' => $name,
                    'subtitle' => 'Lokasi: CV. Fiva Food Meat & Supply',

                    // Stok
                    'raw_qty' => 0,
                    'unit' => 'pcs',

                    // Harga
                    'price' => 0,

                    // Data berat
                    'weight' => 0,
                    'weight_unit' => 'kg',

                    // Packaging
                    // Untuk sementara default.
                    // Nanti bisa kita ambil dari Odoo kalau field packaging
                    // sudah diketahui.
                    'package_unit' => 'karton',
                    'packs_per_package' => 1,

                    // Gambar
                    'imageUrl' => null,
                ];
            }
        }

        if (empty($productMap)) {
            return [];
        }

        $productIds = array_keys($productMap);

        // ============================================================
        // 3. Ambil stok produk
        // ============================================================
        $domainQuant = [
            ['product_id', 'in', $productIds],
            ['location_id.usage', '=', 'internal'],
            ['company_id', '=', $companyId],
        ];

        $quants = $this->client->executeKw(
            'stock.quant',
            'search_read',
            [$domainQuant],
            [
                'fields' => [
                    'product_id',
                    'quantity',
                    'available_quantity',
                ],
            ]
        );

        // ============================================================
        // 4. Akumulasi stok
        // ============================================================
        foreach ($quants as $quant) {
            if (!isset($quant['product_id'][0])) {
                continue;
            }

            $productId = $quant['product_id'][0];

            if (!isset($productMap[$productId])) {
                continue;
            }

            $qty = (float) ($quant['available_quantity'] ?? 0);

            $productMap[$productId]['raw_qty'] += $qty;
        }

        // ============================================================
        // 5. Ambil informasi produk dari Odoo
        // ============================================================
        $products = $this->client->executeKw(
            'product.product',
            'search_read',
            [
                [
                    ['id', 'in', $productIds],
                ],
            ],
            [
                'fields' => [
                    'id',
                    'list_price',
                    'weight',
                    'uom_id',
                ],
            ]
        );

        // ============================================================
        // 6. Masukkan harga + berat + satuan ke master list
        // ============================================================
        foreach ($products as $product) {
            $productId = $product['id'] ?? null;

            if ($productId === null || !isset($productMap[$productId])) {
                continue;
            }

            // Harga
            $productMap[$productId]['price'] =
                (float) ($product['list_price'] ?? 0);

            // Berat dari Odoo
            $weight = (float) ($product['weight'] ?? 0);

            $productMap[$productId]['weight'] = $weight;

            // Odoo weight menggunakan kg
            $productMap[$productId]['weight_unit'] = 'kg';

            // UoM dari Odoo
            if (isset($product['uom_id'][1])) {
                $productMap[$productId]['unit'] =
                    strtolower($product['uom_id'][1]) === 'units'
                        ? 'pcs'
                        : $product['uom_id'][1];
            }
        }

        // ============================================================
        // 7. Kembalikan hasil
        // ============================================================
        return array_values($productMap);
    }

    /**
     * Ambil daftar lokasi gudang bertipe internal
     */
    public function getLocations(): array
    {
        $domain = [
            ['usage', '=', 'internal']
        ];

        $kwargs = [
            'fields' => ['id', 'name', 'complete_name', 'company_id'],
            'limit'  => 50
        ];

        return $this->client->executeKw('stock.location', 'search_read', [$domain], $kwargs);
    }

    /**
     * Ambil produk terjual dan stoknya dari lokasi CV tertentu
     */
    public function getActiveSoldProductsFromCV(int $cvLocationId, int $limit = 10): array
    {
        $productIds = $this->getSoldProductIds($limit * 3);

        if (empty($productIds)) {
            return [];
        }

        $domainQuant = [
            ['product_id', 'in', $productIds],
            ['location_id', '=', $cvLocationId],
        ];

        $kwargsQuant = [
            'fields' => ['product_id', 'quantity', 'reserved_quantity', 'available_quantity'],
            'limit'  => $limit,
        ];

        return $this->client->executeKw('stock.quant', 'search_read', [$domainQuant], $kwargsQuant);
    }

    /**
     * Helper internal untuk mengambil ID Produk unik yang pernah terjual
     */
    protected function getSoldProductIds(int $limit): array
    {
        $domainLine = [
            ['state', 'in', ['sale', 'done']],
            ['product_id.active', '=', true],
        ];

        $soldLines = $this->client->executeKw(
            'sale.order.line', 
            'search_read', 
            [$domainLine], 
            ['fields' => ['product_id'], 'limit' => $limit]
        );

        $productIds = [];
        foreach ($soldLines as $line) {
            if (isset($line['product_id'][0])) {
                $productIds[] = $line['product_id'][0];
            }
        }

        return array_values(array_unique($productIds));
    }
}