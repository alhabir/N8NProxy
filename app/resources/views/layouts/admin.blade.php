<!doctype html>
<html lang="en" class="h-full">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>@yield('title','Admin Panel')</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
</head>
<body class="min-h-full bg-slate-950 text-slate-100">
  <header class="border-b border-slate-800/80 bg-slate-900/40">
    <div class="mx-auto max-w-7xl px-4 py-4 flex items-center justify-between">
      <a href="{{ route('admin.index') }}" class="text-lg font-semibold tracking-wide text-white">
        N8NProxy • Admin
      </a>
      <nav class="flex items-center gap-2">
        <a href="{{ route('admin.index') }}" class="px-3 py-1.5 rounded-md text-slate-300 hover:text-white hover:bg-slate-800/70 transition {{ request()->routeIs('admin.index') ? 'bg-slate-800 text-white' : '' }}">Dashboard</a>
        <a href="{{ route('admin.merchants') }}" class="px-3 py-1.5 rounded-md text-slate-300 hover:text-white hover:bg-slate-800/70 transition {{ request()->routeIs('admin.merchants') ? 'bg-slate-800 text-white' : '' }}">Merchants</a>
        <a href="{{ route('admin.webhooks') }}" class="px-3 py-1.5 rounded-md text-slate-300 hover:text-white hover:bg-slate-800/70 transition {{ request()->routeIs('admin.webhooks') ? 'bg-slate-800 text-white' : '' }}">Webhooks</a>
        <a href="{{ route('admin.app-events.index') }}" class="px-3 py-1.5 rounded-md text-slate-300 hover:text-white hover:bg-slate-800/70 transition {{ request()->routeIs('admin.app-events.index') ? 'bg-slate-800 text-white' : '' }}">App Events</a>
        <a href="{{ route('admin.actions-audit') }}" class="px-3 py-1.5 rounded-md text-slate-300 hover:text-white hover:bg-slate-800/70 transition {{ request()->routeIs('admin.actions-audit') ? 'bg-slate-800 text-white' : '' }}">Actions Audit</a>
        <a href="{{ route('admin.app-settings') }}" class="px-3 py-1.5 rounded-md text-slate-300 hover:text-white hover:bg-slate-800/70 transition {{ request()->routeIs('admin.app-settings') ? 'bg-slate-800 text-white' : '' }}">Settings</a>
        @auth('admin')
        <form method="POST" action="{{ route('admin.logout') }}" class="inline ml-2">
            @csrf
            <button type="submit" class="px-3 py-1.5 rounded-md bg-indigo-500 text-white hover:bg-indigo-400 transition">
                Logout
            </button>
        </form>
        @endauth
      </nav>
    </div>
  </header>

  <main class="mx-auto max-w-7xl px-4 py-6">
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

    @if(session('warning'))
        <div class="mb-6 rounded-lg border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-sm text-amber-200">
            {{ session('warning') }}
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
  </main>
</body>
</html>
