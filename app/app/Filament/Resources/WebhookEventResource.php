<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WebhookEventResource\Pages;
use App\Models\Merchant;
use App\Models\WebhookEvent;
use App\Services\Salla\WebhookForwarder;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class WebhookEventResource extends Resource
{
    protected static ?string $model = WebhookEvent::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-up';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('status')->badge()->sortable(),
                Tables\Columns\TextColumn::make('salla_event')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('salla_merchant_id')->label('Merchant')->sortable(),
                Tables\Columns\TextColumn::make('attempts')->sortable(),
                Tables\Columns\TextColumn::make('last_error')->limit(50),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'stored' => 'Stored',
                    'sent' => 'Sent',
                    'failed' => 'Failed',
                    'skipped' => 'Skipped',
                ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('retryNow')
                    ->label('Retry Now')
                    ->visible(fn(WebhookEvent $record) => in_array($record->status, ['failed','stored']))
                    ->action(function (WebhookEvent $record) {
                        $merchant = Merchant::where('salla_merchant_id', $record->salla_merchant_id)->first();
                        if (!$merchant) {
                            return;
                        }
                        $result = app(WebhookForwarder::class)->forward($record, $merchant);

                        $record->forceFill([
                            'attempts' => ($record->attempts ?? 0) + ($result['attempts'] ?? 1),
                            'last_error' => $result['ok'] ? null : ($result['error'] ?? null),
                            'status' => $result['ok'] ? 'sent' : 'failed',
                            'response_status' => $result['code'],
                            'response_body_excerpt' => $result['body'],
                            'forwarded_at' => now(),
                        ])->save();
                    })
                    ->color('warning')
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWebhookEvents::route('/'),
            'view' => Pages\ViewWebhookEvent::route('/{record}'),
        ];
    }
}


