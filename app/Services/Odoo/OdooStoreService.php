<?php
    namespace App\Services\Odoo;

class OdooStoreService
{
    public function __construct(protected OdooClient $client) {}

    public function calculateStockPercentage(int $partnerId, int $stockPcs): ?int
    {
        $lastOrderPcs = $this->getLatestOrderPcs($partnerId);
        if ($lastOrderPcs === null || $lastOrderPcs <= 0) {
            return null;
        }

        return (int) round(($stockPcs / $lastOrderPcs) * 100);
    }

    private function getLatestOrderPcs(int $partnerId): ?float
    {
        $orders = $this->client->executeKw(
            'sale.order',
            'search_read',
            [[
                ['partner_id', '=', $partnerId],
                ['state', 'in', ['sale', 'done']],
            ]],
            [
                'fields' => ['id'],
                'limit' => 1,
                'order' => 'date_order desc, id desc',
            ]
        );

        $orderId = $orders[0]['id'] ?? null;
        if (!is_numeric($orderId)) {
            return null;
        }

        $lines = $this->client->executeKw(
            'sale.order.line',
            'search_read',
            [[
                ['order_id', '=', (int) $orderId],
                ['product_id', '!=', false],
            ]],
            [
                'fields' => ['product_uom_qty', 'product_uom'],
            ]
        );

        if (!is_array($lines) || $lines === []) {
            return null;
        }

        $totalPcs = 0.0;
        foreach ($lines as $line) {
            $quantity = $line['product_uom_qty'] ?? null;
            $unitName = $line['product_uom'][1] ?? null;

            if (
                !is_numeric($quantity)
                || (float) $quantity < 0
                || !$this->isPcsUnit($unitName)
            ) {
                return null;
            }

            $totalPcs += (float) $quantity;
        }

        return $totalPcs;
    }

    public function getStores(): array
    {
        $domain = [
            '|', '|', '|', '|',
            ['city', 'ilike', 'Jakarta'],
            ['city', 'ilike', 'Bogor'],
            ['city', 'ilike', 'Depok'],
            ['city', 'ilike', 'Tangerang'],
            ['city', 'ilike', 'Bekasi'],
            ['sale_order_ids.date_order', '>=', '2025-01-01 00:00:00'],
            ['sale_order_ids.date_order', '<=', now()->format('Y-m-d 23:59:59')],
        ];

        $kwargs = [
            'fields' => ['id', 'name', 'display_name', 'phone', 'city', 'street', 'partner_latitude', 'partner_longitude'],
        ];

        return $this->client->executeKw('res.partner', 'search_read', [$domain], $kwargs);
    }

    public function getCompleteStoreData(array $storeIds): array
    {
        if (empty($storeIds)) return [];

        $domain = [['id', 'in', array_values($storeIds)]];
        $kwargs = [
            'fields' => ['id', 'name', 'display_name', 'street', 'street2', 'city', 'state_id', 'country_id', 'phone', 'mobile', 'email', 'vat']
        ];

        return $this->client->executeKw('res.partner', 'search_read', [$domain], $kwargs);
    }

    private function isPcsUnit(mixed $unitName): bool
    {
        if (!is_string($unitName)) {
            return false;
        }

        $normalized = strtolower(preg_replace('/[^a-z]/i', '', $unitName));

        return in_array(
            $normalized,
            ['unit', 'units', 'pc', 'pcs', 'piece', 'pieces'],
            true
        );
    }
}