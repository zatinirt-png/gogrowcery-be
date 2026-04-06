<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

Route::get('/health', function () {
    $status = [
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'services' => [
            'database' => 'unknown',
            'redis' => 'unknown',
        ],
    ];

    // Cek koneksi database
    try {
        DB::connection()->getPdo();
        $status['services']['database'] = 'connected';
    } catch (\Exception $e) {
        $status['services']['database'] = 'error: ' . $e->getMessage();
        $status['status'] = 'degraded';
    }

    // Cek koneksi Redis
    try {
        Cache::store('redis')->put('health_check', true, 10);
        $status['services']['redis'] = 'connected';
    } catch (\Exception $e) {
        $status['services']['redis'] = 'error: ' . $e->getMessage();
        $status['status'] = 'degraded';
    }

    return response()->json($status);
});