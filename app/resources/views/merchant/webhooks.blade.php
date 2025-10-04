@extends('layouts.app')

@section('title', 'Webhooks')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold">Webhook Events</h2>
                    <div class="flex space-x-4">
                        <button onclick="sendTestWebhook()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Send Test Webhook
                        </button>
                        <a href="{{ route('dashboard') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Back to Dashboard
                        </a>
                    </div>
                </div>

                @if($webhooks->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Attempts</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Received</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($webhooks as $webhook)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $webhook->salla_event }}</div>
                                    <div class="text-sm text-gray-500">{{ $webhook->salla_event_id }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $webhook->status === 'sent' ? 'bg-green-100 text-green-800' : 
                                           ($webhook->status === 'failed' ? 'bg-red-100 text-red-800' : 
                                           ($webhook->status === 'skipped' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800')) }}">
                                        {{ ucfirst($webhook->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $webhook->attempts }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $webhook->created_at->format('M j, Y g:i A') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="viewWebhookDetails('{{ $webhook->id }}')" class="text-indigo-600 hover:text-indigo-900">
                                        View Details
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $webhooks->links() }}
                </div>
                @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No webhooks</h3>
                    <p class="mt-1 text-sm text-gray-500">No webhook events have been received yet.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Webhook Details Modal -->
<div id="webhookModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Webhook Details</h3>
                <button onclick="closeWebhookModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div id="webhookDetails" class="space-y-4">
                <!-- Content will be loaded here -->
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

function viewWebhookDetails(webhookId) {
    // This would typically fetch webhook details via AJAX
    // For now, we'll show a placeholder
    document.getElementById('webhookDetails').innerHTML = `
        <div class="bg-gray-50 p-4 rounded-md">
            <h4 class="font-medium text-gray-900 mb-2">Webhook ID: ${webhookId}</h4>
            <p class="text-sm text-gray-600">Detailed webhook information would be displayed here.</p>
        </div>
    `;
    document.getElementById('webhookModal').classList.remove('hidden');
}

function closeWebhookModal() {
    document.getElementById('webhookModal').classList.add('hidden');
}
</script>
@endsection
