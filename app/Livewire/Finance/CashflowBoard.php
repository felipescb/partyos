<?php

namespace App\Livewire\Finance;

use App\Domain\Finance\CashflowMovements;
use App\Livewire\Concerns\InteractsWithEvent;
use App\Models\Event;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app.dashboard')]
class CashflowBoard extends Component
{
    use InteractsWithEvent;

    public string $window = 'all';

    public string $map = 'time';

    public string $grain = 'week';

    public function mount(Event $event): void
    {
        $this->mountEvent($event, 'viewFinance');
    }

    public function render(): View
    {
        $movements = $this->movements();
        $visible = $this->filter($movements, $this->window);

        return view('livewire.finance.cashflow', [
            'summaries' => [
                'today' => $this->tally($this->filter($movements, 'today')),
                '7' => $this->tally($this->filter($movements, '7')),
                '30' => $this->tally($this->filter($movements, '30')),
                'all' => $this->tally($this->filter($movements, 'all')),
                'nodate' => $this->tally($this->filter($movements, 'nodate')),
            ],
            'columns' => $this->columns($visible),
            'series' => $this->chartSeries($visible),
            'map' => $this->map,
            'grain' => $this->grain,
        ])->title('Fluxo de caixa · '.$this->event->name);
    }

    /**
     * @param  Collection<int, array{date: ?string, label: string, amount: int, state: string, detail: string, tone: string, category: ?string}>  $rows
     * @return array{amount: int, count: int}
     */
    private function tally(Collection $rows): array
    {
        return [
            'amount' => (int) $rows->sum('amount'),
            'count' => $rows->count(),
        ];
    }

    /**
     * @return Collection<int, array{date: ?string, label: string, amount: int, state: string, detail: string, tone: string, category: ?string}>
     */
    private function movements(): Collection
    {
        return app(CashflowMovements::class)->rows($this->event);
    }

    /**
     * @param  Collection<int, array{date: ?string, label: string, amount: int, state: string, detail: string, tone: string, category: ?string}>  $rows
     * @return Collection<int, array{date: ?string, label: string, amount: int, state: string, detail: string, tone: string, category: ?string}>
     */
    private function filter(Collection $rows, string $window): Collection
    {
        if ($window === 'nodate') {
            return $rows->filter(fn (array $row): bool => $row['date'] === null)->values();
        }

        $start = now()->startOfDay();
        $end = match ($window) {
            'today' => $start->copy(),
            '7' => $start->copy()->addDays(7),
            '30' => $start->copy()->addDays(30),
            default => null,
        };

        return $rows
            ->filter(function (array $row) use ($window, $start, $end): bool {
                if ($row['date'] === null) {
                    return $window === 'all';
                }

                if ($end === null) {
                    return true;
                }

                return Carbon::parse($row['date'])->startOfDay()->betweenIncluded($start, $end);
            })
            ->sortBy(fn (array $row): string => $row['date'] ?? '9999-99-99')
            ->values();
    }

    /**
     * @param  Collection<int, array{date: ?string, label: string, amount: int, state: string, detail: string, tone: string, category: ?string}>  $rows
     * @return list<array{date: ?string, label: string, weekday: string, total: int, isToday: bool, isEvent: bool, isPast: bool, isUndated: bool, events: list<array{date: ?string, label: string, amount: int, state: string, detail: string, tone: string, category: ?string}>}>
     */
    private function columns(Collection $rows): array
    {
        if ($this->map === 'category') {
            return $this->categoryColumns($rows);
        }

        if (! in_array($this->map, ['time', 'category'], true)) {
            $this->map = 'time';
        }

        if ($this->grain === 'day') {
            return $this->dayColumns($rows);
        }

        return $this->weekColumns($rows);
    }

    /**
     * @param  Collection<int, array{date: ?string, label: string, amount: int, state: string, detail: string, tone: string, category: ?string, categorySlug: ?string}>  $rows
     * @return list<array{date: ?string, label: string, weekday: string, total: int, isToday: bool, isEvent: bool, isPast: bool, isUndated: bool, slug: ?string, events: list<array{date: ?string, label: string, amount: int, state: string, detail: string, tone: string, category: ?string, categorySlug: ?string}>}>
     */
    private function categoryColumns(Collection $rows): array
    {
        $columns = [];

        $rows
            ->groupBy(fn (array $row): string => $row['categorySlug'] ?? 'none')
            ->sortByDesc(fn (Collection $events): int => (int) $events->sum('amount'))
            ->each(function (Collection $events, string $slug) use (&$columns): void {
                $sorted = $events
                    ->sortBy(fn (array $row): string => $row['date'] ?? '9999-99-99')
                    ->values();
                $count = $sorted->count();

                $columns[] = $this->column(
                    null,
                    $sorted,
                    $sorted->first()['category'] ?? 'Sem categoria',
                    $slug,
                    $count === 1 ? '1 saída' : "{$count} saídas",
                );
            });

        return $columns;
    }

    /**
     * @param  Collection<int, array{date: ?string, label: string, amount: int, state: string, detail: string, tone: string, category: ?string, categorySlug: ?string}>  $rows
     * @return list<array{date: ?string, label: string, weekday: string, total: int, isToday: bool, isEvent: bool, isPast: bool, isUndated: bool, slug: ?string, events: list<array{date: ?string, label: string, amount: int, state: string, detail: string, tone: string, category: ?string, categorySlug: ?string}>}>
     */
    private function weekColumns(Collection $rows): array
    {
        $undated = $rows->filter(fn (array $row): bool => $row['date'] === null)->values();
        $dated = $rows->filter(fn (array $row): bool => $row['date'] !== null);
        $columns = [];

        if ($undated->isNotEmpty()) {
            $columns[] = $this->column(null, $undated->sortByDesc('amount')->values(), 'Sem data', null, 'Ainda sem vencimento');
        }

        if ($dated->isEmpty()) {
            return $columns;
        }

        $grouped = $dated->groupBy(fn (array $row): int => $this->weekOffset($row['date']));
        $min = (int) $grouped->keys()->min();
        $max = (int) $grouped->keys()->max();

        if ($this->window === 'all' && $this->event->starts_at) {
            $min = min($min, 0);
            $max = max($max, 0);
        }

        for ($offset = $min; $offset <= $max; $offset++) {
            $events = collect($grouped->get($offset, collect()))
                ->sortBy(fn (array $row): string => $row['date'] ?? '9999-99-99')
                ->values();

            $columns[] = $this->weekColumn($offset, $events);
        }

        return $columns;
    }

    private function anchorWeek(): CarbonInterface
    {
        return ($this->event->starts_at ?? now())->copy()->startOfDay()->startOfWeek(Carbon::MONDAY);
    }

    private function weekOffset(string $date): int
    {
        $week = Carbon::parse($date)->startOfDay()->startOfWeek(Carbon::MONDAY);
        $days = (int) $this->anchorWeek()->diffInDays($week, false);

        return intdiv($days, 7);
    }

    private function weekLabel(int $offset): string
    {
        if ($offset === 0) {
            return 'W0';
        }

        return $offset > 0 ? 'W+'.$offset : 'W'.$offset;
    }

    private function dayOffset(string $date): int
    {
        $anchor = ($this->event->starts_at ?? now())->copy()->startOfDay();

        return (int) $anchor->diffInDays(Carbon::parse($date)->startOfDay(), false);
    }

    private function dayLabel(int $offset): string
    {
        if ($offset === 0) {
            return 'D0';
        }

        return $offset > 0 ? 'D+'.$offset : 'D'.$offset;
    }

    /**
     * @param  Collection<int, array{date: ?string, label: string, amount: int, state: string, detail: string, tone: string, category: ?string, categorySlug: ?string}>  $rows
     * @return list<array{date: ?string, label: string, weekday: string, total: int, isToday: bool, isEvent: bool, isPast: bool, isUndated: bool, slug: ?string, events: list<array{date: ?string, label: string, amount: int, state: string, detail: string, tone: string, category: ?string, categorySlug: ?string}>}>
     */
    private function dayColumns(Collection $rows): array
    {
        $undated = $rows->filter(fn (array $row): bool => $row['date'] === null)->values();
        $dated = $rows->filter(fn (array $row): bool => $row['date'] !== null);
        $columns = [];

        if ($undated->isNotEmpty()) {
            $columns[] = $this->column(null, $undated->sortByDesc('amount')->values(), 'Sem data', null, 'Ainda sem vencimento');
        }

        if ($dated->isEmpty()) {
            return $columns;
        }

        $grouped = $dated->groupBy('date');
        $first = Carbon::parse($grouped->keys()->sort()->first())->startOfDay();
        $last = Carbon::parse($grouped->keys()->sort()->last())->startOfDay();

        foreach ([now()->startOfDay(), $this->event->starts_at?->copy()->startOfDay()] as $anchor) {
            if ($anchor && $anchor->betweenIncluded($first, $last)) {
                $key = $anchor->toDateString();
                $grouped->put($key, $grouped->get($key, collect()));
            }
        }

        if ($this->window === 'all' && $this->event->starts_at) {
            $key = $this->event->starts_at->copy()->startOfDay()->toDateString();
            $grouped->put($key, $grouped->get($key, collect()));
        }

        foreach ($grouped->keys()->sort()->values() as $date) {
            $events = collect($grouped->get($date, collect()))
                ->sortByDesc('amount')
                ->values();
            $day = Carbon::parse($date)->startOfDay();

            $columns[] = $this->column(
                $date,
                $events,
                null,
                null,
                $day->translatedFormat('l').' · '.$day->translatedFormat('d M'),
                $this->dayLabel($this->dayOffset($date)),
            );
        }

        return $columns;
    }

    /**
     * @param  Collection<int, array{date: ?string, label: string, amount: int, state: string, detail: string, tone: string, category: ?string, categorySlug: ?string}>  $rows
     * @return array{bars: list<array{x: float, y: float, width: float, height: float, paidY: float, paidHeight: float, label: string, value: string, isEvent: bool, isToday: bool}>, baseline: float, undated: int, hasPoints: bool}
     */
    private function chartSeries(Collection $rows): array
    {
        $undated = (int) $rows->filter(fn (array $row): bool => $row['date'] === null)->sum('amount');
        $empty = [
            'bars' => [],
            'baseline' => 74.0,
            'undated' => $undated,
            'hasPoints' => false,
        ];

        $dated = $rows->filter(fn (array $row): bool => $row['date'] !== null);

        if ($dated->isEmpty()) {
            return $empty;
        }

        $grouped = $dated->groupBy(fn (array $row): int => $this->weekOffset($row['date']));
        $min = (int) $grouped->keys()->min();
        $max = (int) $grouped->keys()->max();

        if ($this->window === 'all' && $this->event->starts_at) {
            $min = min($min, 0);
            $max = max($max, 0);
        }

        $weeks = [];

        for ($offset = $min; $offset <= $max; $offset++) {
            $events = collect($grouped->get($offset, collect()));
            $amount = (int) $events->sum('amount');
            $paid = (int) $events->where('tone', 'paid')->sum('amount');
            $start = $this->anchorWeek()->addWeeks($offset);
            $end = $start->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay();

            $weeks[] = [
                'label' => $this->weekLabel($offset),
                'amount' => $amount,
                'paid' => min($paid, $amount),
                'isEvent' => $offset === 0 && $this->event->starts_at !== null,
                'isToday' => now()->startOfDay()->betweenIncluded($start, $end),
            ];
        }

        $peak = max(1, (int) collect($weeks)->max('amount'));
        $count = count($weeks);
        $plotX = 10.0;
        $plotRight = 630.0;
        $baseline = 74.0;
        $plotH = 48.0;
        $slot = ($plotRight - $plotX) / max(1, $count);
        $barWidth = min(16.0, $slot * 0.68);
        $bars = [];

        foreach ($weeks as $index => $week) {
            $height = ($week['amount'] / $peak) * $plotH;
            $paidHeight = ($week['paid'] / $peak) * $plotH;
            $x = $plotX + ($index * $slot) + (($slot - $barWidth) / 2);

            $bars[] = [
                'x' => $x,
                'y' => $baseline - $height,
                'width' => $barWidth,
                'height' => $height,
                'paidY' => $baseline - $paidHeight,
                'paidHeight' => $paidHeight,
                'label' => $week['label'],
                'value' => $week['amount'] > 0 ? $this->axisMoney($week['amount']) : '',
                'isEvent' => $week['isEvent'],
                'isToday' => $week['isToday'],
            ];
        }

        return [
            'bars' => $bars,
            'baseline' => $baseline,
            'undated' => $undated,
            'hasPoints' => true,
        ];
    }

    private function axisMoney(int $cents): string
    {
        $reais = $cents / 100;

        if ($reais >= 1000) {
            $thousands = rtrim(rtrim(number_format($reais / 1000, 1, ',', '.'), '0'), ',');

            return $thousands.'k';
        }

        return number_format($reais, 0, ',', '.');
    }

    /**
     * @param  Collection<int, array{date: ?string, label: string, amount: int, state: string, detail: string, tone: string, category: ?string, categorySlug: ?string}>  $events
     * @return array{date: ?string, label: string, weekday: string, total: int, isToday: bool, isEvent: bool, isPast: bool, isUndated: bool, slug: ?string, events: list<array{date: ?string, label: string, amount: int, state: string, detail: string, tone: string, category: ?string, categorySlug: ?string}>}
     */
    private function weekColumn(int $offset, Collection $events): array
    {
        $start = $this->anchorWeek()->addWeeks($offset);
        $end = $start->copy()->endOfWeek(Carbon::SUNDAY);
        $today = now()->startOfDay();

        return [
            'date' => $start->toDateString(),
            'label' => $this->weekLabel($offset),
            'weekday' => $start->translatedFormat('d M').' – '.$end->translatedFormat('d M'),
            'total' => (int) $events->sum('amount'),
            'isToday' => $today->betweenIncluded($start, $end->copy()->startOfDay()),
            'isEvent' => $offset === 0 && $this->event->starts_at !== null,
            'isPast' => $end->copy()->startOfDay()->lt($today),
            'isUndated' => false,
            'slug' => null,
            'events' => $events->all(),
        ];
    }

    /**
     * @param  Collection<int, array{date: ?string, label: string, amount: int, state: string, detail: string, tone: string, category: ?string}>  $events
     * @return array{date: ?string, label: string, weekday: string, total: int, isToday: bool, isEvent: bool, isPast: bool, isUndated: bool, events: list<array{date: ?string, label: string, amount: int, state: string, detail: string, tone: string, category: ?string}>}
     */
    private function column(?string $date, Collection $events, ?string $undatedLabel = null, ?string $slug = null, ?string $subtitle = null, ?string $label = null): array
    {
        $day = $date ? Carbon::parse($date)->startOfDay() : null;
        $eventDay = $this->event->starts_at?->copy()->startOfDay();

        return [
            'date' => $date,
            'label' => $label ?? ($day ? $day->translatedFormat('d M') : ($undatedLabel ?? 'Sem data')),
            'weekday' => $subtitle ?? ($day ? $day->translatedFormat('l') : 'Sem vencimento'),
            'total' => (int) $events->sum('amount'),
            'isToday' => $day?->isSameDay(now()) ?? false,
            'isEvent' => $day && $eventDay ? $day->isSameDay($eventDay) : false,
            'isPast' => $day ? $day->lt(now()->startOfDay()) : false,
            'isUndated' => $date === null,
            'slug' => $slug,
            'events' => $events->all(),
        ];
    }
}
