@extends('layouts.admin')

@section('title', 'App Events Logs')

@section('content')
    <div class="space-y-10">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-white">App Events Logs</h1>
                <p class="text-sm text-slate-400">Track every Salla app lifecycle signal captured by the unified webhook.</p>
            </div>
            <a href="{{ route('admin.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-700 px-4 py-2 text-sm text-slate-300 transition hover:bg-slate-800/70">
                ← Back to Dashboard
            </a>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <a href="{{ route('admin.app-events.index', ['event_name' => 'app.installed']) }}" class="group rounded-xl border border-emerald-600/40 bg-emerald-500/10 p-6 shadow transition hover:border-emerald-400/60 hover:bg-emerald-500/15">
                <p class="text-sm font-medium text-emerald-200/80">App Installs</p>
                <p class="mt-2 text-3xl font-semibold text-white">{{ number_format($stats['installs'] ?? 0) }}</p>
                <p class="mt-2 text-xs text-emerald-200/70 group-hover:text-emerald-100">Filter installs →</p>
            </a>
            <a href="{{ route('admin.app-events.index', ['event_name' => 'app.uninstalled']) }}" class="group rounded-xl border border-rose-600/40 bg-rose-500/10 p-6 shadow transition hover:border-rose-400/60 hover:bg-rose-500/15">
                <p class="text-sm font-medium text-rose-200/80">App Uninstalls</p>
                <p class="mt-2 text-3xl font-semibold text-white">{{ number_format($stats['uninstalls'] ?? 0) }}</p>
                <p class="mt-2 text-xs text-rose-200/70 group-hover:text-rose-100">Filter uninstalls →</p>
            </a>
        </div>

        <div class="rounded-xl border border-slate-800 bg-slate-900/60 shadow">
            <div class="border-b border-slate-800 px-6 py-4">
                <h2 class="text-lg font-semibold text-white">Filters</h2>
                <p class="text-xs text-slate-500">Narrow the log by event type or received window.</p>
            </div>
            <div class="px-6 py-4">
                <form method="GET" action="{{ route('admin.app-events.index') }}" class="grid gap-4 md:grid-cols-4 md:items-end">
                    <div class="md:col-span-2">
                        <label for="event_name" class="block text-xs uppercase tracking-wide text-slate-400">Event Name</label>
                        <select id="event_name" name="event_name" class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950/80 px-3 py-2 text-sm text-slate-100 focus:border-indigo-500 focus:outline-none">
                            <option value="">All events</option>
                            @foreach($eventNames as $eventName)
                                <option value="{{ $eventName }}" @selected(($filters['event_name'] ?? null) === $eventName)>{{ $eventName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="from" class="block text-xs uppercase tracking-wide text-slate-400">From</label>
                        <input id="from" type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950/80 px-3 py-2 text-sm text-slate-100 focus:border-indigo-500 focus:outline-none" />
                    </div>
                    <div>
                        <label for="to" class="block text-xs uppercase tracking-wide text-slate-400">To</label>
                        <input id="to" type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950/80 px-3 py-2 text-sm text-slate-100 focus:border-indigo-500 focus:outline-none" />
                    </div>
                    <div class="md:col-span-4 flex flex-wrap items-center gap-3">
                        <button type="submit" class="rounded-lg bg-indigo-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-400">Apply filters</button>
                        <a href="{{ route('admin.app-events.index') }}" class="text-sm text-slate-400 hover:text-slate-200">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="rounded-xl border border-slate-800 bg-slate-900/60 shadow">
            <div class="border-b border-slate-800 px-6 py-4">
                <h2 class="text-lg font-semibold text-white">Event Activity</h2>
                <p class="text-xs text-slate-500">Newest events first, leveraging event timestamps when provided by Salla.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-800 text-sm">
                    <thead class="bg-slate-900/80 text-xs uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-6 py-3 text-left">Event</th>
                            <th class="px-6 py-3 text-left">Salla Merchant ID</th>
                            <th class="px-6 py-3 text-left">Date &amp; Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse($events as $event)
                            @php
                                $timestamp = $event->event_created_at ?? $event->created_at;
                                $timezone = config('app.timezone', 'UTC');
                            @endphp
                            <tr class="hover:bg-slate-900/70 transition">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-white">{{ $event->event_name }}</div>
                                    <div class="text-xs text-slate-400">#{{ $event->id }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="rounded border border-slate-700 bg-slate-950/60 px-2 py-1 text-xs text-slate-200">
                                        {{ $event->salla_merchant_id ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    @if($timestamp)
                                        <div class="text-sm text-white">
                                            {{ $timestamp->copy()->timezone($timezone)->format('Y-m-d H:i:s') }}
                                        </div>
                                        <div class="text-xs text-slate-400">{{ $timestamp->diffForHumans() }}</div>
                                    @else
                                        <span class="text-sm text-slate-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-8 text-center text-sm text-slate-400">
                                    No app events recorded for this filter set yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-800 px-6 py-4">
                {{ $events->links() }}
            </div>
        </div>
    </div>
@endsection
