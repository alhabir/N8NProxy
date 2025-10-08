<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'N8NProxy') }} &mdash; Merchant Access</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
</head>
<body class="flex min-h-full items-center justify-center bg-slate-950 px-4 py-10 text-slate-100">
    <div class="w-full max-w-4xl overflow-hidden rounded-3xl border border-slate-800 bg-slate-900/60 shadow-2xl shadow-slate-950/60 backdrop-blur">
        <div class="grid gap-0 lg:grid-cols-[1.1fr,0.9fr]">
            <div class="relative hidden bg-gradient-to-b from-slate-900/90 via-slate-900/70 to-slate-950 lg:block">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(59,130,246,0.14),_transparent)]"></div>
                <div class="relative flex h-full flex-col justify-between p-10">
                    <div>
                        <span class="inline-flex items-center rounded-full border border-slate-700/60 bg-slate-900/70 px-3 py-1 text-xs uppercase tracking-wide text-slate-300">
                            Merchant Portal
                        </span>
                        <h1 class="mt-6 text-3xl font-semibold tracking-tight text-white">Connect your Salla store to n8n in minutes.</h1>
                        <p class="mt-4 text-sm leading-relaxed text-slate-300/80">
                            Manage webhooks, keep an eye on delivery health, and sync seamlessly with your automation workflows.
                            The merchant portal keeps your events organized while bridging straight into n8n.
                        </p>
                    </div>
                    <dl class="mt-12 grid grid-cols-1 gap-6 text-sm text-slate-300/80">
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-slate-400/70">Live Forwarding</dt>
                            <dd class="mt-1 font-medium text-slate-100">Streaming Salla events into n8n with detailed delivery logs.</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-slate-400/70">Security Controls</dt>
                            <dd class="mt-1 font-medium text-slate-100">Admin-managed test mode, with per-merchant authentication options.</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-slate-400/70">Real-Time Visibility</dt>
                            <dd class="mt-1 font-medium text-slate-100">Inspect webhook payloads, retry attempts, and status history any time.</dd>
                        </div>
                    </dl>
                </div>
            </div>
            <div class="flex flex-col justify-center bg-slate-950/60 px-6 py-10 sm:px-10">
                <div class="mx-auto w-full max-w-md">
                    <div class="mb-8 text-center">
                        <a href="{{ route('login') }}" class="inline-flex items-center gap-2 text-lg font-semibold text-white">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-indigo-500/20 text-indigo-300">
                                @
                            </span>
                            <span>N8NProxy Merchant</span>
                        </a>
                        <p class="mt-2 text-sm text-slate-400">Sign in or create an account to manage your n8n forwarding setup.</p>
                    </div>

                    <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-8 shadow-inner shadow-slate-950/40">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
