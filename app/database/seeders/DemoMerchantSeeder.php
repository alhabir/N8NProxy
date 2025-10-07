<?php

namespace Database\Seeders;

use App\Models\Merchant;
use App\Models\MerchantToken;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoMerchantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'demo-merchant@example.com'],
            [
                'name' => 'Demo Merchant',
                'password' => Hash::make('password'),
            ]
        );

        // Create demo merchant
        $merchant = Merchant::updateOrCreate(
            ['salla_merchant_id' => '112233'],
            [
                'user_id' => $user->id,
                'store_name' => 'Demo Store for Testing',
                'email' => $user->email,
                'n8n_base_url' => 'http://localhost:5678',
                'n8n_webhook_path' => '/webhook/salla-proxy',
                'n8n_auth_type' => 'none',
                'is_active' => true,
                'is_approved' => true,
            ]
        );

        // Create demo token (valid for 30 days)
        MerchantToken::updateOrCreate(
            ['merchant_id' => $merchant->id],
            [
                'salla_merchant_id' => '112233',
                'access_token' => 'demo_access_token_' . bin2hex(random_bytes(16)),
                'refresh_token' => 'demo_refresh_token_' . bin2hex(random_bytes(16)),
                'access_token_expires_at' => now()->addDays(30),
            ]
        );

        $this->command->info('Demo merchant created:');
        $this->command->info('  Salla Merchant ID: 112233');
        $this->command->info('  Store Name: Demo Store for Testing');
        $this->command->info('  Token expires: ' . now()->addDays(30)->toDateTimeString());
    }
}
