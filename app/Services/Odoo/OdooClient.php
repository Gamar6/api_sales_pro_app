<?php
    namespace App\Services\Odoo;

    use PhpXmlRpc\Client;
    use PhpXmlRpc\Request;
    use PhpXmlRpc\Value;
    use PhpXmlRpc\Encoder;
    use Illuminate\Support\Facades\Log;

    class OdooClient
    {
        protected string $url;
        protected string $db;
        protected string $username;
        protected string $password;
        protected ?int $uid = null;
        protected Encoder $encoder;

        public function __construct()
        {
            $this->url = config('services.odoo.url', env('ODOO_URL'));
            $this->db = config('services.odoo.db', env('ODOO_DB'));
            $this->username = config('services.odoo.username', env('ODOO_USERNAME'));
            $this->password = config('services.odoo.password', env('ODOO_PASSWORD'));
            $this->encoder = new Encoder();
        }

        public function authenticate(): bool
        {
            if ($this->uid) return true;

            try {
                $client = new Client($this->url . '/xmlrpc/2/common');
                $req = new Request('authenticate', [
                    new Value($this->db, "string"),
                    new Value($this->username, "string"),
                    new Value($this->password, "string"),
                    new Value([], "array"),
                ]);

                $response = $client->send($req);
                if ($response->faultCode()) return false;

                $this->uid = $response->value()->scalarval();
                return is_int($this->uid) && $this->uid > 0;
            } catch (\Exception $e) {
                Log::error("Odoo Auth Error: " . $e->getMessage());
                return false;
            }
        }

        public function executeKw(string $model, string $method, array $args = [], array $kwargs = [])
        {
            if (!$this->authenticate()) return [];

            $client = new Client($this->url . '/xmlrpc/2/object');
            $params = [
                $this->encoder->encode($this->db),
                $this->encoder->encode($this->uid),
                $this->encoder->encode($this->password),
                $this->encoder->encode($model),
                $this->encoder->encode($method),
                $this->encoder->encode($args),
            ];

            if (!empty($kwargs)) {
                $params[] = $this->encoder->encode($kwargs);
            }

            $response = $client->send(new Request('execute_kw', $params));

            if ($response->faultCode()) {
                Log::error("Odoo XML-RPC Error [{$model}@{$method}]: " . $response->faultString());
                return [];
            }

            return $this->encoder->decode($response->value()) ?? [];
        }
    }