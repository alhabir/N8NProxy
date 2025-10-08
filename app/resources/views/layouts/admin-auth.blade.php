<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'N8NProxy') }} &mdash; Admin Access</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
</head>
<body class="flex min-h-full items-center justify-center bg-slate-950 px-4 py-12 text-slate-100">
    <div class="w-full max-w-xl rounded-3xl border border-slate-800 bg-slate-900/70 p-10 shadow-2xl shadow-slate-950/50 backdrop-blur">
        <div class="mb-8 text-center">
            <div class="inline-flex items-center gap-3 rounded-full border border-slate-800 bg-slate-950/80 px-4 py-2 text-sm font-semibold text-white">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-rose-500/20 text-rose-300">★</span>
                <span>Administrator Sign In</span>
            </div>
            <p class="mt-4 text-sm text-slate-400">
                Use administrator credentials to manage merchants, forwarding rules, and webhook diagnostics.
                This area is restricted to internal operators.
            </p>
        </div>

        {{ $slot }}
    </div>
</body>
</html>
