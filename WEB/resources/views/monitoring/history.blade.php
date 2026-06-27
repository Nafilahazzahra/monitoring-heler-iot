<x-monitoring-layout>
    <x-slot name="title">Riwayat Data - Monitoring Mesin Heler</x-slot>
    <x-slot name="pageHeading">Riwayat Data</x-slot>

    <div class="space-y-6">
        <div class="rounded-[1.75rem] bg-slate-900 p-6 text-white shadow-xl">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm uppercase tracking-[0.25em] text-slate-400">History</p>
                    <p class="mt-2 text-sm text-slate-300">Riwayat sensor yang tersimpan di database bisa dibersihkan saat sudah terlalu banyak.</p>
                </div>

                <form method="POST" action="{{ route('history.destroy') }}" onsubmit="return confirm('Yakin ingin menghapus semua riwayat sensor? Data di database juga akan terhapus.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex rounded-2xl bg-red-500 px-4 py-3 text-sm font-bold text-white transition hover:bg-red-400">
                        Hapus Semua Riwayat
                    </button>
                </form>
            </div>
        </div>

        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="overflow-hidden rounded-[1.75rem] bg-white shadow-sm ring-1 ring-slate-100">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-6 py-4 font-bold uppercase tracking-[0.2em]">Waktu</th>
                            <th class="px-6 py-4 font-bold uppercase tracking-[0.2em]">Suhu</th>
                            <th class="px-6 py-4 font-bold uppercase tracking-[0.2em]">Flow</th>
                            <th class="px-6 py-4 font-bold uppercase tracking-[0.2em]">Velocity</th>
                            <th class="px-6 py-4 font-bold uppercase tracking-[0.2em]">Aliran</th>
                            <th class="px-6 py-4 font-bold uppercase tracking-[0.2em]">Level</th>
                            <th class="px-6 py-4 font-bold uppercase tracking-[0.2em]">Getaran</th>
                            <th class="px-6 py-4 font-bold uppercase tracking-[0.2em]">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                        @forelse ($readings as $reading)
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4 font-semibold">{{ optional($reading->recorded_at)->format('d M Y H:i:s') }}</td>
                                <td class="px-6 py-4">{{ number_format($reading->temperature, 1) }} C</td>
                                <td class="px-6 py-4">{{ number_format($reading->flow_rate, 2) }} L/min</td>
                                <td class="px-6 py-4">{{ number_format($reading->flow_velocity, 3) }} m/s</td>
                                <td class="px-6 py-4">{{ $reading->flow_status }}</td>
                                <td class="px-6 py-4">{{ number_format($reading->water_level, 1) }} %</td>
                                <td class="px-6 py-4">{{ $reading->vibration_status }}</td>
                                <td class="px-6 py-4">
                                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ $reading->status === 'Tidak Normal' ? 'bg-red-100 text-red-700' : ($reading->status === 'Normal' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700') }}">{{ $reading->status }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-8 text-center text-slate-500">Belum ada data sensor tersimpan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 px-6 py-4">
                {{ $readings->links() }}
            </div>
        </div>
    </div>
</x-monitoring-layout>
