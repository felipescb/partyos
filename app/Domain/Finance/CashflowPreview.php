<?php

namespace App\Domain\Finance;

use App\Models\Event;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class CashflowPreview
{
    private const DAY_LIMIT = 4;

    private const WEEK_LIMIT = 4;

    public function __construct(private CashflowMovements $movements) {}

    /**
     * @return array{amount: int, count: int, days: list<array{code: string, label: string, when: string, state: string, amount: int, slug: string}>, weeks: list<array{code: string, label: string, when: string, state: string, amount: int, slug: string}>}
     */
    public function forEvent(Event $event): array
    {
        $today = now()->startOfDay();
        $dayEnd = $today->copy()->addDays(6);

        $upcoming = $this->movements->rows($event)
            ->filter(fn (array $row): bool => $row['date'] !== null && $row['tone'] !== 'paid' && Carbon::parse($row['date'])->startOfDay()->gte($today))
            ->sortBy(fn (array $row): string => $row['date'].$row['label'])
            ->values();

        $days = $upcoming
            ->filter(fn (array $row): bool => Carbon::parse($row['date'])->startOfDay()->lte($dayEnd))
            ->take(self::DAY_LIMIT)
            ->map(fn (array $row): array => $this->line($event, $row, 'day'))
            ->all();

        $weeks = $upcoming
            ->filter(fn (array $row): bool => Carbon::parse($row['date'])->startOfDay()->gt($dayEnd))
            ->take(self::WEEK_LIMIT)
            ->map(fn (array $row): array => $this->line($event, $row, 'week'))
            ->all();

        return [
            'amount' => (int) $upcoming->sum('amount'),
            'count' => $upcoming->count(),
            'days' => $days,
            'weeks' => $weeks,
        ];
    }

    /**
     * @param  array{date: ?string, label: string, amount: int, state: string, detail: string, tone: string, category: ?string, categorySlug: ?string}  $row
     * @return array{code: string, label: string, when: string, state: string, amount: int, slug: string}
     */
    private function line(Event $event, array $row, string $grain): array
    {
        $date = (string) $row['date'];

        return [
            'code' => $grain === 'day' ? $this->dayLabel($this->dayOffset($event, $date)) : $this->weekLabel($this->weekOffset($event, $date)),
            'label' => $row['label'],
            'when' => Carbon::parse($date)->translatedFormat('d M'),
            'state' => $row['state'],
            'amount' => $row['amount'],
            'slug' => $row['categorySlug'] ?? 'none',
        ];
    }

    private function anchor(Event $event): CarbonInterface
    {
        return ($event->starts_at ?? now())->copy()->startOfDay();
    }

    private function dayOffset(Event $event, string $date): int
    {
        return (int) $this->anchor($event)->diffInDays(Carbon::parse($date)->startOfDay(), false);
    }

    private function dayLabel(int $offset): string
    {
        if ($offset === 0) {
            return 'D0';
        }

        return $offset > 0 ? 'D+'.$offset : 'D'.$offset;
    }

    private function weekOffset(Event $event, string $date): int
    {
        $week = Carbon::parse($date)->startOfDay()->startOfWeek(Carbon::MONDAY);
        $anchor = $this->anchor($event)->startOfWeek(Carbon::MONDAY);
        $days = (int) $anchor->diffInDays($week, false);

        return intdiv($days, 7);
    }

    private function weekLabel(int $offset): string
    {
        if ($offset === 0) {
            return 'W0';
        }

        return $offset > 0 ? 'W+'.$offset : 'W'.$offset;
    }
}
