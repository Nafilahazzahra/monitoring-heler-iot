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
    <div class="sticky top-0 z-30 flex items-center justify-between border-b border-slate-200 bg-white px-4 py-3 shadow-sm lg:hidden">
        <div>
            {{-- <p class="text-[0.6rem] uppercase tracking-[0.35em] text-slate-400">IoT Panel</p> --}}
            <p class="text-lg font-black text-slate-900">Monitoring Mesin Heler</p>
        </div>
        <button type="button" id="sidebar-toggle" class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-slate-900 text-white shadow-md" aria-label="Toggle menu" aria-expanded="false">
            <svg data-icon="menu" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            <svg data-icon="close" xmlns="http://www.w3.org/2000/svg" class="hidden h-4 w-4 sm:h-5 sm:w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18" />
            </svg>
        </button>
    </div>

    <aside id="sidebar" class="hidden bg-[#111827] text-white shadow-2xl lg:block lg:min-h-screen lg:static lg:shadow-2xl fixed inset-x-0 top-14 bottom-0 z-40 overflow-y-auto">
        <div class="flex items-center justify-between border-b border-white/10 px-4 py-4 lg:block lg:border-0 lg:px-5 lg:py-7">
            <div>
                {{-- <p class="text-[0.6rem] uppercase tracking-[0.4em] text-slate-400">IoT Panel</p> --}}
                <h1 class="mt-2 text-[1.75rem] font-black leading-tight sm:text-3xl">Monitoring<br>Mesin Heler</h1>
                <p class="mt-2 hidden text-sm text-slate-400 lg:block">Pantau data sensor mesin secara realtime.</p>
            </div>
            <button type="button" id="sidebar-close" class="hidden rounded-full border border-white/10 px-3 py-1 text-[0.65rem] uppercase tracking-[0.3em] text-slate-300 lg:hidden">Tutup</button>
        </div>

        <div class="px-4 pb-4 pt-4 lg:flex lg:min-h-[calc(100vh-112px)] lg:flex-col lg:px-5 lg:pb-6">
            <nav class="grid gap-2 sm:grid-cols-2 lg:block lg:space-y-3">
                <a href="{{ route('dashboard') }}" class="flex items-center justify-between rounded-2xl px-4 py-3 text-sm transition {{ request()->routeIs('dashboard') ? 'bg-white text-slate-900 shadow-lg' : 'bg-slate-900/60 text-slate-200 hover:bg-slate-800' }}">
                    <span class="font-semibold">Dashboard</span>
                    <span class="text-[0.7rem]">01</span>
                </a>
                <a href="{{ route('history') }}" class="flex items-center justify-between rounded-2xl px-4 py-3 text-sm transition {{ request()->routeIs('history') ? 'bg-white text-slate-900 shadow-lg' : 'bg-slate-900/60 text-slate-200 hover:bg-slate-800' }}">
                    <span class="font-semibold">Riwayat</span>
                    <span class="text-[0.7rem]">02</span>
                </a>
            </nav>

            <div class="mt-4 rounded-3xl bg-slate-800/80 p-4">
                <p class="text-[0.6rem] uppercase tracking-[0.3em] text-slate-400">Akun aktif</p>
                <p class="mt-2 text-base font-bold break-words">{{ auth()->user()->name }}</p>
                <p class="text-xs text-slate-400 break-words">{{ auth()->user()->email }}</p>
                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button type="submit" class="w-full rounded-2xl border border-slate-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700">Logout</button>
                </form>
            </div>
        </div>
    </aside>

    <main class="p-3 sm:p-4 lg:p-8">
        <div class="rounded-[1.35rem] bg-white p-4 shadow-sm sm:rounded-[1.75rem] sm:p-5 lg:p-6">
            <div class="flex flex-col gap-3 border-b border-slate-200 pb-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-[0.65rem] font-semibold uppercase tracking-[0.3em] text-slate-400">Selamat datang</p>
                    <h2 class="mt-1 text-xl font-black text-slate-900 sm:mt-2 sm:text-3xl">{{ $pageHeading ?? 'Dashboard' }}</h2>
                </div>
                <div class="rounded-2xl bg-slate-100 px-3 py-2.5 text-left md:text-right">
                    <p class="text-[0.6rem] uppercase tracking-[0.25em] text-slate-400">User</p>
                    <p class="text-sm font-bold text-slate-900 sm:text-base">{{ auth()->user()->name }}</p>
                </div>
            </div>

            <div class="pt-4 sm:pt-5">
                {{ $slot }}
            </div>
        </div>
    </main>
</div>

<script>
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebar-toggle');
    const close = document.getElementById('sidebar-close');
    const menuIcon = document.querySelector('[data-icon="menu"]');
    const closeIcon = document.querySelector('[data-icon="close"]');

    function setSidebar(open) {
        sidebar.classList.toggle('hidden', !open);
        sidebar.classList.toggle('block', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        menuIcon.classList.toggle('hidden', open);
        closeIcon.classList.toggle('hidden', !open);
    }

    toggle?.addEventListener('click', () => {
        setSidebar(sidebar.classList.contains('hidden'));
    });

    close?.addEventListener('click', () => setSidebar(false));

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 1024) {
            sidebar.classList.remove('hidden');
            sidebar.classList.add('block');
            menuIcon.classList.remove('hidden');
            closeIcon.classList.add('hidden');
            toggle.setAttribute('aria-expanded', 'false');
        } else if (window.innerWidth < 1024 && !sidebar.classList.contains('hidden')) {
            toggle.setAttribute('aria-expanded', 'true');
        }
    });
</script>
</body>
</html>
