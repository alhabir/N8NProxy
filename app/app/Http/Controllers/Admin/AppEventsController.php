<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class AppEventsController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'event_name' => $request->input('event_name'),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
        ];

        $query = AppEvent::query();

        if (! empty($filters['event_name'])) {
            $query->where('event_name', $filters['event_name']);
        }

        $fromDate = $this->parseDate($filters['from'], true);
        if ($fromDate) {
            $query->where(function ($builder) use ($fromDate) {
                $builder->where(function ($inner) use ($fromDate) {
                    $inner->whereNotNull('event_created_at')
                        ->where('event_created_at', '>=', $fromDate);
                })->orWhere(function ($inner) use ($fromDate) {
                    $inner->whereNull('event_created_at')
                        ->where('created_at', '>=', $fromDate);
                });
            });
        }

        $toDate = $this->parseDate($filters['to'], false);
        if ($toDate) {
            $query->where(function ($builder) use ($toDate) {
                $builder->where(function ($inner) use ($toDate) {
                    $inner->whereNotNull('event_created_at')
                        ->where('event_created_at', '<=', $toDate);
                })->orWhere(function ($inner) use ($toDate) {
                    $inner->whereNull('event_created_at')
                        ->where('created_at', '<=', $toDate);
                });
            });
        }

        $events = $query
            ->select(['id', 'event_name', 'salla_merchant_id', 'event_created_at', 'created_at'])
            ->orderByDesc('event_created_at')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $eventNames = AppEvent::query()
            ->select('event_name')
            ->distinct()
            ->orderBy('event_name')
            ->pluck('event_name');

        $stats = [
            'installs' => AppEvent::query()->where('event_name', 'app.installed')->count(),
            'uninstalls' => AppEvent::query()->where('event_name', 'app.uninstalled')->count(),
        ];

        return view('admin.app-events', [
            'stats' => $stats,
            'events' => $events,
            'eventNames' => $eventNames,
            'filters' => $filters,
        ]);
    }

    private function parseDate(?string $value, bool $isStartOfDay): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        try {
            $date = Carbon::parse($value);

            return $isStartOfDay ? $date->startOfDay() : $date->endOfDay();
        } catch (\Throwable $exception) {
            Log::debug('Invalid date filter provided for app events', [
                'value' => $value,
                'direction' => $isStartOfDay ? 'from' : 'to',
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}
