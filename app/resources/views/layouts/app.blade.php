<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'N8NProxy') }} - @yield('title', 'Dashboard')</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
</head>
<body class="font-sans antialiased bg-slate-950 text-slate-100">
<div class="min-h-screen flex flex-col">
    <header class="border-b border-slate-800 bg-slate-900/70 backdrop-blur">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-10">
                    <a href="{{ auth()->check() ? route('dashboard') : url('/') }}" class="text-lg font-semibold tracking-wide text-white">
                        {{ config('app.name', 'N8NProxy') }}
                    </a>
                    @auth
                        <nav class="hidden md:flex items-center gap-4 text-sm">
                            <a href="{{ route('dashboard') }}"
                               class="px-3 py-1.5 rounded-md transition-colors {{ request()->routeIs('dashboard') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:text-white hover:bg-slate-800/70' }}">
                                Dashboard
                            </a>
                            <a href="{{ route('webhooks') }}"
                               class="px-3 py-1.5 rounded-md transition-colors {{ request()->routeIs('webhooks') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:text-white hover:bg-slate-800/70' }}">
                                Webhooks
                            </a>
                            <a href="{{ route('actions-audit') }}"
                               class="px-3 py-1.5 rounded-md transition-colors {{ request()->routeIs('actions-audit') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:text-white hover:bg-slate-800/70' }}">
                                Actions Audit
                            </a>
                            @if(auth()->user()->is_admin ?? false)
                                <a href="{{ route('admin.index') }}"
                                   class="px-3 py-1.5 rounded-md transition-colors {{ request()->routeIs('admin.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:text-white hover:bg-slate-800/70' }}">
                                    Admin
                                </a>
                            @endif
                        </nav>
                    @endauth
                </div>
                <div class="flex items-center gap-4 text-sm">
                    @auth
                        <span class="hidden sm:inline text-slate-300">{{ auth()->user()->email }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 rounded-md bg-indigo-500 text-white hover:bg-indigo-400 transition">
                                Logout
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="px-3 py-1.5 rounded-md bg-indigo-500 text-white hover:bg-indigo-400 transition">Login</a>
                        <a href="{{ route('register') }}" class="px-3 py-1.5 rounded-md bg-slate-800 text-slate-200 hover:bg-slate-700 transition">Register</a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <main class="flex-1">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            @if(session('success'))
                <div class="mb-6 rounded-lg border border-emerald-600/40 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 rounded-lg border border-rose-600/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-200">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 rounded-lg border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-sm text-amber-200">
                    <p class="font-medium">There were some problems with your submission:</p>
                    <ul class="mt-2 list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    <footer class="border-t border-slate-800 bg-slate-900/70">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 text-xs text-slate-500">
            &copy; {{ now()->year }} {{ config('app.name', 'N8NProxy') }}. All rights reserved.
        </div>
    </footer>
</div>
</body>
</html>
