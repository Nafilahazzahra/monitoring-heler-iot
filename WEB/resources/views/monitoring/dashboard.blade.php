<x-monitoring-layout>
    <x-slot name="title">Dashboard - Monitoring Mesin Heler</x-slot>
    <x-slot name="pageHeading">Dashboard</x-slot>

    @php
        $latestStatus = data_get($latest, 'status');
        $latestTemperature = (float) data_get($latest, 'temperature', 0);
        $latestFlowRate = (float) data_get($latest, 'flow_rate', 0);
        $latestFlowVelocity = (float) data_get($latest, 'flow_velocity', data_get($latest, 'raw_payload.flow_velocity', 0));
        $latestFlowStatus = data_get($latest, 'flow_status', data_get($latest, 'raw_payload.flow_status', 'Air Tidak Mengalir'));
        $latestWaterLevel = (float) data_get($latest, 'water_level', 0);
        $latestRecordedAt = data_get($latest, 'recorded_at');
        $latestRawPayload = data_get($latest, 'raw_payload', []);

        $statusColor = match($latestStatus) {
            'Tidak Normal' => 'bg-red-100 text-red-700',
            'Normal' => 'bg-amber-100 text-amber-700',
            default => 'bg-slate-100 text-slate-700',
        };

        $vibrationStatus = data_get($latest, 'vibration_status', 'Tidak Bergetar');
        $vibrationCount = (int) (data_get($latest, 'vibration_count', data_get($latestRawPayload, 'vibration_count', 0)));
        $vibrationTone = match($vibrationStatus) {
            'Terlalu Bergetar (Tidak Normal)' => [
                'panel' => 'from-rose-50 via-white to-rose-100/80',
                'screen' => 'bg-slate-950',
                'line' => '#fb7185',
                'glow' => 'rgba(251, 113, 133, 0.28)',
                'text' => 'text-rose-600',
                'pill' => 'bg-rose-100 text-rose-700',
            ],
            'Normal' => [
                'panel' => 'from-cyan-50 via-white to-sky-100/80',
                'screen' => 'bg-slate-950',
                'line' => '#22d3ee',
                'glow' => 'rgba(34, 211, 238, 0.24)',
                'text' => 'text-sky-600',
                'pill' => 'bg-sky-100 text-sky-700',
            ],
            default => [
                'panel' => 'from-slate-50 via-white to-slate-100/80',
                'screen' => 'bg-slate-900',
                'line' => '#94a3b8',
                'glow' => 'rgba(148, 163, 184, 0.18)',
                'text' => 'text-slate-500',
                'pill' => 'bg-slate-100 text-slate-600',
            ],
        };
    @endphp

    <div class="space-y-4 sm:space-y-5">
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-[1.35rem] bg-slate-900 p-4 text-white shadow-xl sm:rounded-[1.5rem] sm:p-5">
                <p class="text-[0.65rem] uppercase tracking-[0.25em] text-slate-400 sm:text-sm">Suhu Terakhir</p>
                <p id="temperature-value" class="mt-2 text-2xl font-black sm:mt-3 sm:text-3xl">{{ number_format($latestTemperature, 1) }}<span class="text-sm font-semibold sm:text-base"> C</span></p>
                <p class="mt-2 text-xs text-slate-400 sm:text-sm">Sensor DS18B20</p>
            </div>
            <div class="rounded-[1.35rem] bg-white p-4 shadow-sm ring-1 ring-slate-100 sm:rounded-[1.5rem] sm:p-5">
                <p class="text-[0.65rem] uppercase tracking-[0.25em] text-slate-400 sm:text-sm">Flow Velocity</p>
                <p id="flow-velocity-value" class="mt-2 text-2xl font-black text-slate-900 sm:mt-3 sm:text-3xl">{{ number_format($latestFlowVelocity, 3) }}<span class="text-sm font-semibold sm:text-base"> m/s</span></p>
                <p id="flow-status" class="mt-2 text-sm font-bold text-slate-600 sm:text-base">{{ $latestFlowStatus }}</p>
                <p id="flow-rate-hint" class="mt-1 text-xs text-slate-500 sm:text-sm">Debit {{ number_format($latestFlowRate, 2) }} L/min</p>
            </div>
            <div class="rounded-[1.35rem] bg-white p-4 shadow-sm ring-1 ring-slate-100 sm:rounded-[1.5rem] sm:p-5">
                <p class="text-[0.65rem] uppercase tracking-[0.25em] text-slate-400 sm:text-sm">Level Air</p>
                <p id="level-value" class="mt-2 text-2xl font-black text-slate-900 sm:mt-3 sm:text-3xl">{{ number_format($latestWaterLevel, 1) }}<span class="text-sm font-semibold sm:text-base"> %</span></p>
                <p class="mt-2 text-xs text-slate-500 sm:text-sm">Ultrasonic / water level</p>
            </div>
            <div id="vibration-card" class="rounded-[1.35rem] bg-gradient-to-br {{ $vibrationTone['panel'] }} p-4 shadow-sm ring-1 ring-slate-100 sm:rounded-[1.5rem] sm:p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[0.65rem] uppercase tracking-[0.35em] text-slate-400 sm:text-sm">Status Mesin</p>
                        <div class="mt-2 inline-flex rounded-full px-3 py-1.5 text-xs font-bold sm:mt-3 sm:px-4 sm:py-2 sm:text-sm {{ $statusColor }}" id="status-badge">{{ $latestStatus ?? 'Belum ada data' }}</div>
                    </div>
                    <div id="vibration-count-badge" class="rounded-full px-3 py-1 text-[0.65rem] font-bold uppercase tracking-[0.22em] {{ $vibrationTone['pill'] }}">
                        {{ $vibrationCount }} trigger
                    </div>
                </div>

                <div class="mt-4 rounded-[1.4rem] {{ $vibrationTone['screen'] }} p-3 shadow-inner">
                    <div class="flex items-center justify-between gap-2 text-[0.65rem] uppercase tracking-[0.28em] text-slate-400">
                        <span>Gelombang Getaran</span>
                        <span>Realtime</span>
                    </div>
                    <div id="vibration-screen" class="mt-3 overflow-hidden rounded-2xl border border-white/5 bg-[radial-gradient(circle_at_top,_rgba(56,189,248,0.08),_transparent_55%),linear-gradient(180deg,_rgba(15,23,42,0.92),_rgba(2,6,23,1))] p-2">
                        <svg id="vibration-wave" viewBox="0 0 320 92" preserveAspectRatio="none" class="h-24 w-full">
                            <defs>
                                <filter id="wave-glow">
                                    <feGaussianBlur stdDeviation="2.5" result="coloredBlur"/>
                                    <feMerge>
                                        <feMergeNode in="coloredBlur"/>
                                        <feMergeNode in="SourceGraphic"/>
                                    </feMerge>
                                </filter>
                            </defs>
                            <g stroke="rgba(148,163,184,0.12)" stroke-width="1">
                                <line x1="0" y1="18" x2="320" y2="18" />
                                <line x1="0" y1="46" x2="320" y2="46" />
                                <line x1="0" y1="74" x2="320" y2="74" />
                            </g>
                            <path id="vibration-wave-glow" d="" fill="none" stroke="{{ $vibrationTone['glow'] }}" stroke-width="10" stroke-linecap="round" stroke-linejoin="round" filter="url(#wave-glow)"></path>
                            <path id="vibration-wave-line" d="" fill="none" stroke="{{ $vibrationTone['line'] }}" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </div>
                </div>

                <div class="mt-4">
                    <p id="vibration-value" class="text-sm font-bold {{ $vibrationTone['text'] }} sm:text-base">Getaran: {{ $vibrationStatus }}</p>
                    <p class="mt-1 text-xs text-slate-500 sm:text-sm">Visual ini menunjukkan pola trigger sensor SW420 dalam beberapa pembacaan terakhir.</p>
                </div>
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-[1.3fr_0.7fr] xl:gap-5">
            <section class="rounded-[1.35rem] bg-white p-4 shadow-sm ring-1 ring-slate-100 sm:rounded-[1.5rem] sm:p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-[0.65rem] uppercase tracking-[0.2em] text-slate-400 sm:text-sm">Ringkasan Realtime</p>
                        <h3 class="mt-2 text-lg font-black text-slate-900 sm:text-xl">Pembacaan Sensor Terbaru</h3>
                    </div>
                    <div class="rounded-2xl bg-slate-100 px-3 py-2.5 sm:text-right">
                        <p class="text-[0.65rem] uppercase tracking-[0.2em] text-slate-400">Waktu data</p>
                        <p id="recorded-at" class="text-xs font-bold text-slate-900 sm:text-sm">{{ $latestRecordedAt ? \Carbon\Carbon::parse($latestRecordedAt)->format('d M Y H:i:s') : 'Belum ada data' }}</p>
                    </div>
                </div>

                <div class="mt-4 grid gap-3 sm:mt-5 sm:grid-cols-2">
                    <div class="rounded-2xl bg-slate-50 p-3.5 sm:rounded-3xl sm:p-4">
                        <p class="text-sm font-semibold text-slate-500">Total Riwayat</p>
                        <p id="history-count" class="mt-1.5 text-xl font-black text-slate-900 sm:mt-2 sm:text-2xl">{{ $historyCount }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-3.5 sm:rounded-3xl sm:p-4">
                        <p class="text-sm font-semibold text-slate-500">Data Hari Ini</p>
                        <p id="readings-today" class="mt-1.5 text-xl font-black text-slate-900 sm:mt-2 sm:text-2xl">{{ $readingsToday }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-3.5 sm:rounded-3xl sm:p-4">
                        <p class="text-sm font-semibold text-slate-500">Normal / Tidak Normal</p>
                        <p id="warning-count" class="mt-1.5 text-xl font-black text-slate-900 sm:mt-2 sm:text-2xl">{{ $warningCount }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-3.5 sm:rounded-3xl sm:p-4">
                        <p class="text-sm font-semibold text-slate-500">Akses Cepat</p>
                        <a href="{{ route('history') }}" class="mt-3 inline-flex rounded-2xl bg-slate-900 px-3.5 py-2.5 text-sm font-bold text-white hover:bg-slate-800">Buka Riwayat Data</a>
                    </div>
                </div>
            </section>

            <section class="rounded-[1.35rem] bg-slate-900 p-4 text-white shadow-xl sm:rounded-[1.5rem] sm:p-5">
                <p class="text-[0.65rem] uppercase tracking-[0.2em] text-slate-400 sm:text-sm">Aktivitas Terakhir</p>
                <h3 class="mt-2 text-lg font-black sm:text-xl">8 Data Terkini</h3>
                <div id="recent-readings" class="mt-4 space-y-2.5 sm:mt-5">
                    @forelse ($recentReadings as $reading)
                        <div class="rounded-2xl bg-white/10 p-3.5">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-bold">{{ number_format((float) data_get($reading, 'temperature', 0), 1) }} C</p>
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ data_get($reading, 'status') === 'Tidak Normal' ? 'bg-red-500/20 text-red-200' : (data_get($reading, 'status') === 'Normal' ? 'bg-amber-500/20 text-amber-100' : 'bg-slate-500/20 text-slate-200') }}">{{ data_get($reading, 'status', 'Mesin Mati') }}</span>
                            </div>
                            <p class="mt-2 text-xs text-slate-300">Velocity {{ number_format((float) data_get($reading, 'flow_velocity', 0), 3) }} m/s | {{ data_get($reading, 'flow_status', 'Air Tidak Mengalir') }}</p>
                            <p class="mt-1 text-xs text-slate-400">Flow {{ number_format((float) data_get($reading, 'flow_rate', 0), 2) }} L/min | Level {{ number_format((float) data_get($reading, 'water_level', 0), 1) }}%</p>
                            <p class="mt-1 text-xs text-slate-400">{{ data_get($reading, 'recorded_at') ? \Carbon\Carbon::parse(data_get($reading, 'recorded_at'))->format('d M Y H:i:s') : 'Belum ada data' }}</p>
                        </div>
                    @empty
                        <div class="rounded-3xl bg-white/10 p-4 text-sm text-slate-300">Belum ada data sensor yang masuk.</div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>

    <script>
        const statusClassMap = {
            'Mesin Mati': 'bg-slate-100 text-slate-700',
            Normal: 'bg-amber-100 text-amber-700',
            'Tidak Normal': 'bg-red-100 text-red-700',
        };
        const vibrationToneMap = {
            'Tidak Bergetar': {
                line: '#94a3b8',
                glow: 'rgba(148, 163, 184, 0.18)',
                text: 'text-slate-500',
                pill: 'bg-slate-100 text-slate-600',
                panel: 'from-slate-50 via-white to-slate-100/80',
                screen: 'bg-slate-900',
            },
            'Normal': {
                line: '#22d3ee',
                glow: 'rgba(34, 211, 238, 0.24)',
                text: 'text-sky-600',
                pill: 'bg-sky-100 text-sky-700',
                panel: 'from-cyan-50 via-white to-sky-100/80',
                screen: 'bg-slate-950',
            },
            'Terlalu Bergetar (Tidak Normal)': {
                line: '#fb7185',
                glow: 'rgba(251, 113, 133, 0.28)',
                text: 'text-rose-600',
                pill: 'bg-rose-100 text-rose-700',
                panel: 'from-rose-50 via-white to-rose-100/80',
                screen: 'bg-slate-950',
            },
        };
        const vibrationSeries = @json($vibrationSeries);
        const vibrationCard = document.getElementById('vibration-card');
        const vibrationScreen = document.getElementById('vibration-screen');
        const vibrationWaveLine = document.getElementById('vibration-wave-line');
        const vibrationWaveGlow = document.getElementById('vibration-wave-glow');
        const vibrationCountBadge = document.getElementById('vibration-count-badge');
        const vibrationValue = document.getElementById('vibration-value');
        const recentReadingsContainer = document.getElementById('recent-readings');
        const flowVelocityValue = document.getElementById('flow-velocity-value');
        const flowStatus = document.getElementById('flow-status');
        const flowRateHint = document.getElementById('flow-rate-hint');

        function normalizeWaveSeries(values) {
            const points = values.slice(-24);
            while (points.length < 24) {
                points.unshift(0);
            }

            return points;
        }

        function buildWavePath(values) {
            const points = normalizeWaveSeries(values);
            const width = 320;
            const height = 92;
            const stepX = width / Math.max(points.length - 1, 1);
            const maxValue = Math.max(...points, 12, 1);
            const baseline = 78;
            const amplitude = 54;

            return points.map((point, index) => {
                const x = Number((index * stepX).toFixed(2));
                const scaled = Math.min(point / maxValue, 1);
                const y = Number((baseline - (scaled * amplitude)).toFixed(2));
                return `${index === 0 ? 'M' : 'L'} ${x} ${y}`;
            }).join(' ');
        }

        function applyVibrationTone(status, count) {
            const tone = vibrationToneMap[status] ?? vibrationToneMap['Tidak Bergetar'];

            vibrationWaveLine?.setAttribute('stroke', tone.line);
            vibrationWaveGlow?.setAttribute('stroke', tone.glow);

            if (vibrationCard) {
                vibrationCard.className = `rounded-[1.35rem] bg-gradient-to-br ${tone.panel} p-4 shadow-sm ring-1 ring-slate-100 sm:rounded-[1.5rem] sm:p-5`;
            }

            if (vibrationScreen) {
                vibrationScreen.className = `mt-3 overflow-hidden rounded-2xl border border-white/5 bg-[radial-gradient(circle_at_top,_rgba(56,189,248,0.08),_transparent_55%),linear-gradient(180deg,_rgba(15,23,42,0.92),_rgba(2,6,23,1))] p-2 ${tone.screen}`;
            }

            if (vibrationValue) {
                vibrationValue.className = `text-sm font-bold ${tone.text} sm:text-base`;
            }

            if (vibrationCountBadge) {
                vibrationCountBadge.className = `rounded-full px-3 py-1 text-[0.65rem] font-bold uppercase tracking-[0.22em] ${tone.pill}`;
                vibrationCountBadge.textContent = `${count} trigger`;
            }
        }

        function renderVibrationWave(status, count) {
            vibrationSeries.push(Number(count) || 0);
            if (vibrationSeries.length > 24) {
                vibrationSeries.splice(0, vibrationSeries.length - 24);
            }

            const path = buildWavePath(vibrationSeries);
            vibrationWaveLine?.setAttribute('d', path);
            vibrationWaveGlow?.setAttribute('d', path);
            applyVibrationTone(status, count);
        }

        function recentReadingStatusClass(status) {
            if (status === 'Critical') {
                return 'bg-red-500/20 text-red-200';
            }

            if (status === 'Tidak Normal') {
                return 'bg-red-500/20 text-red-200';
            }

            if (status === 'Warning' || status === 'Normal') {
                return 'bg-amber-500/20 text-amber-100';
            }

            return 'bg-slate-500/20 text-slate-200';
        }

        function renderRecentReadings(readings) {
            if (!recentReadingsContainer || !Array.isArray(readings)) {
                return;
            }

            if (readings.length === 0) {
                recentReadingsContainer.innerHTML = '<div class="rounded-3xl bg-white/10 p-4 text-sm text-slate-300">Belum ada data sensor yang masuk.</div>';
                return;
            }

            recentReadingsContainer.innerHTML = readings.map((reading) => `
                <div class="rounded-2xl bg-white/10 p-3.5">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-bold">${Number(reading.temperature).toFixed(1)} C</p>
                        <span class="rounded-full px-3 py-1 text-xs font-bold ${recentReadingStatusClass(reading.status)}">${reading.status}</span>
                    </div>
                    <p class="mt-2 text-xs text-slate-300">Velocity ${Number(reading.flow_velocity ?? 0).toFixed(3)} m/s | ${reading.flow_status ?? 'Air Tidak Mengalir'}</p>
                    <p class="mt-1 text-xs text-slate-400">Flow ${Number(reading.flow_rate).toFixed(2)} L/min | Level ${Number(reading.water_level).toFixed(1)}%</p>
                    <p class="mt-1 text-xs text-slate-400">${reading.recorded_at ?? '-'}</p>
                </div>
            `).join('');
        }

        function payloadNumber(reading, key) {
            const rawPayload = reading?.raw_payload ?? {};
            const rawValue = Number(rawPayload[key]);
            const value = Number(reading?.[key]);

            if (Number.isFinite(rawValue) && rawValue !== 0) {
                return rawValue;
            }

            return Number.isFinite(value) ? value : 0;
        }

        function payloadText(reading, key, fallback) {
            const rawPayload = reading?.raw_payload ?? {};

            return rawPayload[key] || reading?.[key] || fallback;
        }

        let lastReadingId = {{ (int) data_get($latest, 'id', 0) }};
        let refreshTimer = null;

        async function refreshLatestReading() {
            try {
                const response = await fetch(`{{ route('latest.reading') }}?t=${Date.now()}`, {
                    cache: 'no-store',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const payload = await response.json();
                const data = payload.data;
                const meta = payload.meta ?? {};
                const recentReadings = payload.recent_readings ?? [];

                if (!data) {
                    return;
                }

                if (Number(data.id) === lastReadingId) {
                    return;
                }

                lastReadingId = Number(data.id);
                const flowRate = payloadNumber(data, 'flow_rate');
                const flowVelocity = payloadNumber(data, 'flow_velocity');
                const flowStatusText = payloadText(data, 'flow_status', 'Air Tidak Mengalir');

                document.getElementById('history-count').textContent = meta.history_count ?? document.getElementById('history-count').textContent;
                document.getElementById('readings-today').textContent = meta.readings_today ?? document.getElementById('readings-today').textContent;
                document.getElementById('warning-count').textContent = meta.warning_count ?? document.getElementById('warning-count').textContent;
                renderRecentReadings(recentReadings);

                document.getElementById('temperature-value').innerHTML = `${Number(data.temperature).toFixed(1)}<span class="text-lg font-semibold"> C</span>`;
                flowVelocityValue.innerHTML = `${flowVelocity.toFixed(3)}<span class="text-lg font-semibold"> m/s</span>`;
                flowStatus.textContent = flowStatusText;
                flowRateHint.textContent = `Debit ${flowRate.toFixed(2)} L/min`;
                document.getElementById('level-value').innerHTML = `${Number(data.water_level).toFixed(1)}<span class="text-lg font-semibold"> %</span>`;
                document.getElementById('vibration-value').textContent = `Getaran: ${data.vibration_status}`;
                document.getElementById('recorded-at').textContent = data.recorded_at;
                renderVibrationWave(data.vibration_status, Number(data.vibration_count) || 0);

                const badge = document.getElementById('status-badge');
                badge.textContent = data.status;
                badge.className = `mt-2 inline-flex rounded-full px-3 py-1.5 text-xs font-bold sm:mt-3 sm:px-4 sm:py-2 sm:text-sm ${statusClassMap[data.status] ?? statusClassMap['Mesin Mati']}`;
            } catch (error) {
                console.error('Gagal mengambil data terbaru:', error);
            } finally {
                refreshTimer = window.setTimeout(refreshLatestReading, 300);
            }
        }

        renderVibrationWave(@js($vibrationStatus), {{ $vibrationCount }});
        refreshLatestReading();
    </script>
</x-monitoring-layout>
