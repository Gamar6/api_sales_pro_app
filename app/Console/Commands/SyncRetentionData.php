<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class SyncRetentionData extends Command
{
    protected $signature = 'retention:sync';
    protected $description = 'Jalankan script Python untuk sync data retensi dari Odoo';

    public function handle()
    {
        $this->info('Memproses data retensi Odoo...');

        // Jalankan script extract & transform
        $pythonPath = 'python';
        $scriptExtract = base_path('python_engine/aplikasi/extract_sales.py');
        $scriptTransform = base_path('python_engine/aplikasi/transform_retensi.py');

        $res1 = Process::run("{$pythonPath} \"{$scriptExtract}\"");
        if ($res1->failed()) {
            $this->error('Gagal extract: ' . $res1->errorOutput());
            return 1;
        }

        $res2 = Process::run("{$pythonPath} \"{$scriptTransform}\"");
        if ($res2->failed()) {
            $this->error('Gagal transform: ' . $res2->errorOutput());
            return 1;
        }

        $this->info('Sync retensi berhasil!');
        return 0;
    }
}