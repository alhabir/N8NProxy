@extends('layouts.admin')

@section('title', 'Recent Webhooks')

@section('content')
    <div class="space-y-8">
        <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-white">Webhook Delivery History</h1>
                <p class="text-sm text-slate-400">Latest 100 webhook events processed across all merchants.</p>
            </div>
            <a href="{{ route('admin.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-700 px-4 py-2 text-sm text-slate-300 hover:bg-slate-800/70 transition">
                Back to Admin
            </a>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900/60 shadow-xl">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-800 text-left text-sm">
                    <thead class="bg-slate-900/80 text-xs font-semibold uppercase tracking-wide text-slate-400">
                    <tr>
                        <th class="px-4 py-3">Event</th>
                        <th class="px-4 py-3">Merchant</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Attempts</th>
                        <th class="px-4 py-3">Last Error</th>
                        <th class="px-4 py-3">Received</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/70">
                    @forelse($events as $event)
                        <tr class="hover:bg-slate-900/80">
                            <td class="px-4 py-4 align-top">
                                <div class="font-medium text-white">{{ $event->salla_event }}</div>
                                <div class="text-xs text-slate-400">{{ $event->salla_event_id }}</div>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <div class="text-sm text-slate-200">{{ $event->salla_merchant_id }}</div>
                                @if($event->payload['store']['name'] ?? false)
                                    <div class="text-xs text-slate-500">{{ $event->payload['store']['name'] }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-4 align-top">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $event->status === 'sent' ? 'bg-emerald-500/20 text-emerald-200' : ($event->status === 'failed' ? 'bg-rose-500/20 text-rose-200' : 'bg-amber-500/20 text-amber-200') }}">
                                    {{ strtoupper($event->status ?? 'pending') }}
                                </span>
                            </td>
                            <td class="px-4 py-4 align-top text-slate-200">
                                {{ $event->attempts ?? 0 }}
                            </td>
                            <td class="px-4 py-4 align-top">
                                @if($event->last_error)
                                    <div class="text-xs leading-relaxed text-rose-200/80">{{ \Illuminate\Support\Str::limit($event->last_error, 120) }}</div>
                                @else
                                    <span class="text-xs text-slate-500">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 align-top text-xs text-slate-400">
                                {{ $event->created_at?->toDayDateTimeString() }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-400">No webhook events recorded yet.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
