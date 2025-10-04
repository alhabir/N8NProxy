<?php

namespace Database\Seeders;

use App\Models\Merchant;
use App\Models\MerchantToken;
use Illuminate\Database\Seeder;

class DemoMerchantSeeder extends Seeder
{
    /**
     * Seed demo merchants with tokens for testing
     */
    public function run(): void
    {
        // Create demo merchant with valid token
        $merchant1 = Merchant::create([
            'salla_merchant_id' => 'demo_merchant_123',
            'store_name' => 'Demo Store 123',
            'n8n_base_url' => 'https://demo-n8n.example.com',
            'n8n_path' => '/webhook/salla',
            'n8n_auth_type' => 'bearer',
            'n8n_bearer_token' => 'demo_n8n_token_123',
            'is_active' => true,
            'last_ping_ok_at' => now(),
        ]);

        MerchantToken::create([
            'merchant_id' => $merchant1->id,
            'salla_merchant_id' => $merchant1->salla_merchant_id,
            'access_token' => 'demo_access_token_123',
            'refresh_token' => 'demo_refresh_token_123',
            'access_token_expires_at' => now()->addHours(2),
        ]);

        // Create another demo merchant with expiring token
        $merchant2 = Merchant::create([
            'salla_merchant_id' => 'demo_merchant_456',
            'store_name' => 'Demo Store 456',
            'n8n_base_url' => 'https://demo-n8n-2.example.com',
            'n8n_path' => '/webhook/salla',
            'n8n_auth_type' => 'none',
            'is_active' => true,
        ]);

        MerchantToken::create([
            'merchant_id' => $merchant2->id,
            'salla_merchant_id' => $merchant2->salla_merchant_id,
            'access_token' => 'demo_access_token_456',
            'refresh_token' => 'demo_refresh_token_456',
            'access_token_expires_at' => now()->addMinutes(30), // Expiring soon
        ]);

        $this->command->info('Demo merchants created:');
        $this->command->info('- demo_merchant_123 (token valid for 2 hours)');
        $this->command->info('- demo_merchant_456 (token expiring in 30 minutes)');
    }
}