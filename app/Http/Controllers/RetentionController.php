<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Process;

class RetentionController extends Controller
{
    public function getRetentionData(): JsonResponse
    {
        $pythonPath = base_path('venv/Scripts/python.exe'); 
        $scriptPath = base_path('scripts/get_retention_json.py');

        $result = Process::env([
            'SystemRoot'  => getenv('SystemRoot') ?: 'C:\\Windows',
            'SystemDrive' => getenv('SystemDrive') ?: 'C:',
            'PATH'        => getenv('PATH'),
            'TEMP'        => getenv('TEMP'),
        ])->run("\"{$pythonPath}\" \"{$scriptPath}\"");

        if ($result->failed()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menjalankan script Python',
                'error'   => $result->errorOutput() ?: $result->output()
            ], 500);
        }

        $data = json_decode($result->output(), true);

        return response()->json($data);
    }
}