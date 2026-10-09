<?php

namespace App\Livewire\Events;

use App\Domain\Events\OfficialCatalog;
use App\Models\EventTemplate;
use App\Support\EventTemplatePresentation;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app.dashboard')]
#[Title('Escolher modelo')]
class PickEventTemplate extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()?->canCreateEvents(), 403);
    }

    public function render(): View
    {
        OfficialCatalog::ensure();

        $order = EventTemplatePresentation::orderedSlugs();

        /** @var Collection<int, EventTemplate> $templates */
        $templates = EventTemplate::query()
            ->where('is_official', true)
            ->get()
            ->sortBy(fn (EventTemplate $template): int => array_search($template->slug, $order, true) ?: 99)
            ->values();

        return view('livewire.events.pick-template', [
            'templates' => $templates,
        ]);
    }
}
