<?php

namespace App\Services;

use PhpXmlRpc\Client;
use PhpXmlRpc\Value;
use PhpXmlRpc\Request;

class OdooService
{
    protected string $url;
    protected string $db;
    protected string $username;
    protected string $password;
    protected ?int $uid = null;

    public function __construct()
    {
        $this->url = config('services.odoo.url', env('ODOO_URL'));
        $this->db = config('services.odoo.db', env('ODOO_DB'));
        $this->username = config('services.odoo.username', env('ODOO_USERNAME'));
        $this->password = config('services.odoo.password', env('ODOO_PASSWORD'));
    }

    /**
     * Autentikasi ke Odoo & dapatkan User ID (UID)
     */
    public function authenticate(): bool
    {
        $client = new Client($this->url . '/xmlrpc/2/common');
        
        $req = new Request('authenticate', [
            new Value($this->db, "string"),
            new Value($this->username, "string"),
            new Value($this->password, "string"),
            new Value([], "array"),
        ]);

        $response = $client->send($req);

        if ($response->faultCode()) {
            return false;
        }

        $this->uid = $response->value()->scalarval();
        return is_int($this->uid) && $this->uid > 0;
    }

    /**
     * Cek status koneksi ke Odoo
     */
    public function checkConnection(): bool
    {
        try {
            return $this->authenticate();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Ambil sampel data stok produk
     */
    public function getStockProducts(int $limit = 10): array
    {
        if (!$this->uid && !$this->authenticate()) {
            return [];
        }

        $client = new Client($this->url . '/xmlrpc/2/object');

        $kwargs = new Value([
            'fields' => new Value([
                new Value('id', 'string'),
                new Value('product_id', 'string'),
                new Value('location_id', 'string'),
                new Value('quantity', 'string'),
            ], 'array'),
            'limit' => new Value($limit, 'int')
        ], 'struct');

        $domain = new Value([
            new Value([
                new Value('quantity', 'string'),
                new Value('>', 'string'),
                new Value(0, 'int')
            ], 'array')
        ], 'array');

        $req = new Request('execute_kw', [
            new Value($this->db, "string"),
            new Value($this->uid, "int"),
            new Value($this->password, "string"),
            new Value('stock.quant', "string"),
            new Value('search_read', "string"),
            new Value([$domain], "array"),
            $kwargs
        ]);

        $response = $client->send($req);

        if ($response->faultCode()) {
            return [];
        }

        return json_decode(json_encode($response->value()->scalarval()), true) ?? [];
    }

    /**
     * Cari daftar Nama Model di Odoo
     */
    public function getAllModels(string $search = ''): array
    {
        if (!$this->uid && !$this->authenticate()) return [];

        $client = new \PhpXmlRpc\Client($this->url . '/xmlrpc/2/common'); // or object
        $client = new \PhpXmlRpc\Client($this->url . '/xmlrpc/2/object');

        $domain = [];
        if (!empty($search)) {
            $domain[] = new \PhpXmlRpc\Value([
                new \PhpXmlRpc\Value('model', 'string'),
                new \PhpXmlRpc\Value('like', 'string'),
                new \PhpXmlRpc\Value($search, 'string')
            ], 'array');
        }

        $kwargs = new \PhpXmlRpc\Value([
            'fields' => new \PhpXmlRpc\Value([
                new \PhpXmlRpc\Value('model', 'string'),
                new \PhpXmlRpc\Value('name', 'string'),
            ], 'array'),
            'limit' => new \PhpXmlRpc\Value(10, 'int')
        ], 'struct');

        $req = new \PhpXmlRpc\Request('execute_kw', [
            new \PhpXmlRpc\Value($this->db, "string"),
            new \PhpXmlRpc\Value($this->uid, "int"),
            new \PhpXmlRpc\Value($this->password, "string"),
            new \PhpXmlRpc\Value('ir.model', "string"),
            new \PhpXmlRpc\Value('search_read', "string"),
            new \PhpXmlRpc\Value([new \PhpXmlRpc\Value($domain, 'array')], "array"),
            $kwargs
        ]);

        $response = $client->send($req);
        if ($response->faultCode()) return [];

        $encoder = new \PhpXmlRpc\Encoder();
        return $encoder->decode($response->value());
    }

    /**
     * Intip semua kolom (fields) yang ada di dalam sebuah Model Odoo
     */
    public function getModelFields(string $modelName): array
    {
        if (!$this->uid && !$this->authenticate()) return [];

        $client = new \PhpXmlRpc\Client($this->url . '/xmlrpc/2/object');

        $req = new \PhpXmlRpc\Request('execute_kw', [
            new \PhpXmlRpc\Value($this->db, "string"),
            new \PhpXmlRpc\Value($this->uid, "int"),
            new \PhpXmlRpc\Value($this->password, "string"),
            new \PhpXmlRpc\Value($modelName, "string"),
            new \PhpXmlRpc\Value('fields_get', "string"),
            new \PhpXmlRpc\Value([], "array"),
            new \PhpXmlRpc\Value([
                'attributes' => new \PhpXmlRpc\Value([
                    new \PhpXmlRpc\Value('string', 'string'),
                    new \PhpXmlRpc\Value('type', 'string'),
                ], 'array')
            ], 'struct')
        ]);

        $response = $client->send($req);
        if ($response->faultCode()) return [];

        $encoder = new \PhpXmlRpc\Encoder();
        return $encoder->decode($response->value());
    }

/**
     * Ambil Produk Siap Jual (Saleable) & Stoknya
     */
    public function getProductsWithStock(int $limit = 20, string $search = ''): array
    {
        if (!$this->uid && !$this->authenticate()) {
            return [];
        }

        $client = new \PhpXmlRpc\Client($this->url . '/xmlrpc/2/object');

        // Filter: Hanya produk aktif DAN termasuk kategori Saleable (ID: 2) beserta anak kategorinya
        $domain = [
            new \PhpXmlRpc\Value([
                new \PhpXmlRpc\Value('sale_ok', 'string'),
                new \PhpXmlRpc\Value('=', 'string'),
                new \PhpXmlRpc\Value(true, 'boolean')
            ], 'array'),
            new \PhpXmlRpc\Value([
                new \PhpXmlRpc\Value('categ_id', 'string'),
                new \PhpXmlRpc\Value('child_of', 'string'),
                new \PhpXmlRpc\Value(2, 'int') // ID 2 = All / Saleable
            ], 'array'),
        ];

        // Filter pencarian opsional berdasarkan nama
        if (!empty($search)) {
            $domain[] = new \PhpXmlRpc\Value([
                new \PhpXmlRpc\Value('name', 'string'),
                new \PhpXmlRpc\Value('ilike', 'string'),
                new \PhpXmlRpc\Value($search, 'string')
            ], 'array');
        }

        $kwargs = new \PhpXmlRpc\Value([
            'fields' => new \PhpXmlRpc\Value([
                new \PhpXmlRpc\Value('id', 'string'),
                new \PhpXmlRpc\Value('display_name', 'string'),
                new \PhpXmlRpc\Value('default_code', 'string'),
                new \PhpXmlRpc\Value('lst_price', 'string'),
                new \PhpXmlRpc\Value('qty_available', 'string'),
                new \PhpXmlRpc\Value('free_qty', 'string'),
                new \PhpXmlRpc\Value('uom_name', 'string'),
                new \PhpXmlRpc\Value('categ_id', 'string'),
            ], 'array'),
            'limit' => new \PhpXmlRpc\Value($limit, 'int')
        ], 'struct');

        $req = new \PhpXmlRpc\Request('execute_kw', [
            new \PhpXmlRpc\Value($this->db, "string"),
            new \PhpXmlRpc\Value($this->uid, "int"),
            new \PhpXmlRpc\Value($this->password, "string"),
            new \PhpXmlRpc\Value('product.product', "string"),
            new \PhpXmlRpc\Value('search_read', "string"),
            new \PhpXmlRpc\Value([new \PhpXmlRpc\Value($domain, 'array')], "array"),
            $kwargs
        ]);

        $response = $client->send($req);
        if ($response->faultCode()) {
            return [];
        }

        $encoder = new \PhpXmlRpc\Encoder();
        return $encoder->decode($response->value());
    }
    /**
     * Intip daftar Kategori Produk yang ada di Odoo
     */
    public function getProductCategories(): array
    {
        if (!$this->uid && !$this->authenticate()) return [];

        $client = new \PhpXmlRpc\Client($this->url . '/xmlrpc/2/object');

        $kwargs = new \PhpXmlRpc\Value([
            'fields' => new \PhpXmlRpc\Value([
                new \PhpXmlRpc\Value('id', 'string'),
                new \PhpXmlRpc\Value('name', 'string'),
                new \PhpXmlRpc\Value('complete_name', 'string'),
            ], 'array'),
            'limit' => new \PhpXmlRpc\Value(50, 'int')
        ], 'struct');

        $req = new \PhpXmlRpc\Request('execute_kw', [
            new \PhpXmlRpc\Value($this->db, "string"),
            new \PhpXmlRpc\Value($this->uid, "int"),
            new \PhpXmlRpc\Value($this->password, "string"),
            new \PhpXmlRpc\Value('product.category', "string"),
            new \PhpXmlRpc\Value('search_read', "string"),
            new \PhpXmlRpc\Value([new \PhpXmlRpc\Value([], 'array')], "array"),
            $kwargs
        ]);

        $response = $client->send($req);
        if ($response->faultCode()) return [];

        $encoder = new \PhpXmlRpc\Encoder();
        return $encoder->decode($response->value());
    }
}