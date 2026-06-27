<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Laravel') }}</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-slate-100 text-slate-900 antialiased">
        <div class="min-h-screen flex flex-col items-center justify-center px-4 py-10">
            <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-xl">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
