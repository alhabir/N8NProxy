<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Services\Salla\OAuthTokenStore;
use Illuminate\Http\Request;

class SallaAppEventsController extends Controller
{
    public function __construct(private OAuthTokenStore $store) {}

    public function authorized(Request $r)
    {
        $payload = $r->json()->all();
        $sallaMerchantId = (string) data_get($payload, 'data.store.id');
        $access = (string) data_get($payload, 'data.tokens.access_token');
        $refresh = (string) data_get($payload, 'data.tokens.refresh_token');
        $expires = now()->addSeconds((int) data_get($payload, 'data.tokens.expires_in', 3600));

        $merchant = Merchant::firstOrCreate(
            ['salla_merchant_id' => $sallaMerchantId],
            ['n8n_base_url' => '', 'is_active' => true]
        );

        $this->store->put($sallaMerchantId, $access, $refresh, $expires);

        return response()->json(['ok' => true]);
    }

    public function installed(Request $r)
    {
        return response()->json(['ok' => true]);
    }
}
