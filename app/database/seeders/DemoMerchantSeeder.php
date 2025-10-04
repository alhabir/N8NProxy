<?php

namespace Database\Seeders;

use App\Models\Merchant;
use App\Models\MerchantToken;
use Illuminate\Database\Seeder;

class DemoMerchantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $merchant = Merchant::create([
            'salla_merchant_id' => '112233',
            'store_name' => 'Demo Store',
            'is_active' => true,
            'n8n_base_url' => 'http://localhost:5678',
            'n8n_path' => '/webhook/salla',
            'n8n_auth_type' => 'none',
        ]);

        MerchantToken::create([
            'merchant_id' => $merchant->id,
            'salla_merchant_id' => '112233',
            'access_token' => 'demo_access_token_' . bin2hex(random_bytes(16)),
            'refresh_token' => 'demo_refresh_token_' . bin2hex(random_bytes(16)),
            'access_token_expires_at' => now()->addDays(7),
        ]);

        $this->command->info('Demo merchant created:');
        $this->command->info('  Merchant ID: ' . $merchant->id);
        $this->command->info('  Salla Merchant ID: 112233');
        $this->command->info('  Store Name: Demo Store');
    }
}
