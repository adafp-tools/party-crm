<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [];
        $status = 'ok';

        // Database
        try {
            DB::select('SELECT 1');
            $checks['database'] = 'ok';
        } catch (\Throwable) {
            $checks['database'] = 'error';
            $status = 'degraded';
        }

        // Cache
        try {
            Cache::put('_health_check', 1, 5);
            $checks['cache'] = Cache::get('_health_check') === 1 ? 'ok' : 'error';
        } catch (\Throwable) {
            $checks['cache'] = 'error';
            $status = 'degraded';
        }

        // Disk (storage writable)
        try {
            Storage::put('_health_check.txt', '1');
            Storage::delete('_health_check.txt');
            $checks['disk'] = 'ok';
        } catch (\Throwable) {
            $checks['disk'] = 'error';
            $status = 'degraded';
        }

        // Queue (check if configured; can't verify worker without pinging)
        $checks['queue_driver'] = config('queue.default', 'sync');

        $httpStatus = $status === 'ok' ? 200 : 503;

        return response()->json([
            'status' => $status,
            'checks' => $checks,
            'version' => config('app.version', '1.0.0'),
            'timestamp' => now()->toIso8601String(),
        ], $httpStatus);
    }
}
