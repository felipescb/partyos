<?php

namespace App\Livewire;

use App\Domain\Events\OfficialCatalog;
use App\Domain\Finance\EventFinanceReader;
use App\Domain\Finance\StatementBuilder;
use App\Livewire\Concerns\DuplicatesEvents;
use App\Models\Event;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app.dashboard')]
#[Title('Meus eventos')]
class Portfolio extends Component
{
    use DuplicatesEvents;

    public function render(EventFinanceReader $reader, StatementBuilder $builder): View
    {
        OfficialCatalog::ensure();

        $user = auth()->user();

        $events = Event::query()
            ->when(
                $user->canCreateEvents(),
                fn ($query) => $query->where('organization_id', $user->current_organization_id),
                fn ($query) => $query->whereHas('members', fn ($members) => $members->whereKey($user->id)),
            )
            ->with(['ticketTiers', 'revenues', 'fees', 'distributions', 'budgetItems.payments', 'budgetItems.category', 'budgetItems.vendor', 'guests'])
            ->orderByRaw('starts_at is null')
            ->orderBy('starts_at')
            ->limit(100)
            ->get();

        $rows = $events->map(function (Event $event) use ($reader, $builder): array {
            return [
                'event' => $event,
                'statement' => $builder->statement($reader->read($event)),
            ];
        });

        $visible = $rows
            ->reject(fn (array $row): bool => $row['event']->status->value === 'cancelled')
            ->values();

        return view('livewire.portfolio', [
            'rows' => $visible,
        ]);
    }
}
