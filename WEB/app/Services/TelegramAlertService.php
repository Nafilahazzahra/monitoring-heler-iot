<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TelegramAlertService
{
    public function handleSensorReading(array $reading): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        foreach ($this->buildAlerts($reading) as $alert) {
            $cacheKey = 'telegram_alert:' . $alert['key'];
            $wasActive = Cache::get($cacheKey, false);

            if ($alert['active'] && ! $wasActive) {
                $this->sendMessage($alert['message']);
                Cache::forever($cacheKey, true);
            }

            if (! $alert['active'] && $wasActive) {
                Cache::forget($cacheKey);
            }
        }
    }

    public function sendMessage(string $message): void
    {
        $token = (string) config('services.telegram.bot_token');
        $chatId = (string) config('services.telegram.chat_id');

        if ($token === '' || $chatId === '') {
            throw new RuntimeException('Konfigurasi Telegram belum lengkap. Isi TELEGRAM_BOT_TOKEN dan TELEGRAM_CHAT_ID.');
        }

        $response = Http::asForm()
            ->timeout(10)
            ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Gagal mengirim notifikasi Telegram: ' . $response->body());
        }
    }

    private function buildAlerts(array $reading): array
    {
        $temperature = (float) ($reading['temperature'] ?? 0);
        $flowRate = (float) ($reading['flow_rate'] ?? 0);
        $vibrationStatus = (string) ($reading['vibration_status'] ?? 'Tidak Bergetar');
        $distanceCm = isset($reading['distance_cm']) ? (float) $reading['distance_cm'] : null;
        $waterHeightCm = isset($reading['water_height_cm'])
            ? (float) $reading['water_height_cm']
            : $this->resolveWaterHeightCm($distanceCm);

        $waterLowThreshold = (float) config('services.telegram.water_low_cm', 10);
        $flowMinThreshold = (float) config('services.telegram.flow_min', 0);
        $temperatureHighThreshold = (float) config('services.telegram.temperature_high_c', 90);

        return [
            [
                'key' => 'level_air_rendah',
                'active' => $waterHeightCm !== null && $waterHeightCm < $waterLowThreshold,
                'message' => sprintf(
                    "Level Air Rendah ⚠️\nKetinggian air %.2f cm, kurang dari %.2f cm.",
                    $waterHeightCm ?? 0,
                    $waterLowThreshold
                ),
            ],
            [
                'key' => 'aliran_air_terhenti',
                'active' => $flowRate <= $flowMinThreshold,
                'message' => sprintf(
                    "Aliran Air Terhenti 🚨\nTidak ada aliran air menuju mesin.\nFlow rate: %.2f L/min.",
                    $flowRate
                ),
            ],
            [
                'key' => 'suhu_mesin_tinggi',
                'active' => $temperature > $temperatureHighThreshold,
                'message' => sprintf(
                    "Suhu Mesin Tinggi 🌡️\nSuhu mesin %.2f C melebihi %.2f C.\nSegera lakukan pemeriksaan.",
                    $temperature,
                    $temperatureHighThreshold
                ),
            ],
            [
                'key' => 'getaran_tidak_normal',
                'active' => strcasecmp($vibrationStatus, 'Terlalu Bergetar (Tidak Normal)') === 0,
                'message' => "Getaran Tidak Normal ⚙️\nStatus sensor getaran: getaran tidak normal.",
            ],
        ];
    }

    private function isEnabled(): bool
    {
        return filter_var(config('services.telegram.enabled', false), FILTER_VALIDATE_BOOL);
    }

    private function resolveWaterHeightCm(?float $distanceCm): ?float
    {
        if ($distanceCm === null) {
            return null;
        }

        $tankHeightCm = (float) config('services.telegram.tank_height_cm', 100);

        return max(0, min($tankHeightCm, $tankHeightCm - $distanceCm));
    }
}
