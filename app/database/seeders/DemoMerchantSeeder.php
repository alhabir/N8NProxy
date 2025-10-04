<?php

namespace Database\Seeders;

use App\Models\Merchant;
use App\Models\MerchantToken;
use Illuminate\Database\Seeder;

class DemoMerchantSeeder extends Seeder
{
    public function run(): void
    {
        $sallaId = '112233';
        $merchant = Merchant::firstOrCreate([
            'salla_merchant_id' => $sallaId,
        ], [
            'store_name' => 'Demo Store',
            'n8n_base_url' => 'http://127.0.0.1:5678',
            'n8n_path' => '/webhook/salla',
            'is_active' => true,
        ]);

        MerchantToken::updateOrCreate([
            'merchant_id' => $merchant->id,
        ], [
            'salla_merchant_id' => $sallaId,
            'access_token' => 'demo_access',
            'refresh_token' => 'demo_refresh',
            'access_token_expires_at' => now()->addHour(),
        ]);
    }
}
