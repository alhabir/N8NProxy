@extends('layouts.app')

@section('title', 'Actions Audit')

@section('content')
    <div class="space-y-8">
        <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-white">Actions API Audit Trail</h1>
                <p class="text-sm text-slate-400">Latest 100 requests issued to the Salla Actions API across all merchants.</p>
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
                        <th class="px-4 py-3">Merchant</th>
                        <th class="px-4 py-3">Resource</th>
                        <th class="px-4 py-3">Method</th>
                        <th class="px-4 py-3">Endpoint</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Duration</th>
                        <th class="px-4 py-3">Requested</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/70">
                    @forelse($audits as $audit)
                        <tr class="hover:bg-slate-900/80">
                            <td class="px-4 py-4 align-top">
                                <div class="text-sm text-slate-200">{{ $audit->merchant_id ?: '—' }}</div>
                                <div class="text-xs text-slate-500">{{ $audit->salla_merchant_id }}</div>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <div class="font-medium text-white">{{ ucfirst($audit->resource ?? 'unknown') }}</div>
                                <div class="text-xs text-slate-400">{{ $audit->action ?? '—' }}</div>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold text-slate-100 bg-slate-800/80">
                                    {{ strtoupper($audit->method ?? 'GET') }}
                                </span>
                            </td>
                            <td class="px-4 py-4 align-top text-xs text-slate-300">
                                <code class="rounded bg-slate-950/60 px-2 py-1">{{ $audit->endpoint }}</code>
                            </td>
                            <td class="px-4 py-4 align-top">
                                @if($audit->status_code)
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $audit->status_code >= 200 && $audit->status_code < 300 ? 'bg-emerald-500/20 text-emerald-200' : 'bg-rose-500/20 text-rose-200' }}">
                                        {{ $audit->status_code }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-500">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 align-top text-xs text-slate-300">
                                {{ $audit->duration_ms ? number_format($audit->duration_ms) . ' ms' : '—' }}
                            </td>
                            <td class="px-4 py-4 align-top text-xs text-slate-400">
                                {{ $audit->created_at?->toDayDateTimeString() }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-400">No actions have been recorded yet.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
