<?php

namespace App\Domain\Events;

use App\Models\Event;
use App\Models\ScheduleItem;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ScheduleFlow
{
    /**
     * @param  Collection<int, ScheduleItem>  $items
     * @return array{
     *     doors: array{clock: string, foot: string},
     *     close: array{clock: string, foot: string},
     *     span: array{value: string, foot: string},
     *     blocks: array{value: string, foot: string},
     *     range: string|null,
     *     days: list<array{key: string, label: string, kicker: string, beats: list<array{id: int, title: string, clock: string, code: string|null, duration: string|null, place: string|null, phase: string, current: bool}>}>,
     *     undated: list<array{id: int, title: string, place: string|null}>
     * }
     */
    public function present(Event $event, Collection $items, int $limit = 6): array
    {
        $dated = $items
            ->filter(fn (ScheduleItem $item): bool => $item->starts_at !== null)
            ->sortBy(fn (ScheduleItem $item): string => $item->starts_at->format('Y-m-d H:i:s').sprintf('%06d', $item->sort_order))
            ->values();

        $now = now();
        $anchor = $dated->search(fn (ScheduleItem $item): bool => $now->lt($this->endsAt($item)));

        if ($anchor === false) {
            $anchor = max(0, $dated->count() - 1);
        }

        $start = max(0, $anchor - 1);

        if ($start + $limit > $dated->count()) {
            $start = max(0, $dated->count() - $limit);
        }

        return $this->forEvent($event, $dated->slice($start, $limit)->values());
    }

    /**
     * @param  Collection<int, ScheduleItem>  $items
     * @return array{
     *     doors: array{clock: string, foot: string},
     *     close: array{clock: string, foot: string},
     *     span: array{value: string, foot: string},
     *     blocks: array{value: string, foot: string},
     *     range: string|null,
     *     days: list<array{key: string, label: string, kicker: string, beats: list<array{id: int, title: string, clock: string, code: string|null, duration: string|null, place: string|null, phase: string, current: bool}>}>,
     *     undated: list<array{id: int, title: string, place: string|null}>
     * }
     */
    public function forEvent(Event $event, Collection $items): array
    {
        $dated = $items
            ->filter(fn (ScheduleItem $item): bool => $item->starts_at !== null)
            ->sortBy(fn (ScheduleItem $item): string => $item->starts_at->format('Y-m-d H:i:s').sprintf('%06d', $item->sort_order))
            ->values();

        $undated = $items
            ->filter(fn (ScheduleItem $item): bool => $item->starts_at === null)
            ->sortBy(fn (ScheduleItem $item): int => $item->sort_order)
            ->values();

        $doors = $event->starts_at;
        $first = $dated->first();
        $last = $dated->last();
        $lastEnd = $last instanceof ScheduleItem ? $this->endsAt($last) : null;

        $days = [];

        foreach ($dated as $item) {
            $start = $item->starts_at;
            $key = $start->toDateString();

            if (! isset($days[$key])) {
                $days[$key] = [
                    'key' => $key,
                    'label' => $start->translatedFormat('d M'),
                    'kicker' => $this->dayKicker($doors, $start),
                    'beats' => [],
                ];
            }

            $days[$key]['beats'][] = $this->beat($event, $item);
        }

        $spanMinutes = $first instanceof ScheduleItem && $lastEnd !== null
            ? (int) round(($lastEnd->getTimestamp() - $first->starts_at->getTimestamp()) / 60)
            : null;

        return [
            'doors' => [
                'clock' => $doors?->format('H:i') ?? '—',
                'foot' => $doors?->translatedFormat('d M') ?? 'Sem abertura marcada',
            ],
            'close' => [
                'clock' => $lastEnd?->format('H:i') ?? '—',
                'foot' => $lastEnd?->translatedFormat('d M') ?? 'Sem fim marcado',
            ],
            'span' => [
                'value' => $spanMinutes !== null && $spanMinutes > 0 ? $this->lengthLabel($spanMinutes) : '—',
                'foot' => $first instanceof ScheduleItem && $doors !== null && $first->starts_at->lt($doors)
                    ? 'Da chegada ao fim'
                    : 'Do primeiro ao último',
            ],
            'blocks' => [
                'value' => (string) $items->count(),
                'foot' => $undated->isEmpty()
                    ? 'Todos com hora'
                    : $undated->count().($undated->count() === 1 ? ' sem hora' : ' sem hora'),
            ],
            'range' => $first instanceof ScheduleItem && $lastEnd !== null
                ? $first->starts_at->translatedFormat('d M H:i').' → '.$lastEnd->translatedFormat('d M H:i')
                : null,
            'days' => array_values($days),
            'undated' => $undated->map(fn (ScheduleItem $item): array => [
                'id' => $item->id,
                'title' => $item->title,
                'place' => $this->place($item),
            ])->all(),
        ];
    }

    /**
     * @return array{id: int, title: string, clock: string, code: string|null, duration: string|null, place: string|null, phase: string, current: bool}
     */
    private function beat(Event $event, ScheduleItem $item): array
    {
        $start = $item->starts_at;
        $end = $this->endsAt($item);
        $doors = $event->starts_at;
        $now = now();

        $phase = 'night';

        if ($doors !== null && $start->lt($doors) && $end->lte($doors)) {
            $phase = 'prep';
        } elseif ($doors !== null && $start->lte($doors) && $end->gt($doors)) {
            $phase = 'doors';
        } elseif ($doors !== null && $start->equalTo($doors)) {
            $phase = 'doors';
        }

        $current = $item->duration_minutes
            ? $now->gte($start) && $now->lt($end)
            : false;

        return [
            'id' => $item->id,
            'title' => $item->title,
            'clock' => $start->format('H:i'),
            'code' => $doors !== null ? $this->offsetCode((int) round(($start->getTimestamp() - $doors->getTimestamp()) / 60)) : null,
            'duration' => $item->duration_minutes ? $this->lengthLabel($item->duration_minutes) : null,
            'place' => $this->place($item),
            'phase' => $phase,
            'current' => $current,
        ];
    }

    private function endsAt(ScheduleItem $item): CarbonInterface
    {
        $start = $item->starts_at;

        if ($item->duration_minutes) {
            return $start->copy()->addMinutes($item->duration_minutes);
        }

        return $start->copy();
    }

    private function dayKicker(?CarbonInterface $doors, CarbonInterface $start): string
    {
        if ($doors === null) {
            return 'Dia';
        }

        $offset = (int) round(($start->copy()->startOfDay()->getTimestamp() - $doors->copy()->startOfDay()->getTimestamp()) / 86400);

        return match (true) {
            $offset === -1 => 'Véspera',
            $offset < 0 => 'Antes',
            $offset === 0 => 'Dia da festa',
            $offset === 1 => 'Dia seguinte',
            default => 'Depois',
        };
    }

    private function offsetCode(int $minutes): string
    {
        if ($minutes === 0) {
            return 'T0';
        }

        $sign = $minutes < 0 ? '-' : '+';
        $absolute = abs($minutes);
        $hours = intdiv($absolute, 60);
        $rest = $absolute % 60;

        if ($hours === 0) {
            return 'T'.$sign.$rest.'min';
        }

        if ($rest === 0) {
            return 'T'.$sign.$hours.'h';
        }

        return 'T'.$sign.$hours.'h'.$rest;
    }

    private function lengthLabel(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        if ($hours === 0) {
            return $rest.'min';
        }

        if ($rest === 0) {
            return $hours.'h';
        }

        return $hours.'h'.$rest;
    }

    private function place(ScheduleItem $item): ?string
    {
        $parts = array_values(array_filter([
            $item->location,
            $item->vendor?->name,
        ]));

        return $parts === [] ? null : implode(' · ', $parts);
    }
}
