<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MerchantResource\Pages;
use App\Models\Merchant;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Support\Facades\Http;

class MerchantResource extends Resource
{
    protected static ?string $model = Merchant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('salla_merchant_id')->required()->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('store_name'),
                Forms\Components\TextInput::make('n8n_base_url')->required(),
                Forms\Components\TextInput::make('n8n_webhook_path')->default('/webhook/salla'),
                Forms\Components\Select::make('n8n_auth_type')
                    ->options([
                        'none' => 'None',
                        'bearer' => 'Bearer',
                        'basic' => 'Basic',
                    ])->default('none'),
                Forms\Components\Textarea::make('n8n_auth_token')
                    ->label('Auth token / credentials')
                    ->rows(3)
                    ->visible(fn(callable $get) => $get('n8n_auth_type') !== 'none')
                    ->helperText('Bearer: paste the access token. Basic: provide a JSON object such as {"username":"foo","password":"bar"}.')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_active')->default(true),
                Forms\Components\DateTimePicker::make('last_ping_ok_at')->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('salla_merchant_id')->sortable()->searchable(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('n8n_base_url')->wrap(),
                Tables\Columns\TextColumn::make('last_ping_ok_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->since(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('pingN8n')
                    ->label('Ping n8n')
                    ->action(function (Merchant $record) {
                        $url = self::buildTargetUrl($record);

                        if (!$url) {
                            return;
                        }

                        $headers = [
                            'Content-Type' => 'application/json',
                            'X-Forwarded-By' => 'n8n-ai-salla-proxy',
                        ];

                        if ($record->n8n_auth_type === 'bearer' && $record->n8n_auth_token) {
                            $headers['Authorization'] = 'Bearer '.$record->n8n_auth_token;
                        } elseif ($record->n8n_auth_type === 'basic' && $record->n8n_auth_token) {
                            $credentials = self::decodeBasicCredentials($record->n8n_auth_token);
                            if ($credentials) {
                                $headers['Authorization'] = 'Basic '.base64_encode($credentials['username'].':'.$credentials['password']);
                            }
                        }

                        $response = Http::withHeaders($headers)->timeout(6)->asJson()->post($url, [
                            'type' => 'n8n-ai.ping',
                            'time' => now()->toIso8601String(),
                        ]);

                        if ($response->successful()) {
                            $record->update(['last_ping_ok_at' => now()]);
                        }
                    })
                    ->color('success'),
            ]);
    }

    protected static function buildTargetUrl(Merchant $merchant): ?string
    {
        if (!$merchant->n8n_base_url) {
            return null;
        }

        $base = rtrim($merchant->n8n_base_url, '/');
        $path = $merchant->n8n_webhook_path ?: '/webhook/salla';

        return $base.'/'.ltrim($path, '/');
    }

    protected static function decodeBasicCredentials(?string $token): ?array
    {
        if (!$token) {
            return null;
        }

        $decoded = json_decode($token, true);

        if (is_array($decoded) && isset($decoded['username'], $decoded['password'])) {
            return [
                'username' => (string) $decoded['username'],
                'password' => (string) $decoded['password'],
            ];
        }

        if (str_contains($token, ':')) {
            [$username, $password] = explode(':', $token, 2);
            if ($username !== '' && $password !== '') {
                return [
                    'username' => $username,
                    'password' => $password,
                ];
            }
        }

        return null;
    }

    public static function normalizeFormData(array $data): array
    {
        if (isset($data['n8n_base_url'])) {
            $data['n8n_base_url'] = rtrim($data['n8n_base_url'], '/');
        }

        if (!empty($data['n8n_webhook_path'])) {
            $data['n8n_webhook_path'] = '/'.ltrim($data['n8n_webhook_path'], '/');
        }

        $authType = $data['n8n_auth_type'] ?? 'none';

        if ($authType === 'none') {
            $data['n8n_auth_token'] = null;
        } elseif ($authType === 'bearer') {
            $data['n8n_auth_token'] = isset($data['n8n_auth_token']) ? trim($data['n8n_auth_token']) : null;
        } elseif (isset($data['n8n_auth_token'])) {
            $credentials = self::decodeBasicCredentials($data['n8n_auth_token']);
            $data['n8n_auth_token'] = $credentials ? json_encode($credentials) : null;
        }

        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMerchants::route('/'),
            'create' => Pages\CreateMerchant::route('/create'),
            'edit' => Pages\EditMerchant::route('/{record}/edit'),
        ];
    }
}


