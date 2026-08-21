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

        $client = new Client($this->url . '/xmlrpc/2/common');
        $client = new Client($this->url . '/xmlrpc/2/object');

        $domain = [];
        if (!empty($search)) {
            $domain[] = new Value([
                new Value('model', 'string'),
                new Value('like', 'string'),
                new Value($search, 'string')
            ], 'array');
        }

        $kwargs = new Value([
            'fields' => new Value([
                new Value('model', 'string'),
                new Value('name', 'string'),
            ], 'array'),
            'limit' => new Value(10, 'int')
        ], 'struct');

        $req = new Request('execute_kw', [
            new Value($this->db, "string"),
            new Value($this->uid, "int"),
            new Value($this->password, "string"),
            new Value('ir.model', "string"),
            new Value('search_read', "string"),
            new Value([new Value($domain, 'array')], "array"),
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

        $client = new Client($this->url . '/xmlrpc/2/object');

        $req = new Request('execute_kw', [
            new Value($this->db, "string"),
            new Value($this->uid, "int"),
            new Value($this->password, "string"),
            new Value($modelName, "string"),
            new Value('fields_get', "string"),
            new Value([], "array"),
            new Value([
                'attributes' => new Value([
                    new Value('string', 'string'),
                    new Value('type', 'string'),
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

        $client = new Client($this->url . '/xmlrpc/2/object');

        // Filter: Hanya produk aktif DAN termasuk kategori Saleable (ID: 2) beserta anak kategorinya
        $domain = [
            new Value([
                new Value('sale_ok', 'string'),
                new Value('=', 'string'),
                new Value(true, 'boolean')
            ], 'array'),
            new Value([
                new Value('categ_id', 'string'),
                new Value('child_of', 'string'),
                new Value(2, 'int') // ID 2 = All / Saleable
            ], 'array'),
        ];

        // Filter pencarian opsional berdasarkan nama
        if (!empty($search)) {
            $domain[] = new Value([
                new Value('name', 'string'),
                new Value('ilike', 'string'),
                new Value($search, 'string')
            ], 'array');
        }

        $kwargs = new Value([
            'fields' => new Value([
                new Value('id', 'string'),
                new Value('display_name', 'string'),
                new Value('default_code', 'string'),
                new Value('lst_price', 'string'),
                new Value('qty_available', 'string'),
                new Value('free_qty', 'string'),
                new Value('uom_name', 'string'),
                new Value('categ_id', 'string'),
            ], 'array'),
            'limit' => new Value($limit, 'int')
        ], 'struct');

        $req = new Request('execute_kw', [
            new Value($this->db, "string"),
            new Value($this->uid, "int"),
            new Value($this->password, "string"),
            new Value('product.product', "string"),
            new Value('search_read', "string"),
            new Value([new Value($domain, 'array')], "array"),
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

        $client = new Client($this->url . '/xmlrpc/2/object');

        $kwargs = new Value([
            'fields' => new Value([
                new Value('id', 'string'),
                new Value('name', 'string'),
                new Value('complete_name', 'string'),
            ], 'array'),
            'limit' => new Value(50, 'int')
        ], 'struct');

        $req = new Request('execute_kw', [
            new Value($this->db, "string"),
            new Value($this->uid, "int"),
            new Value($this->password, "string"),
            new Value('product.category', "string"),
            new Value('search_read', "string"),
            new Value([new Value([], 'array')], "array"),
            $kwargs
        ]);

        $response = $client->send($req);
        if ($response->faultCode()) return [];

        $encoder = new \PhpXmlRpc\Encoder();
        return $encoder->decode($response->value());
    }

    /**
     * Ambil sampel data Toko/Customer dari model res.partner
     */
    public function getStores(): array
    {
        if (!$this->uid && !$this->authenticate()) return [];

        $client = new Client($this->url . '/xmlrpc/2/object');
        $encoder = new \PhpXmlRpc\Encoder();

        // 1. Domain Kosong (Ambil semua data)
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
        
        // 2. Tentukan field yang ingin diambil & limit
        $kwargs = [
            'fields' => [
                'id', 
                'name', 
                'display_name', 
                'phone', 
                'city', 
                'street',
                'partner_latitude',  // Latitude Odoo
                'partner_longitude',  // Longitude Odoo
            ],
        ];

        // 3. Encode otomatis semua parameter ke format XML-RPC
        $req = new Request('execute_kw', [
            $encoder->encode($this->db),
            $encoder->encode($this->uid),
            $encoder->encode($this->password),
            $encoder->encode('res.partner'),
            $encoder->encode('search_read'),
            $encoder->encode([$domain]), // Array di dalam array: [ [] ]
            $encoder->encode($kwargs),
        ]);

        $response = $client->send($req);

        // Jika terjadi error dari server Odoo, tampilkan alasannya di terminal
        if ($response->faultCode()) {
            dump("Odoo Error Code: " . $response->faultCode());
            dump("Odoo Error Message: " . $response->faultString());
            return [];
        }

        return $encoder->decode($response->value()) ?? [];
    }

    /**
     * Hitung total jumlah toko/partner yang ada di Odoo
     */
    public function getStoresCount(): int
    {
        if (!$this->uid && !$this->authenticate()) return 0;

        $client = new Client($this->url . '/xmlrpc/2/object');
        $encoder = new \PhpXmlRpc\Encoder();

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
        $req = new Request('execute_kw', [
            $encoder->encode($this->db),
            $encoder->encode($this->uid),
            $encoder->encode($this->password),
            $encoder->encode('res.partner'),
            $encoder->encode('search_count'),
            $encoder->encode([$domain]),
        ]);

        $response = $client->send($req);

        if ($response->faultCode()) {
            dump("Odoo Error: " . $response->faultString());
            return 0;
        }

        return $encoder->decode($response->value()) ?? 0;
    }

    /**
     * Ambil produk yang pernah terjual DAN statusnya masih aktif dijual
     */
    public function getActiveSoldProducts(int $limit = 20): array
    {
        if (!$this->uid && !$this->authenticate()) {
            return [];
        }

        $client = new Client($this->url . '/xmlrpc/2/object');
        $encoder = new \PhpXmlRpc\Encoder();

        $domain = [
            ['state', 'in', ['sale', 'done']], 
            ['product_id.active', '=', true],
        ];

        $kwargs = [
            'fields' => [
                'id',
                'product_id',   // Berisi [id, nama_produk]
                'product_uom_qty', // Jumlah yang terjual
                'price_unit',
                'order_id',     // Referensi ke nomor Sales Order
            ],
            'limit' => $limit,
        ];

        $req = new Request('execute_kw', [
            $encoder->encode($this->db),
            $encoder->encode($this->uid),
            $encoder->encode($this->password),
            $encoder->encode('sale.order.line'), // Model baris pesanan penjualan
            $encoder->encode('search_read'),
            $encoder->encode([$domain]),
            $encoder->encode($kwargs),
        ]);

        $response = $client->send($req);

        if ($response->faultCode()) {
            dump("Odoo Error: " . $response->faultString());
            return [];
        }

        return $encoder->decode($response->value()) ?? [];
    }

    /**
     * Ambil produk terjual dan stoknya khusus untuk perusahaan CV (Company ID: 1)
     */
    public function getActiveSoldProductsForCV(int $companyId = 1, int $limit = 10): array
    {
        if (!$this->uid && !$this->authenticate()) {
            return [];
        }

        $client = new Client($this->url . '/xmlrpc/2/object');
        $encoder = new \PhpXmlRpc\Encoder();

        // 1. Ambil ID produk unik yang pernah terjual (status sale/done & aktif)
        $domainLine = [
            ['state', 'in', ['sale', 'done']],
            ['product_id.active', '=', true],
        ];

        $reqLine = new Request('execute_kw', [
            $encoder->encode($this->db ?? $this->db), // sesuaikan dengan properti kamu
            $encoder->encode($this->uid),
            $encoder->encode($this->password),
            $encoder->encode('sale.order.line'),
            $encoder->encode('search_read'),
            $encoder->encode([$domainLine]),
            $encoder->encode([
                'fields' => ['product_id'],
                'limit' => $limit * 3
            ]),
        ]);

        $resLine = $client->send($reqLine);
        if ($resLine->faultCode()) return [];

        $soldLines = $encoder->decode($resLine->value()) ?? [];
        
        $productIds = [];
        foreach ($soldLines as $line) {
            if (isset($line['product_id'][0])) {
                $productIds[] = $line['product_id'][0];
            }
        }
        $productIds = array_unique($productIds);

        if (empty($productIds)) {
            return [];
        }

        // 2. Ambil stok dari stock.quant khusus lokasi yang perusahaannya adalah CV (company_id = 1)
        $domainQuant = [
            ['product_id', 'in', array_values($productIds)],
            ['company_id', '=', $companyId], // Filter langsung berdasarkan company CV
        ];

        $kwargsQuant = [
            'fields' => ['product_id', 'location_id', 'quantity', 'reserved_quantity', 'available_quantity'],
            'limit' => $limit,
        ];

        $reqQuant = new Request('execute_kw', [
            $encoder->encode($this->db),
            $encoder->encode($this->uid),
            $encoder->encode($this->password),
            $encoder->encode('stock.quant'),
            $encoder->encode('search_read'),
            $encoder->encode([$domainQuant]),
            $encoder->encode($kwargsQuant),
        ]);

        $resQuant = $client->send($reqQuant);
        if ($resQuant->faultCode()) {
            return [];
        }

        return $encoder->decode($resQuant->value()) ?? [];
    }

    /**
     * Ambil daftar lokasi gudang untuk mencari ID lokasi CV
     */
    public function getLocations(): array
    {
        if (!$this->uid && !$this->authenticate()) return [];

        $client = new Client($this->url . '/xmlrpc/2/object');
        $encoder = new \PhpXmlRpc\Encoder();

        $domain = [
            ['usage', '=', 'internal'] // Hanya ambil lokasi bertipe internal (gudang)
        ];

        $kwargs = [
            'fields' => ['id', 'name', 'complete_name', 'company_id'],
            'limit' => 50
        ];

        $req = new Request('execute_kw', [
            $encoder->encode($this->db),
            $encoder->encode($this->uid),
            $encoder->encode($this->password),
            $encoder->encode('stock.location'),
            $encoder->encode('search_read'),
            $encoder->encode([$domain]),
            $encoder->encode($kwargs),
        ]);

        $response = $client->send($req);
        if ($response->faultCode()) return [];

        return $encoder->decode($response->value()) ?? [];
    }

    /**
     * Ambil produk terjual dan stok khususnya dari lokasi CV tertentu
     */
    public function getActiveSoldProductsFromCV(int $cvLocationId, int $limit = 10): array
    {
        if (!$this->uid && !$this->authenticate()) {
            return [];
        }

        $client = new Client($this->url . '/xmlrpc/2/object');
        $encoder = new \PhpXmlRpc\Encoder();

        // 1. Ambil ID produk unik yang pernah terjual (status sale/done & aktif)
        $domainLine = [
            ['state', 'in', ['sale', 'done']],
            ['product_id.active', '=', true],
        ];

        $reqLine = new Request('execute_kw', [
            $encoder->encode($this->db),
            $encoder->encode($this->uid),
            $encoder->encode($this->password),
            $encoder->encode('sale.order.line'),
            $encoder->encode('search_read'),
            $encoder->encode([$domainLine]),
            $encoder->encode([
                'fields' => ['product_id'],
                'limit' => $limit * 3
            ]),
        ]);

        $resLine = $client->send($reqLine);
        if ($resLine->faultCode()) return [];

        $soldLines = $encoder->decode($resLine->value()) ?? [];
        
        $productIds = [];
        foreach ($soldLines as $line) {
            if (isset($line['product_id'][0])) {
                $productIds[] = $line['product_id'][0];
            }
        }
        $productIds = array_unique($productIds);

        if (empty($productIds)) {
            return [];
        }

        // 2. Ambil stok produk tersebut khusus dari stock.quant berdasarkan lokasi CV
        $domainQuant = [
            ['product_id', 'in', array_values($productIds)],
            ['location_id', '=', $cvLocationId],
        ];

        $kwargsQuant = [
            'fields' => ['product_id', 'quantity', 'reserved_quantity', 'available_quantity'],
            'limit' => $limit,
        ];

        $reqQuant = new Request('execute_kw', [
            $encoder->encode($this->db),
            $encoder->encode($this->uid),
            $encoder->encode($this->password),
            $encoder->encode('stock.quant'),
            $encoder->encode('search_read'),
            $encoder->encode([$domainQuant]),
            $encoder->encode($kwargsQuant),
        ]);

        $resQuant = $client->send($reqQuant);
        if ($resQuant->faultCode()) {
            return [];
        }

        return $encoder->decode($resQuant->value()) ?? [];
    }
}