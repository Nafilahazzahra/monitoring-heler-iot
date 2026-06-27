<?php

namespace App\Http\Controllers;

use App\Models\SensorReading;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Throwable;
use Carbon\Carbon;

class MonitoringController extends Controller
{
    public function dashboard(): View
    {
        $snapshot = $this->getRealtimeSnapshot();

        return view('monitoring.dashboard', [
            'latest' => $snapshot['latest'],
            'vibrationSeries' => $snapshot['vibration_series'],
            'historyCount' => $snapshot['meta']['history_count'],
            'readingsToday' => $snapshot['meta']['readings_today'],
            'warningCount' => $snapshot['meta']['warning_count'],
            'recentReadings' => $snapshot['recent_readings'],
        ]);
    }

    public function history(): View
    {
        $readings = SensorReading::latest('id')->paginate(12);

        return view('monitoring.history', compact('readings'));
    }

    public function destroyHistory(): RedirectResponse
    {
        SensorReading::query()->delete();
        $this->clearRealtimeCache();

        return redirect()
            ->route('history')
            ->with('success', 'Riwayat sensor berhasil dihapus.');
    }

    public function latest(): JsonResponse
    {
        $snapshot = $this->getRealtimeSnapshot();
        $latest = $snapshot['latest'];
        $latestRawPayload = $latest['raw_payload'] ?? [];

        return response()->json([
            'data' => $latest ? [
                'id' => $latest['id'],
                'recorded_at' => $this->formatRecordedAt($latest['recorded_at'] ?? null),
                'temperature' => $latest['temperature'],
                'flow_rate' => $this->payloadNumber($latest, 'flow_rate'),
                'flow_velocity' => $this->payloadNumber($latest, 'flow_velocity'),
                'flow_status' => $this->payloadText($latest, 'flow_status', 'Air Tidak Mengalir'),
                'water_level' => $latest['water_level'],
                'vibration_status' => $latest['vibration_status'],
                'vibration_count' => (int) ($latest['vibration_count'] ?? 0),
                'status' => $latest['status'],
                'raw_payload' => $latestRawPayload,
            ] : null,
            'meta' => $snapshot['meta'],
            'recent_readings' => collect($snapshot['recent_readings'])->map(fn (array $reading) => [
                'id' => $reading['id'],
                'recorded_at' => $this->formatRecordedAt($reading['recorded_at'] ?? null),
                'temperature' => $reading['temperature'],
                'flow_rate' => $this->payloadNumber($reading, 'flow_rate'),
                'flow_velocity' => $this->payloadNumber($reading, 'flow_velocity'),
                'flow_status' => $this->payloadText($reading, 'flow_status', 'Air Tidak Mengalir'),
                'water_level' => $reading['water_level'],
                'status' => $reading['status'],
                'raw_payload' => $reading['raw_payload'] ?? [],
            ])->values(),
        ])
        ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
        ->header('Pragma', 'no-cache')
        ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }

    private function extractVibrationLevel(SensorReading|array $reading): int
    {
        $rawPayload = is_array($reading)
            ? ($reading['raw_payload'] ?? [])
            : ($reading->raw_payload ?? []);
        $vibrationCount = (int) ($rawPayload['vibration_count'] ?? 0);

        if ($vibrationCount > 0) {
            return $vibrationCount;
        }

        $vibrationStatus = is_array($reading)
            ? ($reading['vibration_status'] ?? 'Tidak Bergetar')
            : $reading->vibration_status;

        return match ($vibrationStatus) {
            'Terlalu Bergetar (Tidak Normal)' => 12,
            'Normal' => 5,
            default => 0,
        };
    }

    private function getRealtimeSnapshot(): array
    {
        $cachedLatest = Cache::get('monitoring.realtime.latest');
        $cachedRecent = Cache::get('monitoring.realtime.recent', []);
        $cachedMeta = Cache::get('monitoring.realtime.meta', [
            'history_count' => 0,
            'readings_today' => 0,
            'warning_count' => 0,
        ]);

        if ($cachedLatest) {
            $vibrationSeries = collect($cachedRecent)
                ->reverse()
                ->map(fn (array $reading) => $this->extractVibrationLevel($reading))
                ->values()
                ->all();

            return [
                'latest' => $cachedLatest,
                'recent_readings' => $cachedRecent,
                'meta' => $cachedMeta,
                'vibration_series' => $vibrationSeries,
            ];
        }

        try {
            $latest = SensorReading::latest('id')->first();
            $recentReadings = SensorReading::latest('id')->take(8)->get();
            $vibrationSeries = SensorReading::latest('id')
                ->take(24)
                ->get()
                ->reverse()
                ->map(fn (SensorReading $reading) => $this->extractVibrationLevel($reading))
                ->values()
                ->all();

            return [
                'latest' => $latest ? $this->readingToArray($latest) : null,
                'recent_readings' => $recentReadings
                    ->map(fn (SensorReading $reading) => $this->readingToArray($reading))
                    ->values()
                    ->all(),
                'meta' => [
                    'history_count' => SensorReading::count(),
                    'readings_today' => SensorReading::whereDate('recorded_at', now()->toDateString())->count(),
                    'warning_count' => SensorReading::whereIn('status', ['Normal', 'Tidak Normal'])->count(),
                ],
                'vibration_series' => $vibrationSeries,
            ];
        } catch (Throwable) {
            return [
                'latest' => null,
                'recent_readings' => [],
                'meta' => [
                    'history_count' => 0,
                    'readings_today' => 0,
                    'warning_count' => 0,
                ],
                'vibration_series' => [],
            ];
        }
    }

    private function readingToArray(SensorReading $reading): array
    {
        $payload = [
            'flow_rate' => $reading->flow_rate,
            'flow_velocity' => $reading->flow_velocity ?? 0,
            'flow_status' => $reading->flow_status ?? 'Air Tidak Mengalir',
            'raw_payload' => $reading->raw_payload ?? [],
        ];

        return [
            'id' => $reading->id,
            'recorded_at' => optional($reading->recorded_at)?->toIso8601String(),
            'temperature' => $reading->temperature,
            'flow_rate' => $this->payloadNumber($payload, 'flow_rate'),
            'flow_velocity' => $this->payloadNumber($payload, 'flow_velocity'),
            'flow_status' => $this->payloadText($payload, 'flow_status', 'Air Tidak Mengalir'),
            'water_level' => $reading->water_level,
            'vibration_status' => $reading->vibration_status,
            'vibration_count' => $this->extractVibrationLevel($reading),
            'status' => $reading->status,
            'raw_payload' => $payload['raw_payload'] ?? [],
        ];
    }

    private function payloadNumber(array $reading, string $key): float
    {
        $rawPayload = $reading['raw_payload'] ?? [];

        if (isset($rawPayload[$key]) && is_numeric($rawPayload[$key])) {
            return (float) $rawPayload[$key];
        }

        return (float) ($reading[$key] ?? 0);
    }

    private function payloadText(array $reading, string $key, string $default): string
    {
        $rawPayload = $reading['raw_payload'] ?? [];

        if (isset($rawPayload[$key]) && trim((string) $rawPayload[$key]) !== '') {
            return (string) $rawPayload[$key];
        }

        if (isset($reading[$key]) && trim((string) $reading[$key]) !== '') {
            return (string) $reading[$key];
        }

        return $default;
    }

    private function formatRecordedAt(?string $recordedAt): string
    {
        if (! $recordedAt) {
            return 'Belum ada data';
        }

        return Carbon::parse($recordedAt)->format('d M Y H:i:s');
    }

    private function clearRealtimeCache(): void
    {
        Cache::forget('monitoring.realtime.latest');
        Cache::forget('monitoring.realtime.recent');
        Cache::forget('monitoring.realtime.meta');
        Cache::forget('monitoring.realtime.history_count');
        Cache::forget('monitoring.realtime.warning_count');
        Cache::forget('monitoring.realtime.day');
        Cache::forget('monitoring.realtime.readings_today');
    }
}
