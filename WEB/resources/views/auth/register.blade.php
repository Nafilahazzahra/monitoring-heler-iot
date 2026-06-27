<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Monitoring Mesin Heler</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-[linear-gradient(135deg,#e2e8f0,#cbd5e1,#f8fafc)] px-4 py-8 text-slate-900">
    <div class="mx-auto grid min-h-[calc(100vh-4rem)] max-w-5xl items-center gap-8 lg:grid-cols-[0.95fr_1.05fr]">
        <section class="rounded-[1.5rem] bg-slate-900 p-7 text-white shadow-2xl sm:p-8">
            <p class="text-xs uppercase tracking-[0.35em] text-slate-400">Registrasi</p>
            <h1 class="mt-3 text-3xl font-black leading-tight sm:text-4xl">Buat akun<br>Monitoring Anda</h1>
            <p class="mt-3 max-w-md text-sm text-slate-300 sm:text-base">Setelah register, Anda akan langsung masuk ke dashboard untuk melihat data sensor mesin heler.</p>
            <div class="mt-7 space-y-3 text-sm text-slate-300">
                <div class="rounded-2xl bg-white/10 p-3.5">1. Buat akun dengan nama, email, dan password.</div>
                <div class="rounded-2xl bg-white/10 p-3.5">2. Login otomatis setelah register berhasil.</div>
                <div class="rounded-2xl bg-white/10 p-3.5">3. Dashboard menampilkan data sensor terbaru dan riwayat.</div>
            </div>
        </section>

        <section class="mx-auto w-full max-w-md rounded-[1.5rem] bg-white p-6 shadow-2xl sm:p-7">
            <div class="mb-6 text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-400">Register</p>
                <h2 class="mt-2 text-3xl font-black text-slate-900 sm:text-[2rem]">Akun Baru</h2>
                <p class="mt-2 text-sm text-slate-500">Isi form di bawah untuk mulai menggunakan sistem.</p>
            </div>

            <form method="POST" action="{{ route('register') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="text-sm font-bold text-slate-700">Username</label>
                    <input type="text" name="name" value="{{ old('name') }}" required autofocus class="mt-1.5 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                    @error('name')
                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-bold text-slate-700">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required class="mt-1.5 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
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

                <div>
                    <label class="text-sm font-bold text-slate-700">Konfirmasi Password</label>
                    <input type="password" name="password_confirmation" required class="mt-1.5 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                </div>

                <button type="submit" class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-slate-800">Register dan Masuk</button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-500">Sudah punya akun? <a href="{{ route('login') }}" class="font-bold text-slate-900 hover:text-slate-700">Kembali ke login</a></p>
        </section>
    </div>
</body>
</html>
