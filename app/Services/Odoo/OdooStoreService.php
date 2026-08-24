<?php
    namespace App\Services\Odoo;

class OdooStoreService
{
    public function __construct(protected OdooClient $client) {}

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
}