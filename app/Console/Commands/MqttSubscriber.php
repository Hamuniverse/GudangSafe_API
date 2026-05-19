<?php

namespace App\Console\Commands;

use App\Models\AnomalyLog;
use App\Models\ActuatorLog;
use App\Models\Notification;
use App\Models\SensorData;
use App\Models\Threshold;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

class MqttSubscriber extends Command
{
    protected $signature   = 'mqtt:subscribe';
    protected $description = 'Menjalankan MQTT subscriber untuk menerima data dari ESP32';

    public function handle(): void
    {
        $host     = env('MQTT_HOST', '127.0.0.1');
        $port     = (int) env('MQTT_PORT', 1883);
        $clientId = env('MQTT_CLIENT_ID', 'gudangsafe-laravel');

        $this->info('GudangSafe MQTT Subscriber dimulai...');
        $this->info("Broker : {$host}:{$port}");
        $this->info('Topics : gudang/sensor, gudang/status, gudang/actuator/status');
        $this->newLine();

        try {
            $client = new MqttClient($host, $port, $clientId);

            $settings = (new ConnectionSettings())
                ->setKeepAliveInterval(60);

            $client->connect($settings, true);
            $this->info('✅ Terhubung ke MQTT Broker.');

            // Data sensor DHT22
            $client->subscribe('gudang/sensor', function (string $topic, string $message) {
                $this->processSensorData($message);
            }, MqttClient::QOS_AT_LEAST_ONCE);

            // Status kondisi gudang dari ESP32
            $client->subscribe('gudang/status', function (string $topic, string $message) {
                $this->info("[gudang/status] {$message}");
                Log::info('MQTT gudang/status: ' . $message);
            }, MqttClient::QOS_AT_LEAST_ONCE);

            // Feedback status aktuator dari ESP32
            $client->subscribe('gudang/actuator/status', function (string $topic, string $message) {
                $this->processActuatorStatus($message);
            }, MqttClient::QOS_AT_LEAST_ONCE);

            $this->info('Mendengarkan pesan MQTT... (Ctrl+C untuk berhenti)');
            $this->newLine();

            // Loop terus sampai dihentikan manual
            $client->loop(allowSleep: true);

        } catch (\Exception $e) {
            $this->error('❌ MQTT Error: ' . $e->getMessage());
            Log::error('MQTT Subscriber error: ' . $e->getMessage());
        }
    }

    // Handler: gudang/sensor
    private function processSensorData(string $message): void
    {
        $this->line("[gudang/sensor] {$message}");

        $data = json_decode($message, true);

        if (!isset($data['temperature'], $data['humidity'])) {
            $this->warn('Format payload sensor tidak valid, diabaikan.');
            return;
        }

        if (isset($data['device_id'])) {
            $this->line("Device: {$data['device_id']}");
        }

        $temperature = (float) $data['temperature'];
        $humidity    = (float) $data['humidity'];
        $recordedAt  = now();

        // Ambil threshold aktif
        $threshold = Threshold::current();

        // Hitung status kondisi gudang
        $status = SensorData::calculateStatus($temperature, $humidity, $threshold);

        // Simpan ke database
        $sensor = SensorData::create([
            'temperature' => $temperature,
            'humidity'    => $humidity,
            'status'      => $status,
            'recorded_at' => $recordedAt,
            'created_at'  => now(),
        ]);

        $this->info("Tersimpan — Suhu: {$temperature}°C | Kelembaban: {$humidity}% | Status: {$status}");

        // Proses anomali jika status bukan normal
        if ($status !== 'normal') {
            $this->processAnomaly($sensor, $temperature, $humidity, $threshold);
        } else {
            // Resolve anomali yang belum selesai jika kondisi kembali normal
            $this->resolveOpenAnomalies();
        }
    }

    // Handler: gudang/actuator/status
    private function processActuatorStatus(string $message): void
    {
        $this->line("[gudang/actuator/status] {$message}");

        $data = json_decode($message, true);

        // Format dari ESP32
        if (isset($data['fan'])) {
            $status = $data['fan'] === 'on' ? 'on' : 'off';
            $this->info("Status kipas dari ESP32: {$status}");

            // Update log aktuator terakhir yang masih pending
            ActuatorLog::where('device', 'fan')
                ->where('status', 'pending')
                ->latest('executed_at')
                ->first()
                ?->update(['status' => 'success']);
            return;
        }

        if (!isset($data['log_id'], $data['status'])) {
            $this->warn('Format payload aktuator tidak valid, diabaikan.');
            return;
        }

        $log = ActuatorLog::find($data['log_id']);

        if (!$log) {
            $this->warn("ActuatorLog ID {$data['log_id']} tidak ditemukan.");
            return;
        }

        $log->update(['status' => $data['status']]);

        $statusIcon = $data['status'] === 'success' ? '✅' : '❌';
        $this->info("   {$statusIcon} Aktuator {$log->device} — {$log->command} → {$data['status']}");

        Log::info("MQTT actuator/status: log_id={$data['log_id']} status={$data['status']}");
    }

    // ─────────────────────────────────────────────────────────────────────
    // Proses dan simpan anomali
    // ─────────────────────────────────────────────────────────────────────
    private function processAnomaly(
        SensorData $sensor,
        float $temperature,
        float $humidity,
        Threshold $threshold
    ): void {
        $anomalies = [];

        // Cek anomali suhu
        if ($temperature < $threshold->temp_min) {
            $anomalies[] = [
                'type'            => 'temp_low',
                'value'           => $temperature,
                'threshold_value' => $threshold->temp_min,
            ];
        } elseif ($temperature > $threshold->temp_max) {
            $anomalies[] = [
                'type'            => 'temp_high',
                'value'           => $temperature,
                'threshold_value' => $threshold->temp_max,
            ];
        }

        // Cek anomali kelembaban
        if ($humidity < $threshold->humidity_min) {
            $anomalies[] = [
                'type'            => 'humidity_low',
                'value'           => $humidity,
                'threshold_value' => $threshold->humidity_min,
            ];
        } elseif ($humidity > $threshold->humidity_max) {
            $anomalies[] = [
                'type'            => 'humidity_high',
                'value'           => $humidity,
                'threshold_value' => $threshold->humidity_max,
            ];
        }

        foreach ($anomalies as $anomalyData) {
            $severity = AnomalyLog::determineSeverity(
                $anomalyData['value'],
                $anomalyData['threshold_value'],
                $anomalyData['type']
            );

            $anomaly = AnomalyLog::create([
                'sensor_data_id'  => $sensor->id,
                'type'            => $anomalyData['type'],
                'value'           => $anomalyData['value'],
                'threshold_value' => $anomalyData['threshold_value'],
                'severity'        => $severity,
                'is_resolved'     => false,
                'created_at'      => now(),
            ]);

            $this->warn("Anomali: {$anomalyData['type']} = {$anomalyData['value']} (severity: {$severity})");

            // Kirim notifikasi ke semua user aktif
            $this->sendAnomalyNotification($anomaly, $anomalyData['type'], $anomalyData['value'], $severity);
        }
    }

    // Kirim notifikasi anomali ke semua user aktif
    private function sendAnomalyNotification(
        AnomalyLog $anomaly,
        string $type,
        float $value,
        string $severity
    ): void {
        $labels = [
            'temp_high'     => ['title' => '🌡️ Suhu Terlalu Tinggi',   'unit' => '°C'],
            'temp_low'      => ['title' => '🌡️ Suhu Terlalu Rendah',   'unit' => '°C'],
            'humidity_high' => ['title' => '💧 Kelembaban Terlalu Tinggi', 'unit' => '%'],
            'humidity_low'  => ['title' => '💧 Kelembaban Terlalu Rendah', 'unit' => '%'],
        ];

        $label   = $labels[$type];
        $title   = $label['title'];
        $unit    = $label['unit'];
        $message = "Nilai saat ini: {$value}{$unit}. Severity: {$severity}. Segera periksa kondisi gudang.";

        $users = User::where('is_active', true)->get();

        foreach ($users as $user) {
            Notification::create([
                'user_id'        => $user->id,
                'title'          => $title,
                'message'        => $message,
                'type'           => 'anomaly',
                'anomaly_log_id' => $anomaly->id,
                'is_read'        => false,
                'created_at'     => now(),
            ]);
        }

        $this->info("   📲 Notifikasi dikirim ke " . $users->count() . " user.");
    }

    // Tandai anomali lama sebagai resolved jika kondisi kembali normal
    private function resolveOpenAnomalies(): void
    {
        $resolved = AnomalyLog::where('is_resolved', false)
            ->update([
                'is_resolved' => true,
                'resolved_at' => now(),
            ]);

        if ($resolved > 0) {
            $this->info("   ✅ {$resolved} anomali ditandai resolved (kondisi kembali normal).");
        }
    }
}
