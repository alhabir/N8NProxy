@extends('layouts.merchant')

@section('title', 'n8n Settings')

@section('content')
<div class="py-12">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <h2 class="text-2xl font-bold mb-6">n8n Configuration</h2>
                
                @php
                    $bearerToken = old('n8n_bearer_token');
                    if ($bearerToken === null && $merchant->n8n_auth_type === 'bearer') {
                        $bearerToken = $merchant->n8n_auth_token;
                    }

                    $basicUser = old('n8n_basic_user');
                    $basicPass = old('n8n_basic_pass');
                    if ($basicUser === null && $basicPass === null && $merchant->n8n_auth_type === 'basic' && $merchant->n8n_auth_token) {
                        $decoded = json_decode($merchant->n8n_auth_token, true) ?? [];
                        $basicUser = $decoded['username'] ?? '';
                        $basicPass = $decoded['password'] ?? '';
                    }
                @endphp

                @if(!$allowTestMode)
                    <div class="mb-6 rounded-md border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
                        Test mode is currently disabled by the administrator. You can still save your configuration and request a test from the admin panel when enabled.
                    </div>
                @endif

                <form method="POST" action="{{ route('settings.n8n') }}">
                    @csrf
                    
                    <!-- n8n Base URL -->
                    <div class="mb-6">
                        <label for="n8n_base_url" class="block text-sm font-medium text-gray-700 mb-2">
                            n8n Base URL *
                        </label>
                        <input type="url" 
                               id="n8n_base_url" 
                               name="n8n_base_url" 
                               value="{{ old('n8n_base_url', $merchant->n8n_base_url) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                               placeholder="https://your-n8n-instance.com"
                               required>
                        @error('n8n_base_url')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-sm text-gray-500">The base URL of your n8n instance</p>
                    </div>

                    <!-- n8n Path -->
                    <div class="mb-6">
                        <label for="n8n_webhook_path" class="block text-sm font-medium text-gray-700 mb-2">
                            Webhook Path
                        </label>
                        <input type="text" 
                               id="n8n_webhook_path" 
                               name="n8n_webhook_path" 
                               value="{{ old('n8n_webhook_path', $merchant->n8n_webhook_path) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                               placeholder="/webhook/salla">
                        @error('n8n_webhook_path')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-sm text-gray-500">The path where webhooks will be sent (default: /webhook/salla)</p>
                    </div>

                    <!-- Authentication Type -->
                    <div class="mb-6">
                        <label for="n8n_auth_type" class="block text-sm font-medium text-gray-700 mb-2">
                            Authentication Type
                        </label>
                        <select id="n8n_auth_type" 
                                name="n8n_auth_type" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                onchange="toggleAuthFields()">
                            <option value="none" {{ old('n8n_auth_type', $merchant->n8n_auth_type) === 'none' ? 'selected' : '' }}>None</option>
                            <option value="bearer" {{ old('n8n_auth_type', $merchant->n8n_auth_type) === 'bearer' ? 'selected' : '' }}>Bearer Token</option>
                            <option value="basic" {{ old('n8n_auth_type', $merchant->n8n_auth_type) === 'basic' ? 'selected' : '' }}>Basic Auth</option>
                        </select>
                        @error('n8n_auth_type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Bearer Token -->
                    <div id="bearer-fields" class="mb-6" style="display: {{ old('n8n_auth_type', $merchant->n8n_auth_type) === 'bearer' ? 'block' : 'none' }}">
                        <label for="n8n_bearer_token" class="block text-sm font-medium text-gray-700 mb-2">
                            Bearer Token
                        </label>
                        <input type="password" 
                               id="n8n_bearer_token" 
                               name="n8n_bearer_token" 
                               value="{{ $bearerToken ?? '' }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                               placeholder="Your bearer token">
                        @error('n8n_bearer_token')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-sm text-gray-500">The bearer token for authentication</p>
                    </div>

                    <!-- Basic Auth -->
                    <div id="basic-fields" class="mb-6" style="display: {{ old('n8n_auth_type', $merchant->n8n_auth_type) === 'basic' ? 'block' : 'none' }}">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="n8n_basic_user" class="block text-sm font-medium text-gray-700 mb-2">
                                    Username
                                </label>
                                <input type="text" 
                                       id="n8n_basic_user" 
                                       name="n8n_basic_user" 
                                       value="{{ $basicUser ?? '' }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                       placeholder="Username">
                                @error('n8n_basic_user')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="n8n_basic_pass" class="block text-sm font-medium text-gray-700 mb-2">
                                    Password
                                </label>
                                <input type="password" 
                                       id="n8n_basic_pass" 
                                       name="n8n_basic_pass" 
                                       value="{{ $basicPass ?? '' }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                       placeholder="Password">
                                @error('n8n_basic_pass')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <p class="mt-1 text-sm text-gray-500">Basic authentication credentials</p>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex items-center justify-end space-x-4">
                        <a href="{{ route('dashboard') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Cancel
                        </a>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleAuthFields() {
    const authType = document.getElementById('n8n_auth_type').value;
    const bearerFields = document.getElementById('bearer-fields');
    const basicFields = document.getElementById('basic-fields');
    
    bearerFields.style.display = authType === 'bearer' ? 'block' : 'none';
    basicFields.style.display = authType === 'basic' ? 'block' : 'none';
}
</script>
@endsection
