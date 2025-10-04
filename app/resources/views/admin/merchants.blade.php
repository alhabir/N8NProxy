@extends('layouts.app')

@section('title', 'Manage Merchants')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold">Manage Merchants</h2>
                    <a href="{{ route('admin.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Back to Dashboard
                    </a>
                </div>

                <!-- Filters -->
                <div class="mb-6">
                    <form method="GET" class="flex space-x-4">
                        <div>
                            <input type="text" 
                                   name="search" 
                                   value="{{ request('search') }}"
                                   placeholder="Search by store name or email..."
                                   class="px-3 py-2 border border-gray-300 rounded-md text-sm">
                        </div>
                        <div>
                            <select name="status" class="px-3 py-2 border border-gray-300 rounded-md text-sm">
                                <option value="">All Status</option>
                                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            </select>
                        </div>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                            Filter
                        </button>
                        <a href="{{ route('admin.merchants') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Clear
                        </a>
                    </form>
                </div>

                @if($merchants->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Store</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">n8n URL</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($merchants as $merchant)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $merchant->store_name ?: 'Unknown Store' }}</div>
                                    <div class="text-sm text-gray-500">ID: {{ $merchant->salla_merchant_id ?: 'Not set' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $merchant->email }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $merchant->is_approved ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ $merchant->is_approved ? 'Approved' : 'Pending' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if($merchant->n8n_base_url)
                                        <div class="text-sm">{{ $merchant->n8n_base_url }}{{ $merchant->n8n_path }}</div>
                                        <div class="text-xs text-gray-500">{{ ucfirst($merchant->n8n_auth_type) }} auth</div>
                                    @else
                                        <span class="text-sm text-gray-500">Not configured</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $merchant->created_at->format('M j, Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    @if(!$merchant->is_approved)
                                    <form method="POST" action="{{ route('admin.merchants.approve', $merchant) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-green-600 hover:text-green-900">
                                            Approve
                                        </button>
                                    </form>
                                    @endif
                                    
                                    @if($merchant->n8n_base_url)
                                    <form method="POST" action="{{ route('admin.tests.send-webhook', $merchant) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-blue-600 hover:text-blue-900">
                                            Test Webhook
                                        </button>
                                    </form>
                                    @endif
                                    
                                    <button onclick="viewMerchantDetails('{{ $merchant->id }}')" class="text-indigo-600 hover:text-indigo-900">
                                        Details
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $merchants->links() }}
                </div>
                @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No merchants</h3>
                    <p class="mt-1 text-sm text-gray-500">No merchants have registered yet.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Merchant Details Modal -->
<div id="merchantModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Merchant Details</h3>
                <button onclick="closeMerchantModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div id="merchantDetails" class="space-y-4">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
function viewMerchantDetails(merchantId) {
    // This would typically fetch merchant details via AJAX
    // For now, we'll show a placeholder
    document.getElementById('merchantDetails').innerHTML = `
        <div class="bg-gray-50 p-4 rounded-md">
            <h4 class="font-medium text-gray-900 mb-2">Merchant ID: ${merchantId}</h4>
            <p class="text-sm text-gray-600">Detailed merchant information would be displayed here.</p>
        </div>
    `;
    document.getElementById('merchantModal').classList.remove('hidden');
}

function closeMerchantModal() {
    document.getElementById('merchantModal').classList.add('hidden');
}
</script>
@endsection
