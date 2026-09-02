<?php
    namespace App\Services\Odoo;

class OdooSchemaService
{
    public function __construct(protected OdooClient $client) {}

    /**
     * Cari daftar Nama Model di Odoo
     */
    public function getAllModels(string $search = ''): array
    {
        $domain = [];
        if (!empty($search)) {
            $domain[] = ['model', 'like', $search];
        }

        $kwargs = [
            'fields' => ['model', 'name'],
            'limit'  => 10
        ];

        return $this->client->executeKw('ir.model', 'search_read', [$domain], $kwargs);
    }

    /**
     * Intip semua kolom (fields) di dalam suatu Model
     */
    public function getModelFields(string $modelName): array
    {
        $kwargs = [
            'attributes' => ['string', 'type']
        ];

        return $this->client->executeKw($modelName, 'fields_get', [], $kwargs);
    }
}