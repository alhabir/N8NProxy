@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Approval Banner -->
        @if(!$merchant->is_approved)
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-6">
            <div class="flex">
                <div class="py-1">
                    <svg class="fill-current h-6 w-6 text-yellow-500 mr-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM9 11V9h2v6H9v-4zm0-6h2v2H9V5z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-bold">Account Pending Approval</p>
                    <p class="text-sm">Your account is pending admin approval. You'll receive an email once approved.</p>
                </div>
            </div>
        </div>
        @endif

        <!-- Setup Checklist -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6 text-gray-900">
                <h2 class="text-2xl font-bold mb-4">Setup Checklist</h2>
                <div class="space-y-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            @if($merchant->salla_merchant_id)
                                <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            @else
                                <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            @endif
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium {{ $merchant->salla_merchant_id ? 'text-green-700' : 'text-gray-500' }}">
                                Install "n8n ai" app in your Salla store
                            </p>
                            <p class="text-xs text-gray-500">
                                @if($merchant->salla_merchant_id)
                                    ✅ App installed and authorized
                                @else
                                    Install the app from Salla App Store
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            @if($merchant->n8n_base_url)
                                <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            @else
                                <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            @endif
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium {{ $merchant->n8n_base_url ? 'text-green-700' : 'text-gray-500' }}">
                                Configure your n8n URL
                            </p>
                            <p class="text-xs text-gray-500">
                                @if($merchant->n8n_base_url)
                                    ✅ n8n URL configured
                                @else
                                    <a href="{{ route('settings.n8n') }}" class="text-blue-600 hover:text-blue-800">Configure now</a>
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-500">
                                Test webhook delivery
                            </p>
                            <p class="text-xs text-gray-500">
                                @if($merchant->n8n_base_url)
                                    <button onclick="sendTestWebhook()" class="text-blue-600 hover:text-blue-800">Send test webhook</button>
                                @else
                                    Configure n8n URL first
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Total Webhooks</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ $recentWebhooks->count() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Successful</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ $recentWebhooks->where('status', 'sent')->count() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Actions Called</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ $recentActions->count() }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Webhooks -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Webhooks</h3>
                    <div class="space-y-3">
                        @forelse($recentWebhooks->take(5) as $webhook)
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $webhook->salla_event }}</p>
                                <p class="text-xs text-gray-500">{{ $webhook->created_at->diffForHumans() }}</p>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $webhook->status === 'sent' ? 'bg-green-100 text-green-800' : 
                                   ($webhook->status === 'failed' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                {{ ucfirst($webhook->status) }}
                            </span>
                        </div>
                        @empty
                        <p class="text-sm text-gray-500">No webhooks received yet.</p>
                        @endforelse
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('webhooks') }}" class="text-sm text-blue-600 hover:text-blue-800">View all webhooks</a>
                    </div>
                </div>
            </div>

            <!-- Recent Actions -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Actions</h3>
                    <div class="space-y-3">
                        @forelse($recentActions->take(5) as $action)
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ ucfirst($action->resource) }} - {{ $action->action }}</p>
                                <p class="text-xs text-gray-500">{{ $action->created_at->diffForHumans() }}</p>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $action->status_code >= 200 && $action->status_code < 300 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $action->status_code ?? 'N/A' }}
                            </span>
                        </div>
                        @empty
                        <p class="text-sm text-gray-500">No actions called yet.</p>
                        @endforelse
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('actions-audit') }}" class="text-sm text-blue-600 hover:text-blue-800">View all actions</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function sendTestWebhook() {
    if (confirm('Send a test webhook to your n8n instance?')) {
        fetch('{{ route("tests.send-webhook") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Test webhook sent successfully!');
                location.reload();
            } else {
                alert('Failed to send test webhook: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error sending test webhook: ' + error.message);
        });
    }
}
</script>
@endsection
