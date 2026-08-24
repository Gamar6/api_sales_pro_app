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
        // 1. Cari semua baris penjualan resmi (sale/done) di Company 1
        $soldLines = $this->client->executeKw('sale.order.line', 'search_read', [
            [
                ['company_id', '=', $companyId],
                ['state', 'in', ['sale', 'done']]
            ]
        ], [
            'fields' => ['product_id'],
            'limit' => $limit * 5,
        ]);

        // 2. Buat Master List produk yang pernah dijual & BER-FILTER [PRODUK JADI]
        $productMap = [];
        foreach ($soldLines as $line) {
            if (isset($line['product_id'][0])) {
                $id = $line['product_id'][0];
                $name = $line['product_id'][1] ?? 'Produk Tanpa Nama';

                // FILTER KETAT: Hanya ambil produk yang judulnya mengandung '[PRODUK JADI]'
                if (!str_contains($name, '[PRODUK JADI]')) {
                    continue;
                }
                
                if (!isset($productMap[$id])) {
                    $productMap[$id] = [
                        'id' => $id,
                        'title' => $name,
                        'subtitle' => 'Lokasi: CV. Fiva Food Meat & Supply',
                        'raw_qty' => 0, //default 0 if out of stock
                        'unit' => 'pcs',
                        'imageUrl' => null,
                    ];
                }
            }
        }

        if (empty($productMap)) {
            return [];
        }

        $productIds = array_keys($productMap);

        // 3. Ambil stok aktual dari stock.quant hanya untuk ID produk yang lolos filter
        $domainQuant = [
            ['product_id', 'in', $productIds],
            ['location_id.usage', '=', 'internal'],
            ['company_id', '=', $companyId],
        ];

        $quants = $this->client->executeKw('stock.quant', 'search_read', [$domainQuant], [
            'fields' => ['product_id', 'quantity', 'available_quantity'],
        ]);

        // 4. Akumulasikan stok dari quants ke master list produk
        foreach ($quants as $quant) {
            if (isset($quant['product_id'][0])) {
                $pId = $quant['product_id'][0];
                if (isset($productMap[$pId])) {
                    $qty = (int) ($quant['available_quantity'] ?? $quant['quantity'] ?? 0);
                    $productMap[$pId]['raw_qty'] += $qty;
                }
            }
        }

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