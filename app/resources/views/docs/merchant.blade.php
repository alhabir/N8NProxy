@extends('layouts.app')

@section('title', 'Merchant Documentation')

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <h1 class="text-3xl font-bold mb-8">Merchant Setup Guide</h1>
                
                <div class="prose max-w-none">
                    <h2>Getting Started</h2>
                    <p>This guide will help you connect your Salla store with n8n using our proxy service.</p>
                    
                    <h3>Step 1: Install the n8n ai App</h3>
                    <ol>
                        <li>Go to your Salla store admin panel</li>
                        <li>Navigate to Apps → App Store</li>
                        <li>Search for "n8n ai"</li>
                        <li>Click "Install" and authorize the app</li>
                    </ol>
                    
                    <h3>Step 2: Configure n8n URL</h3>
                    <ol>
                        <li>Log into your merchant dashboard at <a href="{{ config('panels.merchant_url') }}" class="text-indigo-600">{{ config('panels.merchant_url') }}</a></li>
                        <li>Go to Settings → n8n Configuration</li>
                        <li>Enter your n8n instance URL (e.g., https://your-n8n.com)</li>
                        <li>Set the webhook path (default: /webhook/salla)</li>
                        <li>Configure authentication if needed</li>
                    </ol>
                    
                    <h3>Step 3: Test the Connection</h3>
                    <ol>
                        <li>In your merchant dashboard (`{{ config('panels.merchant_url') }}`), click "Send Test Webhook"</li>
                        <li>Check your n8n instance for the test webhook</li>
                        <li>Verify the webhook was received successfully</li>
                    </ol>
                    
                    <h2>Supported Events</h2>
                    <p>Our proxy supports all major Salla events:</p>
                    <ul>
                        <li><strong>Orders:</strong> created, updated, cancelled, deleted, refunded</li>
                        <li><strong>Customers:</strong> created, updated, login, OTP requests</li>
                        <li><strong>Products:</strong> created, updated, deleted, availability changes</li>
                        <li><strong>Categories:</strong> created, updated</li>
                        <li><strong>Brands:</strong> created, updated, deleted</li>
                        <li><strong>Marketing:</strong> abandoned carts, coupon applications</li>
                        <li><strong>Reviews:</strong> added</li>
                        <li><strong>And many more...</strong></li>
                    </ul>
                    
                    <h2>n8n Workflow Setup</h2>
                    <p>To receive webhooks in n8n:</p>
                    <ol>
                        <li>Create a new workflow in n8n</li>
                        <li>Add a "Webhook" node</li>
                        <li>Set the HTTP Method to POST</li>
                        <li>Copy the webhook URL from your merchant dashboard</li>
                        <li>Configure your workflow logic</li>
                        <li>Test the webhook using the test button</li>
                    </ol>
                    
                    <h2>Webhook Headers</h2>
                    <p>Our proxy adds these headers to help you identify events:</p>
                    <ul>
                        <li><code>X-Forwarded-By:</code> n8n-ai-salla-proxy</li>
                        <li><code>X-Salla-Event:</code> The event type (e.g., order.created)</li>
                        <li><code>X-Salla-Event-Id:</code> Unique event identifier</li>
                        <li><code>X-Salla-Merchant-Id:</code> Your store ID</li>
                        <li><code>X-Event-Checksum:</code> SHA256 hash for verification</li>
                    </ul>
                    
                    <h2>Troubleshooting</h2>
                    <h3>Webhook Not Received</h3>
                    <ul>
                        <li>Check your n8n URL is correct</li>
                        <li>Verify your n8n instance is accessible</li>
                        <li>Check authentication settings</li>
                        <li>Review webhook logs in your dashboard</li>
                    </ul>
                    
                    <h3>Authentication Issues</h3>
                    <ul>
                        <li>For Bearer token: Ensure token is valid and not expired</li>
                        <li>For Basic auth: Verify username and password</li>
                        <li>Test authentication with a simple HTTP request</li>
                    </ul>
                    
                    <h2>Support</h2>
                    <p>If you need help:</p>
                    <ul>
                        <li>Check the webhook logs in your dashboard</li>
                        <li>Review the actions audit for API calls</li>
                        <li>Actions API base URL: <code>{{ rtrim(config('panels.admin_url'), '/') }}/api</code> (requires bearer token)</li>
                        <li>Contact support with your merchant ID</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
