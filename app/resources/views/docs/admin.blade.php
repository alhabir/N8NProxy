@extends('layouts.app')

@section('title', 'Admin Documentation')

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <h1 class="text-3xl font-bold mb-8">Admin Documentation</h1>
                
                <div class="prose max-w-none">
                    <h2>System Overview</h2>
                    <p>The N8NProxy system acts as a bridge between Salla stores and n8n instances, providing both webhook forwarding and API actions.</p>
                    
                    <h3>Components</h3>
                    <ul>
                        <li><strong>Webhook Proxy:</strong> Receives Salla webhooks, validates signatures, and forwards to merchant n8n instances</li>
                        <li><strong>Actions API:</strong> Allows n8n to call Salla Admin API on behalf of merchants</li>
                        <li><strong>OAuth Management:</strong> Handles token storage and refresh for API access</li>
                        <li><strong>Admin Panel:</strong> Manage merchants, monitor activity, configure settings</li>
                    </ul>
                    
                    <h2>Salla Console Configuration</h2>
                    <p>Configure your Salla app with these exact settings:</p>
                    
                    <h3>Webhook Settings</h3>
                    <ul>
                        <li><strong>Webhook URL:</strong> <code>{{ rtrim(config('panels.admin_url'), '/') }}/webhooks/salla</code></li>
                        <li><strong>Security Strategy:</strong> Signature</li>
                        <li><strong>Webhook Secret:</strong> <code>519dd95fbd631b78020de2e36ae116c3</code></li>
                    </ul>
                    
                    <h3>App Events</h3>
                    <ul>
                        <li><strong>App Authorized Event:</strong> <code>{{ rtrim(config('panels.admin_url'), '/') }}/app-events/authorized</code></li>
                        <li><strong>Purpose:</strong> Captures OAuth tokens for API access</li>
                    </ul>
                    
                    <h3>Scopes Required</h3>
                    <ul>
                        <li>Orders: Read/Write</li>
                        <li>Products: Read/Write</li>
                        <li>Customers: Read/Write</li>
                        <li>Marketing: Read/Write</li>
                        <li>Categories: Read/Write</li>
                        <li>Exports: Read/Write</li>
                        <li>Webhooks: Read/Write</li>
                    </ul>
                    
                    <h2>Merchant Management</h2>
                    <h3>Approval Process</h3>
                    <ol>
                        <li>Merchants register and install the app</li>
                        <li>App authorization event is received</li>
                        <li>Admin reviews merchant in admin panel</li>
                        <li>Admin approves merchant account</li>
                        <li>Merchant can configure n8n settings</li>
                    </ol>
                    
                    <h3>Monitoring Merchants</h3>
                    <ul>
                        <li>View merchant status and configuration</li>
                        <li>Send test webhooks to verify setup</li>
                        <li>Monitor webhook delivery success rates</li>
                        <li>Review API action usage</li>
                    </ul>
                    
                    <h2>System Monitoring</h2>
                    <h3>Webhook Monitoring</h3>
                    <ul>
                        <li>Track webhook delivery status</li>
                        <li>Monitor retry attempts</li>
                        <li>Identify failed deliveries</li>
                        <li>View webhook payloads for debugging</li>
                    </ul>
                    
                    <h3>API Actions Monitoring</h3>
                    <ul>
                        <li>Audit all API calls made by n8n</li>
                        <li>Track success/failure rates</li>
                        <li>Monitor token usage and refresh</li>
                        <li>Identify rate limiting issues</li>
                    </ul>
                    
                    <h2>Configuration</h2>
                    <h3>Environment Variables</h3>
                    <ul>
                        <li><code>SALLA_CLIENT_ID:</code> Your Salla app client ID</li>
                        <li><code>SALLA_CLIENT_SECRET:</code> Your Salla app client secret</li>
                        <li><code>SALLA_WEBHOOK_SECRET:</code> Webhook signature verification key</li>
                        <li><code>ACTIONS_API_BEARER:</code> Bearer token for API authentication</li>
                    </ul>
                    
                    <h3>App Settings</h3>
                    <p>Configure non-sensitive settings through the admin panel:</p>
                    <ul>
                        <li>Forward timeout settings</li>
                        <li>Retry configuration</li>
                        <li>Rate limiting settings</li>
                        <li>Notification preferences</li>
                    </ul>
                    
                    <h2>Security</h2>
                    <h3>Webhook Security</h3>
                    <ul>
                        <li>All webhooks are signature verified</li>
                        <li>Invalid signatures are rejected</li>
                        <li>Webhook payloads are logged for audit</li>
                    </ul>
                    
                    <h3>API Security</h3>
                    <ul>
                        <li>Actions API requires bearer token authentication</li>
                        <li>OAuth tokens are encrypted in database</li>
                        <li>All API calls are audited</li>
                        <li>Rate limiting prevents abuse</li>
                    </ul>
                    
                    <h2>Troubleshooting</h2>
                    <h3>Common Issues</h3>
                    <ul>
                        <li><strong>Webhook failures:</strong> Check merchant n8n URL and authentication</li>
                        <li><strong>API failures:</strong> Verify OAuth tokens and permissions</li>
                        <li><strong>Token expiry:</strong> System automatically refreshes tokens</li>
                        <li><strong>Rate limiting:</strong> Monitor API usage and implement backoff</li>
                    </ul>
                    
                    <h3>Logs and Debugging</h3>
                    <ul>
                        <li>Check Laravel logs: <code>storage/logs/laravel.log</code></li>
                        <li>Review webhook delivery attempts</li>
                        <li>Monitor API response codes</li>
                        <li>Use admin panel to send test webhooks</li>
                    </ul>
                    
                    <h2>Maintenance</h2>
                    <h3>Regular Tasks</h3>
                    <ul>
                        <li>Monitor system health dashboard</li>
                        <li>Review failed webhook deliveries</li>
                        <li>Check OAuth token status</li>
                        <li>Update app settings as needed</li>
                    </ul>
                    
                    <h3>Database Maintenance</h3>
                    <ul>
                        <li>Archive old webhook events</li>
                        <li>Clean up expired tokens</li>
                        <li>Optimize audit log storage</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
