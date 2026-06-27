<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Monitoring Mesin Heler' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-[#eef3f7] text-slate-900">
<div class="min-h-screen lg:grid lg:grid-cols-[280px_1fr]">
    <aside class="bg-[#111827] text-white px-6 py-8 flex flex-col gap-8 shadow-2xl">
        <div>
            {{-- <p class="text-xs uppercase tracking-[0.35em] text-slate-400">IoT Panel</p> --}}
            <h1 class="mt-3 text-3xl font-black leading-tight">Monitoring<br>Mesin Heler</h1>
            <p class="mt-3 text-sm text-slate-400">Pantau data sensor mesin secara realtime.</p>
        </div>

        <nav class="space-y-3">
            <a href="{{ route('dashboard') }}" class="flex items-center justify-between rounded-2xl px-4 py-3 transition {{ request()->routeIs('dashboard') ? 'bg-white text-slate-900 shadow-lg' : 'bg-slate-900/60 text-slate-200 hover:bg-slate-800' }}">
                <span class="font-semibold">Dashboard</span>
                <span class="text-xs">01</span>
            </a>
            <a href="{{ route('history') }}" class="flex items-center justify-between rounded-2xl px-4 py-3 transition {{ request()->routeIs('history') ? 'bg-white text-slate-900 shadow-lg' : 'bg-slate-900/60 text-slate-200 hover:bg-slate-800' }}">
                <span class="font-semibold">Riwayat</span>
                <span class="text-xs">02</span>
            </a>
        </nav>

        <div class="mt-auto rounded-3xl bg-slate-800/80 p-5">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Akun aktif</p>
            <p class="mt-2 text-lg font-bold">{{ auth()->user()->name }}</p>
            <p class="text-sm text-slate-400">{{ auth()->user()->email }}</p>
            <form method="POST" action="{{ route('logout') }}" class="mt-4">
                @csrf
                <button type="submit" class="w-full rounded-2xl border border-slate-600 px-4 py-3 font-semibold text-white transition hover:bg-slate-700">Logout</button>
            </form>
        </div>
    </aside>

    <main class="p-4 sm:p-6 lg:p-8">
        <div class="rounded-[2rem] bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-col gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.25em] text-slate-400">Selamat datang</p>
                    <h2 class="mt-2 text-3xl font-black text-slate-900">{{ $pageHeading ?? 'Dashboard' }}</h2>
                </div>
                <div class="rounded-2xl bg-slate-100 px-4 py-3 text-right">
                    <p class="text-xs uppercase tracking-[0.25em] text-slate-400">User</p>
                    <p class="text-lg font-bold text-slate-900">{{ auth()->user()->name }}</p>
                </div>
            </div>

            <div class="pt-6">
                {{ $slot }}
            </div>
        </div>
    </main>
</div>
</body>
</html>
