<?php

namespace App\Filament\Resources\WebhookEventResource\Pages;

use App\Filament\Resources\WebhookEventResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;

class ViewWebhookEvent extends ViewRecord
{
    protected static string $resource = WebhookEventResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                TextEntry::make('event'),
                TextEntry::make('salla_event_id')->copyable(),
                TextEntry::make('merchant.salla_merchant_id'),
                TextEntry::make('received_at')->dateTime(),
                TextEntry::make('forward_status'),
                TextEntry::make('forward_attempts'),
                TextEntry::make('forwarded_response_code'),
                TextEntry::make('last_forward_error')->columnSpanFull(),
                TextEntry::make('headers')->json()->columnSpanFull(),
                TextEntry::make('payload')->json()->columnSpanFull(),
                TextEntry::make('forwarded_response_body')->json()->columnSpanFull(),
            ]);
    }
}


