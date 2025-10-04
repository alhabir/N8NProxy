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
                Forms\Components\TextInput::make('n8n_path')->default('/webhook/salla'),
                Forms\Components\Select::make('n8n_auth_type')
                    ->options([
                        'none' => 'None',
                        'bearer' => 'Bearer',
                        'basic' => 'Basic',
                    ])->default('none'),
                Forms\Components\TextInput::make('n8n_bearer_token')->password()->revealable()->visible(fn($get) => $get('n8n_auth_type') === 'bearer'),
                Forms\Components\TextInput::make('n8n_basic_user')->visible(fn($get) => $get('n8n_auth_type') === 'basic'),
                Forms\Components\TextInput::make('n8n_basic_pass')->password()->revealable()->visible(fn($get) => $get('n8n_auth_type') === 'basic'),
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
                        $url = rtrim($record->n8n_base_url, '/').'/'.ltrim($record->n8n_path ?? '/webhook/salla', '/');
                        $headers = [
                            'Content-Type' => 'application/json',
                            'X-Forwarded-By' => 'n8n-ai-salla-proxy',
                        ];
                        if ($record->n8n_auth_type === 'bearer' && $record->n8n_bearer_token) {
                            $headers['Authorization'] = 'Bearer '.$record->n8n_bearer_token;
                        } elseif ($record->n8n_auth_type === 'basic' && $record->n8n_basic_user !== null) {
                            $headers['Authorization'] = 'Basic '.base64_encode($record->n8n_basic_user.':'.$record->n8n_basic_pass);
                        }
                        $resp = Http::withHeaders($headers)->timeout(6)->asJson()->post($url, [
                            'type' => 'n8n-ai.ping',
                            'time' => now()->toIso8601String(),
                        ]);
                        if ($resp->successful()) {
                            $record->update(['last_ping_ok_at' => now()]);
                        }
                    })
                    ->color('success'),
            ]);
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


