@extends('layouts.admin')

@section('title', 'App Settings')

@section('content')
    <div class="space-y-8">
        <div>
            <h1 class="text-3xl font-semibold text-white">Application Settings</h1>
            <p class="mt-2 text-sm text-slate-400">Manage the global forwarding behaviour and security settings used by the proxy.</p>
        </div>

        <div class="rounded-xl border border-slate-800 bg-slate-900/60 shadow-xl">
            <form method="POST" action="{{ route('admin.app-settings') }}" class="p-6 space-y-8">
                @csrf

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div class="space-y-2">
                        <label for="actions_api_bearer" class="block text-sm font-medium text-slate-200">ACTIONS_API_BEARER</label>
                        <p class="text-xs text-slate-400">Bearer token used when calling Salla Actions API from background jobs.</p>
                        <input type="text"
                               id="actions_api_bearer"
                               name="actions_api_bearer"
                               value="{{ old('actions_api_bearer', $settings['ACTIONS_API_BEARER'] ?? '') }}"
                               class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-sm text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/40"
                               placeholder="sk_live_...">
                    </div>

                    <div class="space-y-2">
                        <label for="forward_default_timeout_ms" class="block text-sm font-medium text-slate-200">FORWARD_DEFAULT_TIMEOUT_MS</label>
                        <p class="text-xs text-slate-400">Timeout applied to synchronous webhook forwarding attempts (in milliseconds).</p>
                        <input type="number"
                               min="100"
                               max="120000"
                               step="100"
                               id="forward_default_timeout_ms"
                               name="forward_default_timeout_ms"
                               value="{{ old('forward_default_timeout_ms', $settings['FORWARD_DEFAULT_TIMEOUT_MS'] ?? 5000) }}"
                               class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-sm text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/40">
                    </div>

                    <div class="space-y-2">
                        <label for="forward_sync_retries" class="block text-sm font-medium text-slate-200">FORWARD_SYNC_RETRIES</label>
                        <p class="text-xs text-slate-400">Number of immediate retries performed when a webhook fails synchronously.</p>
                        <input type="number"
                               min="0"
                               max="10"
                               id="forward_sync_retries"
                               name="forward_sync_retries"
                               value="{{ old('forward_sync_retries', $settings['FORWARD_SYNC_RETRIES'] ?? 0) }}"
                               class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-sm text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/40">
                    </div>

                    <div class="space-y-2">
                        <label for="forward_retry_schedule_max_attempts" class="block text-sm font-medium text-slate-200">FORWARD_RETRY_SCHEDULE_MAX_ATTEMPTS</label>
                        <p class="text-xs text-slate-400">Maximum number of scheduled retries that will be queued for failed webhooks.</p>
                        <input type="number"
                               min="0"
                               max="50"
                               id="forward_retry_schedule_max_attempts"
                               name="forward_retry_schedule_max_attempts"
                               value="{{ old('forward_retry_schedule_max_attempts', $settings['FORWARD_RETRY_SCHEDULE_MAX_ATTEMPTS'] ?? 0) }}"
                               class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-sm text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/40">
                    </div>
                </div>

                <div class="mt-4">
                    <label for="allow_test_mode" class="block text-sm font-medium text-slate-300 mb-2">
                        ALLOW_TEST_MODE
                    </label>
                    <p class="text-xs text-slate-500 mb-2">Permit merchants to trigger test webhooks from the dashboard UI.</p>

                    <input type="hidden" name="allow_test_mode" value="0">
                    <label class="relative inline-flex items-center cursor-pointer select-none">
                        <input type="checkbox" id="allow_test_mode" name="allow_test_mode" value="1"
                            class="sr-only peer"
                            {{ old('allow_test_mode', $settings['ALLOW_TEST_MODE'] ?? '0') == '1' ? 'checked' : '' }}>

                        <div class="w-11 h-6 bg-slate-600 rounded-full peer-focus:ring-2 peer-focus:ring-sky-400 peer-checked:bg-sky-500 transition-colors duration-300"></div>

                        <span class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full shadow-sm transition-transform duration-300 peer-checked:translate-x-5"></span>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-4">
                    <a href="{{ route('admin.index') }}" class="rounded-lg border border-slate-700 px-4 py-2 text-sm text-slate-300 hover:bg-slate-800/70 transition">Cancel</a>
                    <button type="submit" class="rounded-lg bg-indigo-500 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-400 transition">
                        Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
