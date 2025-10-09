@extends('layouts.merchant')

@section('title', 'Connect Salla Store')

@section('content')
    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-2xl border border-slate-800/40 bg-slate-950/60 shadow shadow-slate-950/30">
                <div class="border-b border-slate-800/60 bg-slate-900/70 px-6 py-5">
                    <h2 class="text-xl font-semibold text-white">Connect Your Salla Store</h2>
                    <p class="mt-1 text-sm text-slate-400">
                        Authorize the app inside Salla, then claim the store here to unlock n8n forwarding and Actions API access.
                    </p>
                </div>
                <div class="px-6 py-6 text-slate-200">
                    @if (session('success'))
                        <div class="mb-6 rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-6 rounded-lg border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-200">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if (session('warning'))
                        <div class="mb-6 rounded-lg border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-sm text-amber-200">
                            {{ session('warning') }}
                        </div>
                    @endif

                    <div class="mb-6 grid gap-4 sm:grid-cols-3">
                        <div class="rounded-xl border border-slate-800 bg-slate-950/70 p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Salla Authorization</p>
                            <p class="mt-2 text-lg font-semibold {{ $connection['has_tokens'] ? 'text-emerald-300' : 'text-amber-200' }}">
                                {{ $connection['has_tokens'] ? 'Connected' : 'Pending' }}
                            </p>
                            <p class="mt-1 text-xs text-slate-400">
                                {{ $connection['has_tokens'] ? 'Tokens received from Salla' : 'Install the app and authorize it in Salla' }}
                            </p>
                        </div>
                        <div class="rounded-xl border border-slate-800 bg-slate-950/70 p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Account Claim</p>
                            <p class="mt-2 text-lg font-semibold {{ $connection['is_claimed'] ? 'text-emerald-300' : 'text-amber-200' }}">
                                {{ $connection['is_claimed'] ? 'Linked' : 'Not Linked' }}
                            </p>
                            <p class="mt-1 text-xs text-slate-400">
                                {{ $connection['is_claimed'] ? 'This store is linked to your login' : 'Claim the store to enable dashboard features' }}
                            </p>
                        </div>
                        <div class="rounded-xl border border-slate-800 bg-slate-950/70 p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">n8n Forwarding</p>
                            <p class="mt-2 text-lg font-semibold {{ $connection['n8n_configured'] ? 'text-emerald-300' : 'text-slate-200' }}">
                                {{ $connection['n8n_configured'] ? 'Configured' : 'Configure Next' }}
                            </p>
                            <p class="mt-1 text-xs text-slate-400">
                                {{ $connection['n8n_configured'] ? 'n8n target is ready' : 'Configure n8n after claiming your store' }}
                            </p>
                        </div>
                    </div>

                    @if ($connection['has_tokens'] && $connection['is_claimed'])
                        <div class="rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-4 py-4 text-sm text-emerald-100">
                            <p class="font-semibold text-emerald-300">All set!</p>
                            <p class="mt-1">
                                Your Salla store is linked. You can now configure n8n settings and send test webhooks from the dashboard.
                            </p>
                        </div>
                    @else
                        <div class="rounded-lg border border-slate-800 bg-slate-950/70 px-5 py-5 text-sm text-slate-200">
                            <ol class="list-decimal space-y-2 pl-5 text-slate-300">
                                <li>Inside the Salla console, install/authorize the app to generate access tokens.</li>
                                <li>Paste your store domain (e.g., <code class="rounded bg-slate-800 px-1.5 py-0.5 text-xs">yourstore.salla.sa</code>) or Salla store ID below.</li>
                                <li>Submit to link the authorized store to your merchant account.</li>
                            </ol>
                        </div>

                        <form method="POST" action="{{ route('settings.connect-salla.claim') }}" class="mt-6 space-y-4">
                            @csrf

                            <div>
                                <label for="store_domain" class="block text-sm font-medium text-slate-200 mb-2">
                                    Store Domain or Salla Merchant ID
                                </label>
                                <input
                                    type="text"
                                    id="store_domain"
                                    name="store_domain"
                                    value="{{ old('store_domain') }}"
                                    class="w-full rounded-lg border border-slate-800 bg-slate-900 px-4 py-2 text-slate-100 shadow focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-400/40"
                                    placeholder="yourstore.salla.sa or 123456"
                                    required
                                >
                                @error('store_domain')
                                    <p class="mt-1 text-sm text-rose-300">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-slate-400">The value must match the store that authorized the app.</p>
                            </div>

                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('dashboard') }}" class="rounded-lg border border-slate-700 px-4 py-2 text-sm text-slate-200 transition hover:bg-slate-800">
                                    Cancel
                                </a>
                                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-400/60">
                                    Claim Store
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
