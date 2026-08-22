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
    public function getActiveSoldProductsForCV(int $companyId = 1, int $limit = 10): array
    {
        $productIds = $this->getSoldProductIds($limit * 3);

        if (empty($productIds)) {
            return [];
        }

        $domainQuant = [
            ['product_id', 'in', $productIds],
            ['company_id', '=', $companyId],
        ];

        $kwargsQuant = [
            'fields' => ['product_id', 'location_id', 'quantity', 'reserved_quantity', 'available_quantity'],
            'limit'  => $limit,
        ];

        return $this->client->executeKw('stock.quant', 'search_read', [$domainQuant], $kwargsQuant);
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