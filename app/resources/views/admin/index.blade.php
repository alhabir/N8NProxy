@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')
    <div class="space-y-10">
        <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-white">Admin Control Centre</h1>
                <p class="text-sm text-slate-400">Monitor merchants, webhook traffic, and Actions API usage at a glance.</p>
            </div>
            <div class="flex flex-wrap gap-2 text-sm">
                <a href="{{ route('admin.app-settings') }}" class="rounded-lg border border-slate-700 px-4 py-2 text-slate-300 hover:bg-slate-800/70 transition">App Settings</a>
                <a href="{{ route('admin.webhooks') }}" class="rounded-lg border border-slate-700 px-4 py-2 text-slate-300 hover:bg-slate-800/70 transition">Webhooks</a>
                <a href="{{ route('admin.actions-audit') }}" class="rounded-lg border border-slate-700 px-4 py-2 text-slate-300 hover:bg-slate-800/70 transition">Actions Audit</a>
                <a href="{{ route('admin.merchants') }}" class="rounded-lg border border-slate-700 px-4 py-2 text-slate-300 hover:bg-slate-800/70 transition">Merchants</a>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <a href="{{ route('admin.app-events.index', ['event_name' => 'app.installed']) }}" class="group rounded-xl border border-emerald-600/40 bg-emerald-500/10 p-6 shadow transition hover:border-emerald-400/60 hover:bg-emerald-500/15">
                <p class="text-sm font-medium text-emerald-200/80">App Installs</p>
                <p class="mt-2 text-3xl font-semibold text-white">{{ number_format($appEventStats['installs'] ?? 0) }}</p>
                <p class="mt-2 text-xs text-emerald-200/70 group-hover:text-emerald-100">View install activity →</p>
            </a>
            <a href="{{ route('admin.app-events.index', ['event_name' => 'app.uninstalled']) }}" class="group rounded-xl border border-rose-600/40 bg-rose-500/10 p-6 shadow transition hover:border-rose-400/60 hover:bg-rose-500/15">
                <p class="text-sm font-medium text-rose-200/80">App Uninstalls</p>
                <p class="mt-2 text-3xl font-semibold text-white">{{ number_format($appEventStats['uninstalls'] ?? 0) }}</p>
                <p class="mt-2 text-xs text-rose-200/70 group-hover:text-rose-100">View uninstall activity →</p>
            </a>
        </div>

        <div class="grid gap-6 md:grid-cols-3">
            <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-6 shadow">
                <p class="text-sm text-slate-400">Merchants</p>
                <p class="mt-2 text-3xl font-semibold text-white">{{ number_format($counts['merchants'] ?? 0) }}</p>
            </div>
            <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-6 shadow">
                <p class="text-sm text-slate-400">Webhook Events</p>
                <p class="mt-2 text-3xl font-semibold text-white">{{ number_format($counts['webhook_events'] ?? 0) }}</p>
            </div>
            <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-6 shadow">
                <p class="text-sm text-slate-400">Actions Logged</p>
                <p class="mt-2 text-3xl font-semibold text-white">{{ number_format($counts['salla_action_audits'] ?? 0) }}</p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-xl border border-slate-800 bg-slate-900/60 shadow">
                <div class="border-b border-slate-800 px-6 py-4">
                    <h2 class="text-lg font-semibold text-white">Latest Webhooks</h2>
                    <p class="text-xs text-slate-500">Most recent deliveries across the platform.</p>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        @forelse($recentWebhooks as $event)
                            <div class="flex flex-col rounded-lg border border-slate-800/70 bg-slate-950/40 p-4 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <p class="text-sm font-medium text-white">{{ $event->salla_event }}</p>
                                    <p class="text-xs text-slate-400">{{ $event->salla_merchant_id }} • {{ $event->salla_event_id }}</p>
                                </div>
                                <div class="mt-3 flex items-center gap-3 md:mt-0">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $event->status === 'sent' ? 'bg-emerald-500/20 text-emerald-200' : ($event->status === 'failed' ? 'bg-rose-500/20 text-rose-200' : 'bg-amber-500/20 text-amber-200') }}">
                                        {{ strtoupper($event->status ?? 'PENDING') }}
                                    </span>
                                    <span class="text-xs text-slate-500">{{ $event->created_at?->diffForHumans() }}</span>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-400">No webhook events recorded yet.</p>
                        @endforelse
                    </div>
                    <div class="mt-6 text-right">
                        <a href="{{ route('admin.webhooks') }}" class="text-sm text-indigo-300 hover:text-indigo-200">View all webhooks →</a>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-900/60 shadow">
                <div class="border-b border-slate-800 px-6 py-4">
                    <h2 class="text-lg font-semibold text-white">Latest Actions</h2>
                    <p class="text-xs text-slate-500">Recent Salla Actions API calls.</p>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        @forelse($recentActions as $audit)
                            <div class="flex flex-col rounded-lg border border-slate-800/70 bg-slate-950/40 p-4 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <p class="text-sm font-medium text-white">{{ ucfirst($audit->resource ?? 'unknown') }} • {{ $audit->action ?? '—' }}</p>
                                    <p class="text-xs text-slate-400">{{ $audit->salla_merchant_id }} • {{ $audit->method ?? 'GET' }}</p>
                                </div>
                                <div class="mt-3 flex items-center gap-3 md:mt-0">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $audit->status_code >= 200 && $audit->status_code < 300 ? 'bg-emerald-500/20 text-emerald-200' : 'bg-rose-500/20 text-rose-200' }}">
                                        {{ $audit->status_code ?? '—' }}
                                    </span>
                                    <span class="text-xs text-slate-500">{{ $audit->created_at?->diffForHumans() }}</span>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-400">No actions recorded yet.</p>
                        @endforelse
                    </div>
                    <div class="mt-6 text-right">
                        <a href="{{ route('admin.actions-audit') }}" class="text-sm text-indigo-300 hover:text-indigo-200">View audit log →</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
