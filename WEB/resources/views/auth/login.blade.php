<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Monitoring Mesin Heler</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-[radial-gradient(circle_at_top,#1f2937,#020617_60%)] px-4 py-8 text-slate-900">
    <div class="mx-auto grid min-h-[calc(100vh-4rem)] max-w-5xl items-center gap-8 lg:grid-cols-[1.15fr_0.85fr]">
        <section class="hidden rounded-[2rem] bg-white/10 p-10 text-white shadow-2xl backdrop-blur lg:block">
            <p class="text-sm uppercase tracking-[0.4em] text-slate-300">IoT Monitoring</p>
            <h1 class="mt-5 text-5xl font-black leading-tight">Mesin Heler<br>Realtime Dashboard</h1>
            <p class="mt-5 max-w-lg text-lg text-slate-200">Masuk untuk memantau suhu, aliran air, level air, dan status getaran mesin heler langsung dari laptop Anda.</p>
            <div class="mt-10 grid grid-cols-2 gap-4">
                <div class="rounded-3xl bg-white/10 p-5">
                    <p class="text-sm text-slate-300">Akses</p>
                    <p class="mt-2 text-2xl font-bold">Dashboard</p>
                </div>
                <div class="rounded-3xl bg-white/10 p-5">
                    <p class="text-sm text-slate-300">Data</p>
                    <p class="mt-2 text-2xl font-bold">Riwayat Sensor</p>
                </div>
            </div>
        </section>

        <section class="mx-auto w-full max-w-md rounded-[1.5rem] bg-white p-6 shadow-2xl sm:p-7">
            <div class="mb-6 text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-400">Login</p>
                <h2 class="mt-2 text-3xl font-black text-slate-900 sm:text-[2rem]">Monitoring Mesin Heler</h2>
                <p class="mt-2 text-sm text-slate-500">Masuk untuk membuka dashboard.</p>
            </div>

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="text-sm font-bold text-slate-700">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus class="mt-1.5 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                    @error('email')
                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-bold text-slate-700">Password</label>
                    <input type="password" name="password" required class="mt-1.5 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                    @error('password')
                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between text-sm text-slate-500">
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-slate-900 focus:ring-slate-400">
                        <span>Ingat saya</span>
                    </label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="font-semibold text-slate-700 hover:text-slate-900">Lupa password?</a>
                    @endif
                </div>

                <button type="submit" class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-slate-800">Masuk</button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-500">Belum punya akun? <a href="{{ route('register') }}" class="font-bold text-slate-900 hover:text-slate-700">Register sekarang</a></p>
        </section>
    </div>
</body>
</html>
