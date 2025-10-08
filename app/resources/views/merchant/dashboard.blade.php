@extends('layouts.merchant')

@section('title', 'Dashboard')

@section('content')
    <div class="space-y-8">
        @if (! $merchant->is_approved)
            <div class="rounded-2xl border border-amber-500/40 bg-amber-500/10 px-6 py-4 text-amber-100">
                <div class="flex items-start gap-3">
                    <span class="mt-0.5 inline-flex h-8 w-8 items-center justify-center rounded-full bg-amber-400/30 text-amber-200">!</span>
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide">Account Pending Approval</p>
                        <p class="mt-1 text-sm text-amber-200/80">
                            Our team is reviewing your registration. You will receive an email once the account is approved.
                            Until then, forwarding webhooks and test mode remain disabled.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <div class="grid gap-4 md:grid-cols-2">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-6 shadow shadow-slate-950/30">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-400">n8n Forwarding</p>
                        <p class="mt-2 text-xl font-semibold text-white">
                            {{ $merchant->n8n_base_url ? 'Configured' : 'Not configured yet' }}
                        </p>
                        <p class="mt-1 text-sm text-slate-400">
                            @if ($merchant->n8n_base_url)
                                {{ rtrim($merchant->n8n_base_url, '/') }}{{ $merchant->n8n_webhook_path ?? '/webhook/salla' }}
                            @else
                                Add your n8n base URL and webhook path to start forwarding webhooks.
                            @endif
                        </p>
                    </div>
                    <a href="{{ route('settings.n8n') }}" class="rounded-lg border border-slate-700 px-3 py-1.5 text-sm text-slate-200 transition hover:bg-slate-800">
                        Manage
                    </a>
                </div>
                <dl class="mt-6 grid gap-4 text-sm text-slate-300 sm:grid-cols-3">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Auth Method</dt>
                        <dd class="mt-1 font-semibold">{{ ucfirst($merchant->n8n_auth_type ?? 'none') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Test Mode</dt>
                        <dd class="mt-1 font-semibold {{ $allowTestMode ? 'text-emerald-300' : 'text-slate-300' }}">
                            {{ $allowTestMode ? 'Enabled by admin' : 'Disabled' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Forward Status</dt>
                        <dd class="mt-1 font-semibold {{ $merchant->n8n_base_url ? 'text-emerald-300' : 'text-slate-300' }}">
                            {{ $merchant->n8n_base_url ? 'Ready' : 'Action required' }}
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-6 shadow shadow-slate-950/30">
                <p class="text-xs uppercase tracking-wide text-slate-400">Webhook Activity (last 20)</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-400">Received</p>
                        <p class="mt-2 text-2xl font-semibold text-white">{{ $recentWebhooks->count() }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-400">Delivered</p>
                        <p class="mt-2 text-2xl font-semibold text-emerald-300">{{ $recentWebhooks->where('status', 'sent')->count() }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-400">Failed / Skipped</p>
                        <p class="mt-2 text-2xl font-semibold text-rose-300">{{ $recentWebhooks->whereIn('status', ['failed', 'skipped'])->count() }}</p>
                    </div>
                </div>
                <div class="mt-6 text-sm text-slate-400">
                    <p>
                        Latest event:
                        @if ($recentWebhooks->isEmpty())
                            <span class="font-medium text-slate-200">No activity yet.</span>
                        @else
                            <span class="font-medium text-slate-200">{{ $recentWebhooks->first()->salla_event }}</span>
                            <span class="text-xs text-slate-500">&middot; {{ $recentWebhooks->first()->created_at->diffForHumans() }}</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-6 shadow shadow-slate-950/30">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-white">Quick Actions</h2>
                    <p class="text-sm text-slate-400">Send a sample webhook once your account is approved and test mode is enabled.</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('webhooks') }}" class="rounded-lg border border-slate-700 px-3 py-1.5 text-sm text-slate-200 transition hover:bg-slate-800">View Webhooks</a>
                    <a href="{{ route('actions-audit') }}" class="rounded-lg border border-slate-700 px-3 py-1.5 text-sm text-slate-200 transition hover:bg-slate-800">View Actions</a>
                    <form method="POST" action="{{ route('tests.send-webhook') }}">
                        @csrf
                        <button
                            type="submit"
                            class="rounded-lg bg-indigo-500 px-3 py-1.5 text-sm font-semibold text-white transition hover:bg-indigo-400 disabled:cursor-not-allowed disabled:opacity-60"
                            @disabled(! $allowTestMode || ! $merchant->n8n_base_url || ! $merchant->is_approved)
                        >
                            Send Test Webhook
                        </button>
                    </form>
                </div>
            </div>
            @if (! $allowTestMode || ! $merchant->n8n_base_url || ! $merchant->is_approved)
                <p class="mt-3 text-xs text-slate-400">
                    Test webhook requires: admin test mode enabled, your n8n URL configured, and an approved account.
                </p>
            @endif
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-6 shadow shadow-slate-950/30">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-white">Recent Webhooks</h3>
                    <a href="{{ route('webhooks') }}" class="text-sm text-indigo-300 hover:text-indigo-200">View all</a>
                </div>
                <div class="mt-4 space-y-4">
                    @forelse ($recentWebhooks->take(5) as $event)
                        <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-white">{{ $event->salla_event }}</p>
                                    <p class="text-xs text-slate-400">{{ $event->created_at->diffForHumans() }}</p>
                                </div>
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $event->status === 'sent' ? 'bg-emerald-500/20 text-emerald-200' : ($event->status === 'failed' ? 'bg-rose-500/20 text-rose-200' : 'bg-amber-500/20 text-amber-200') }}">
                                    {{ strtoupper($event->status ?? 'pending') }}
                                </span>
                            </div>
                            <p class="mt-3 text-xs text-slate-400">ID: {{ $event->salla_event_id }}</p>
                        </div>
                    @empty
                        <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-6 text-sm text-slate-400">
                            No webhooks received yet.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-6 shadow shadow-slate-950/30">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-white">Recent Actions</h3>
                    <a href="{{ route('actions-audit') }}" class="text-sm text-indigo-300 hover:text-indigo-200">View audit log</a>
                </div>
                <div class="mt-4 space-y-4">
                    @forelse ($recentActions->take(5) as $audit)
                        <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-white">{{ ucfirst($audit->resource) }} &middot; {{ $audit->action }}</p>
                                    <p class="text-xs text-slate-400">{{ $audit->created_at->diffForHumans() }}</p>
                                </div>
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $audit->status_code >= 200 && $audit->status_code < 300 ? 'bg-emerald-500/20 text-emerald-200' : 'bg-rose-500/20 text-rose-200' }}">
                                    {{ $audit->status_code ?? 'N/A' }}
                                </span>
                            </div>
                            @if ($audit->notes)
                                <p class="mt-2 text-xs text-slate-400">{{ \Illuminate\Support\Str::limit($audit->notes, 120) }}</p>
                            @endif
                        </div>
                    @empty
                        <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-6 text-sm text-slate-400">
                            No Salla actions have been triggered yet.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
