<?php

namespace App\Domain\Events;

use App\Enums\ScenarioKind;
use App\Models\Event;
use App\Models\FinancialScenario;
use App\Models\TicketTier;
use Illuminate\Support\Collection;

final class ScenarioPresets
{
    public function generate(Event $event, bool $replace = false): void
    {
        if ($replace) {
            $event->scenarios()->delete();
        } elseif ($event->scenarios()->exists()) {
            return;
        }

        $event->loadMissing(['ticketTiers', 'revenues', 'budgetItems']);
        $tiers = $event->ticketTiers;
        $goal = (int) $tiers->sum('goal');

        foreach ([ScenarioKind::Bad, ScenarioKind::Conservative, ScenarioKind::Expected, ScenarioKind::Ok, ScenarioKind::Great] as $kind) {
            $attendance = $goal > 0 ? intdiv($goal * $kind->scalePercent() + 50, 100) : 0;

            if ($event->capacity) {
                $attendance = min($attendance, $event->capacity);
            }

            $scenario = $event->scenarios()->create([
                'name' => $kind->label(),
                'kind' => $kind,
                'attendance' => $attendance,
            ]);

            $this->writeLines($scenario, $event, $this->scale($tiers, $attendance));
        }
    }

    /**
     * @param  Collection<int, TicketTier>  $tiers
     * @return array<int, int>
     */
    public function scale(Collection $tiers, int $attendance): array
    {
        $goal = (int) $tiers->sum('goal');
        $quantities = [];
        $used = 0;
        $lastId = $tiers->last()?->id;

        foreach ($tiers as $tier) {
            if ($tier->id === $lastId) {
                $quantities[$tier->id] = max(0, $attendance - $used);

                continue;
            }

            $share = $goal > 0 ? intdiv($attendance * $tier->goal, $goal) : 0;
            $quantities[$tier->id] = $share;
            $used += $share;
        }

        return $quantities;
    }

    /**
     * @param  array<int, int>  $ticketQuantities
     */
    private function writeLines(FinancialScenario $scenario, Event $event, array $ticketQuantities): void
    {
        foreach ($event->ticketTiers as $tier) {
            $scenario->lines()->create([
                'line_type' => 'ticket',
                'reference_id' => $tier->id,
                'label' => $tier->name,
                'quantity' => $ticketQuantities[$tier->id] ?? 0,
            ]);
        }

        foreach ($event->revenues as $revenue) {
            if ($revenue->status->value === 'cancelled') {
                continue;
            }

            $scenario->lines()->create([
                'line_type' => 'revenue',
                'reference_id' => $revenue->id,
                'label' => $revenue->description,
                'amount' => $revenue->expected_amount,
            ]);
        }

        foreach ($event->budgetItems as $item) {
            if ($item->status->value === 'cancelled') {
                continue;
            }

            $scenario->lines()->create([
                'line_type' => 'cost',
                'reference_id' => $item->id,
                'label' => $item->description,
                'amount' => $item->contracted_amount ?? $item->estimated_amount,
            ]);
        }
    }
}
