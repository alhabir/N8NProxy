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
                TextEntry::make('salla_event'),
                TextEntry::make('salla_event_id')->copyable(),
                TextEntry::make('salla_merchant_id'),
                TextEntry::make('status'),
                TextEntry::make('attempts'),
                TextEntry::make('last_error')->columnSpanFull(),
                TextEntry::make('headers')->json()->columnSpanFull(),
                TextEntry::make('payload')->json()->columnSpanFull(),
                TextEntry::make('created_at')->dateTime(),
                TextEntry::make('updated_at')->dateTime(),
            ]);
    }
}


