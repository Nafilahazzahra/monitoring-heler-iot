<?php

namespace App\Console\Commands;

use App\Models\SensorReading;
use App\Services\TelegramAlertService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Console\Command;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\Exceptions\MqttClientException;
use PhpMqtt\Client\MqttClient;
use Throwable;

class MqttListenCommand extends Command
{
    protected $signature = 'mqtt:listen';

    protected $description = 'Listen to MQTT sensor data and store it in the database';

    public function handle(): int
    {
        $host = env('MQTT_HOST', '127.0.0.1');
        $port = (int) env('MQTT_PORT', 1883);
        $topic = env('MQTT_TOPIC', 'heler/sensor');
        $clientId = substr(env('MQTT_CLIENT_ID', 'lsub'), 0, 15) . '_' . substr(md5(uniqid()), 0, 6);
        $cleanSession = filter_var(env('MQTT_CLEAN_SESSION', true), FILTER_VALIDATE_BOOL);

        $this->info("Connecting to MQTT {$host}:{$port} on topic {$topic}");

        try {
            $telegramAlertService = app(TelegramAlertService::class);
            $mqtt = new MqttClient($host, $port, $clientId);
            $settings = (new ConnectionSettings())
                ->setKeepAliveInterval((int) env('MQTT_KEEP_ALIVE', 60))
                ->setConnectTimeout((int) env('MQTT_TIMEOUT', 5));

            $username = trim((string) env('MQTT_USERNAME', ''));
            $password = (string) env('MQTT_PASSWORD', '');

            if ($username !== '') {
                $settings = $settings->setUsername($username)->setPassword($password);
            }

            $mqtt->connect($settings, $cleanSession);
            $mqtt->subscribe($topic, function (string $topicName, string $message) use ($telegramAlertService) {
                $payload = json_decode($message, true);

                if (! is_array($payload)) {
                    $this->warn("Payload invalid from {$topicName}: {$message}");
                    return;
                }

                $temperature = (float) ($payload['temperature'] ?? 0);
                $flowRate = (float) ($payload['flow_rate'] ?? 0);
                $flowVelocity = (float) ($payload['flow_velocity'] ?? $this->calculateFlowVelocity($flowRate));
                $flowStatus = (string) ($payload['flow_status'] ?? $this->resolveFlowStatus($flowVelocity));
                $waterLevel = (float) ($payload['water_level'] ?? 0);
                $waterHeightCm = isset($payload['water_height_cm']) ? (float) $payload['water_height_cm'] : null;
                $distanceCm = isset($payload['distance_cm']) ? (float) $payload['distance_cm'] : null;
                $vibration = $this->normalizeVibrationStatus((string) ($payload['vibration_status'] ?? 'Tidak Bergetar'));
                $status = (string) ($payload['status'] ?? $this->resolveStatus($vibration));
                $recordedAt = $payload['recorded_at'] ?? now();

                $normalizedPayload = array_merge($payload, [
                    'temperature' => $temperature,
                    'flow_rate' => $flowRate,
                    'flow_velocity' => $flowVelocity,
                    'flow_status' => $flowStatus,
                    'water_level' => $waterLevel,
                    'water_height_cm' => $waterHeightCm,
                    'distance_cm' => $distanceCm,
                    'vibration_status' => $vibration,
                    'status' => $status,
                    'recorded_at' => $recordedAt,
                ]);

                try {
                    $telegramAlertService->handleSensorReading($normalizedPayload);
                } catch (Throwable $exception) {
                    $this->warn('Telegram alert gagal: ' . $exception->getMessage());
                }

                $this->updateRealtimeCache($normalizedPayload);

                try {
                    SensorReading::create([
                        'recorded_at' => $recordedAt,
                        'temperature' => $temperature,
                        'flow_rate' => $flowRate,
                        'flow_velocity' => $flowVelocity,
                        'flow_status' => $flowStatus,
                        'water_level' => $waterLevel,
                        'vibration_status' => $vibration,
                        'status' => $status,
                        'raw_payload' => $normalizedPayload,
                    ]);
                } catch (Throwable $exception) {
                    $this->warn('Simpan database gagal: ' . $exception->getMessage());
                }

                $this->line(sprintf(
                    '[%s] T: %.2f C | Flow: %.2f L/min | Vel: %.3f m/s | %s | Height: %s cm | Level: %.2f%% | Vib: %s | %s',
                    now()->format('Y-m-d H:i:s'),
                    $temperature,
                    $flowRate,
                    $flowVelocity,
                    $flowStatus,
                    $waterHeightCm !== null ? number_format($waterHeightCm, 2, '.', '') : '-',
                    $waterLevel,
                    $vibration,
                    $status
                ));
            }, 0);

            $mqtt->loop(true);
            $mqtt->disconnect();

            return self::SUCCESS;
        } catch (MqttClientException|Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function resolveStatus(string $vibration): string
    {
        if (strcasecmp($vibration, 'Terlalu Bergetar (Tidak Normal)') === 0) {
            return 'Tidak Normal';
        }

        if (strcasecmp($vibration, 'Normal') === 0) {
            return 'Normal';
        }

        return 'Mesin Mati';
    }

    private function normalizeVibrationStatus(string $vibration): string
    {
        $normalized = trim(strtolower($vibration));

        return match ($normalized) {
            '', 'tidak bergetar' => 'Tidak Bergetar',
            'normal' => 'Normal',
            'tidak normal', 'terlalu bergetar', 'terlalu bergetar (tidak normal)' => 'Terlalu Bergetar (Tidak Normal)',
            default => $vibration,
        };
    }

    private function calculateFlowVelocity(float $flowRate): float
    {
        $pipeDiameterM = 0.0127;
        $pipeAreaM2 = pi() * (($pipeDiameterM / 2) ** 2);
        $flowRateM3S = ($flowRate / 1000) / 60;

        return $pipeAreaM2 > 0 ? $flowRateM3S / $pipeAreaM2 : 0;
    }

    private function resolveFlowStatus(float $flowVelocity): string
    {
        $stoppedThreshold = 0.01;
        $normalThreshold = 1.00;

        if ($flowVelocity <= $stoppedThreshold) {
            return 'Air Tidak Mengalir';
        }

        if ($flowVelocity <= $normalThreshold) {
            return 'Air Mengalir';
        }

        return 'Air Mengalir Deras';
    }

    private function updateRealtimeCache(array $payload): void
    {
        $historyKey = 'monitoring.realtime.history_count';
        $warningKey = 'monitoring.realtime.warning_count';
        $dayKey = 'monitoring.realtime.day';
        $todayCountKey = 'monitoring.realtime.readings_today';
        $latestKey = 'monitoring.realtime.latest';
        $recentKey = 'monitoring.realtime.recent';

        $recordedAt = (string) ($payload['recorded_at'] ?? now()->toIso8601String());
        $currentDay = now()->toDateString();
        $storedDay = Cache::get($dayKey);

        if ($storedDay !== $currentDay) {
            Cache::forever($dayKey, $currentDay);
            Cache::forever($todayCountKey, 0);
        }

        $historyCount = (int) Cache::increment($historyKey);
        $readingsToday = (int) Cache::increment($todayCountKey);
        $warningCount = (int) Cache::get($warningKey, 0);

        if (in_array((string) ($payload['status'] ?? ''), ['Normal', 'Tidak Normal'], true)) {
            $warningCount = (int) Cache::increment($warningKey);
        }

        $latestSnapshot = [
            'id' => $historyCount,
            'recorded_at' => $recordedAt,
            'temperature' => (float) ($payload['temperature'] ?? 0),
            'flow_rate' => (float) ($payload['flow_rate'] ?? 0),
            'flow_velocity' => (float) ($payload['flow_velocity'] ?? 0),
            'flow_status' => (string) ($payload['flow_status'] ?? 'Air Tidak Mengalir'),
            'water_level' => (float) ($payload['water_level'] ?? 0),
            'water_height_cm' => isset($payload['water_height_cm']) ? (float) $payload['water_height_cm'] : null,
            'distance_cm' => isset($payload['distance_cm']) ? (float) $payload['distance_cm'] : null,
            'vibration_status' => (string) ($payload['vibration_status'] ?? 'Tidak Bergetar'),
            'vibration_count' => (int) ($payload['vibration_count'] ?? 0),
            'status' => (string) ($payload['status'] ?? 'Mesin Mati'),
            'raw_payload' => $payload,
        ];

        Cache::forever($latestKey, $latestSnapshot);

        $recentReadings = Cache::get($recentKey, []);
        array_unshift($recentReadings, [
            'id' => $historyCount,
            'recorded_at' => $recordedAt,
            'temperature' => (float) ($payload['temperature'] ?? 0),
            'flow_rate' => (float) ($payload['flow_rate'] ?? 0),
            'flow_velocity' => (float) ($payload['flow_velocity'] ?? 0),
            'flow_status' => (string) ($payload['flow_status'] ?? 'Air Tidak Mengalir'),
            'water_level' => (float) ($payload['water_level'] ?? 0),
            'vibration_status' => (string) ($payload['vibration_status'] ?? 'Tidak Bergetar'),
            'vibration_count' => (int) ($payload['vibration_count'] ?? 0),
            'status' => (string) ($payload['status'] ?? 'Mesin Mati'),
            'raw_payload' => $payload,
        ]);

        Cache::forever($recentKey, array_slice($recentReadings, 0, 8));
        Cache::forever('monitoring.realtime.meta', [
            'history_count' => $historyCount,
            'readings_today' => $readingsToday,
            'warning_count' => $warningCount,
        ]);
    }
}
