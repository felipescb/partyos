@props([
    'event',
    'members',
    'memberCount' => 0,
    'canManage' => false,
    'roles' => [],
])

<article {{ $attributes->class(['broker-card', 'broker-card-module', 'broker-team-panel']) }} role="listitem">
    <header class="broker-team-panel-head">
        <div>
            <x-event-category-symbol category="equipe" />
            <h2 class="broker-team-panel-title">Equipe</h2>
        </div>
        <div class="broker-team-panel-actions">
            @if ($canManage)
                <button
                    type="button"
                    class="broker-card-icon-btn"
                    wire:click="openTeamModal"
                    aria-label="Adicionar pessoa"
                >
                    <flux:icon.plus variant="mini" class="size-4" />
                </button>
            @endif
            <a href="{{ route('events.team', $event) }}" wire:navigate class="broker-task-panel-link">
                Ver todas{{ $memberCount > 0 ? ' · '.$memberCount : '' }}
            </a>
        </div>
    </header>

    <div class="broker-team-list" role="list" aria-label="Equipe do evento">
        @forelse ($members as $member)
            @php($role = \App\Enums\EventRole::tryFrom($member->pivot->role))
            <div class="broker-team-row" role="listitem" wire:key="team-member-{{ $member->id }}">
                <div class="broker-team-field">
                    <span class="broker-team-field-label">Nome</span>
                    <span class="broker-team-field-value">{{ $member->name }}</span>
                </div>
                <div class="broker-team-field">
                    <span class="broker-team-field-label">Atribuição</span>
                    @if ($canManage && $role)
                        <select
                            class="broker-team-field-select"
                            aria-label="Atribuição de {{ $member->name }}"
                            wire:change="updateTeamMemberRole({{ $member->id }}, $event.target.value)"
                        >
                            @foreach ($roles as $roleOption)
                                <option value="{{ $roleOption->value }}" @selected($member->pivot->role === $roleOption->value)>
                                    {{ $roleOption->label() }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        <span class="broker-team-field-value">{{ $role?->label() ?? $member->pivot->role }}</span>
                    @endif
                </div>
            </div>
        @empty
            <div class="broker-team-empty">
                <p>Ninguém na equipe ainda.</p>
                @if ($canManage)
                    <button type="button" class="broker-task-panel-link" wire:click="openTeamModal">Adicionar pessoa</button>
                @else
                    <a href="{{ route('events.team', $event) }}" wire:navigate class="broker-task-panel-link">Abrir equipe</a>
                @endif
            </div>
        @endforelse
    </div>
</article>
