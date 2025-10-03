<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WebhookEventResource\Pages;
use App\Models\WebhookEvent;
use App\Services\Forwarder;
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
                Tables\Columns\TextColumn::make('forward_status')->badge()->sortable(),
                Tables\Columns\TextColumn::make('event')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('merchant.salla_merchant_id')->label('Merchant')->sortable(),
                Tables\Columns\TextColumn::make('received_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('forward_attempts')->sortable(),
                Tables\Columns\TextColumn::make('forwarded_response_code'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('forward_status')->options([
                    'pending' => 'Pending',
                    'sent' => 'Sent',
                    'failed' => 'Failed',
                    'skipped' => 'Skipped',
                ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('retryNow')
                    ->label('Retry Now')
                    ->visible(fn(WebhookEvent $record) => in_array($record->forward_status, ['failed','pending']))
                    ->action(function (WebhookEvent $record) {
                        $merchant = $record->merchant;
                        if (!$merchant) {
                            return;
                        }
                        $result = app(Forwarder::class)->forward($record, $merchant);
                        $record->update([
                            'forward_attempts' => $record->forward_attempts + ($result['attempts'] ?? 1),
                            'forwarded_response_code' => $result['code'] ?? null,
                            'forwarded_response_body' => $result['body'] ?? null,
                            'last_forward_error' => $result['error'] ?? null,
                            'forwarded_at' => $result['ok'] ? now() : null,
                            'forward_status' => $result['ok'] ? 'sent' : 'failed',
                        ]);
                    })
                    ->color('warning')
            ])
            ->defaultSort('received_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWebhookEvents::route('/'),
            'view' => Pages\ViewWebhookEvent::route('/{record}'),
        ];
    }
}


