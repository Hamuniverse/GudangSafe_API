<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActuatorLog;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ActuatorController extends Controller
{

    //  GET /api/actuator/status
    //  Status kipas terkini berdasarkan log terakhir.

    public function status(): JsonResponse
    {
        $latest = ActuatorLog::where('device', 'fan')
            ->latest('executed_at')
            ->first();

        return response()->json([
            'success' => true,
            'data'    => [
                'device'       => 'fan',
                'is_on'        => $latest?->command === 'on',
                'last_command' => $latest?->command,
                'last_updated' => $latest?->executed_at?->toISOString(),
                'triggered_by' => $latest?->triggered_by,
            ],
        ]);
    }


    // POST /api/actuator/control
    // Mengirim perintah ON/OFF ke kipas via MQTT.

    public function control(Request $request): JsonResponse
    {
        $request->validate([
            'command' => 'required|in:on,off',
            'note'    => 'nullable|string|max:255',
        ]);

        $user = $request->user();

        // Simpan log
        $log = ActuatorLog::create([
            'device'       => 'fan',
            'command'      => $request->command,
            'status'       => 'pending',
            'triggered_by' => 'manual',
            'user_id'      => $user->id,
            'note'         => $request->note,
            'executed_at'  => now(),
            'created_at'   => now(),
        ]);

        // Publish ke MQTT langsung tanpa Facade
        try {
            $mqtt = new \PhpMqtt\Client\MqttClient(
                env('MQTT_HOST', '127.0.0.1'),
                (int) env('MQTT_PORT', 1883),
                'gudangsafe-actuator-' . uniqid()
            );

            $mqtt->connect();

            $payload = json_encode([
                'command'   => $request->command,
                'log_id'    => $log->id,
                'timestamp' => now()->toISOString(),
            ]);

            $mqtt->publish('gudang/actuator/kipas', $payload);
            $mqtt->disconnect();

            Log::info('MQTT publish success: ' . $payload);
        } catch (\Exception $e) {
            Log::warning('MQTT publish gagal (ESP32 mungkin belum terhubung): ' . $e->getMessage());
        }

        // Update status ke success (terlepas dari MQTT berhasil atau tidak)
        $log->update(['status' => 'success']);

        // Kirim notifikasi ke semua user aktif
        $this->createActuatorNotification($log, $user->name);

        return response()->json([
            'success' => true,
            'message' => 'Perintah berhasil disimpan.',
            'data'    => [
                'log_id'  => $log->id,
                'command' => $log->command,
                'status'  => 'success',
            ],
        ]);
    }

    /**
     * GET /api/actuator/log
     * Riwayat semua perintah aktuator.
     */
    public function log(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:200',
        ]);

        $logs = ActuatorLog::with('user:id,name,role')
            ->orderByDesc('executed_at')
            ->limit($request->input('limit', 50))
            ->get();

        return response()->json([
            'success' => true,
            'count'   => $logs->count(),
            'data'    => $logs->map(fn($log) => [
                'id'           => $log->id,
                'device'       => $log->device,
                'command'      => $log->command,
                'status'       => $log->status,
                'triggered_by' => $log->triggered_by,
                'note'         => $log->note,
                'executed_at'  => $log->executed_at->toISOString(),
                'user'         => $log->user ? [
                    'id'   => $log->user->id,
                    'name' => $log->user->name,
                ] : null,
            ]),
        ]);
    }

    // Private Helper

    private function createActuatorNotification(ActuatorLog $log, string $userName): void
    {
        $action = $log->command === 'on' ? 'dinyalakan' : 'dimatikan';
        $users  = \App\Models\User::where('is_active', true)->get();

        foreach ($users as $u) {
            Notification::create([
                'user_id'         => $u->id,
                'title'           => 'Kipas ' . ucfirst($action),
                'message'         => "Kipas telah {$action} secara manual oleh {$userName}.",
                'type'            => 'actuator',
                'actuator_log_id' => $log->id,
                'is_read'         => false,
                'created_at'      => now(),
            ]);
        }
    }
}
