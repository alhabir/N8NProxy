@extends('layouts.admin')

@section('title', 'Manage Merchants')

@section('content')
    <div class="space-y-8">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-white">Merchants</h1>
                <p class="mt-1 text-sm text-slate-400">Search, review, approve, or remove merchants connected to the proxy.</p>
            </div>
            <a href="{{ route('admin.index') }}" class="rounded-lg border border-slate-700 px-4 py-2 text-sm text-slate-200 transition hover:bg-slate-800/70">
                Back to dashboard
            </a>
        </header>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-6 shadow shadow-slate-950/30">
            <form method="GET" class="grid gap-4 md:grid-cols-[minmax(0,1fr)_180px_140px]">
                <div>
                    <label for="search" class="text-xs uppercase tracking-wide text-slate-400">Search</label>
                    <input
                        id="search"
                        type="search"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Store name or email"
                        class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-sm text-white focus:border-indigo-500 focus:ring-indigo-500"
                    >
                </div>
                <div>
                    <label for="status" class="text-xs uppercase tracking-wide text-slate-400">Status</label>
                    <select
                        id="status"
                        name="status"
                        class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-sm text-white focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">All</option>
                        <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                        <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 rounded-lg bg-indigo-500 px-3 py-2 text-sm font-semibold text-white transition hover:bg-indigo-400">
                        Filter
                    </button>
                    <a href="{{ route('admin.merchants') }}" class="rounded-lg border border-slate-700 px-3 py-2 text-sm text-slate-200 transition hover:bg-slate-800/70">
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/70 shadow shadow-slate-950/30">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-800 text-left text-sm">
                    <thead class="bg-slate-900/80 text-xs font-semibold uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-4 py-3">Store</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">n8n Target</th>
                            <th class="px-4 py-3">Created</th>
                            <th class="px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/70">
                        @forelse ($merchants as $merchant)
                            <tr class="hover:bg-slate-900/80">
                                <td class="px-4 py-4 align-top">
                                    <div class="font-medium text-white">{{ $merchant->store_name ?: 'Unknown store' }}</div>
                                    <div class="text-xs text-slate-500">ID: {{ $merchant->salla_merchant_id ?: 'Not set' }}</div>
                                </td>
                                <td class="px-4 py-4 align-top text-slate-200">
                                    {{ $merchant->email }}
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $merchant->is_approved ? 'bg-emerald-500/20 text-emerald-200' : 'bg-amber-500/20 text-amber-200' }}">
                                        {{ $merchant->is_approved ? 'Approved' : 'Pending' }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 align-top text-xs text-slate-400">
                                    @if ($merchant->n8n_base_url)
                                        <p class="text-slate-200">{{ rtrim($merchant->n8n_base_url, '/') }}{{ $merchant->n8n_webhook_path ?? '/webhook/salla' }}</p>
                                        <p class="mt-1">Auth: <span class="font-semibold text-slate-300">{{ ucfirst($merchant->n8n_auth_type) }}</span></p>
                                    @else
                                        <span class="text-slate-500">Not configured</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 align-top text-xs text-slate-400">
                                    {{ $merchant->created_at?->format('M j, Y') }}
                                </td>
                                <td class="px-4 py-4 align-top text-sm">
                                    <div class="flex flex-wrap items-center gap-2">
                                        @if (! $merchant->is_approved)
                                            <form method="POST" action="{{ route('admin.merchants.approve', $merchant) }}">
                                                @csrf
                                                <button type="submit" class="rounded-lg bg-emerald-500/20 px-3 py-1 text-xs font-semibold text-emerald-200 transition hover:bg-emerald-500/30">
                                                    Approve
                                                </button>
                                            </form>
                                        @endif

                                        @if ($merchant->n8n_base_url)
                                            <form method="POST" action="{{ route('admin.tests.send-webhook', $merchant) }}">
                                                @csrf
                                                <button type="submit" class="rounded-lg bg-sky-500/20 px-3 py-1 text-xs font-semibold text-sky-200 transition hover:bg-sky-500/30">
                                                    Send test webhook
                                                </button>
                                            </form>
                                        @endif

                                        <form
                                            method="POST"
                                            action="{{ route('admin.merchants.destroy', $merchant) }}"
                                            onsubmit="return confirm('Delete this merchant? Their configuration and history will be retained for audit but hidden from the UI.');"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg bg-rose-500/20 px-3 py-1 text-xs font-semibold text-rose-200 transition hover:bg-rose-500/30">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-400">
                                    No merchants found for this filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-800/70 bg-slate-950/40 px-4 py-3">
                {{ $merchants->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection
