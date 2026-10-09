@php
    $steps = [
        1 => ['label' => 'Essencial', 'hint' => 'Nome e tipo'],
        2 => ['label' => 'Quando & onde', 'hint' => 'Plano e local'],
        3 => ['label' => 'Detalhes', 'hint' => 'Opcional'],
    ];
@endphp

<div>
@if ($editing)
<div class="broker-config-page">
    <form wire:submit="save" class="broker-config-form">
        <header class="broker-tickets-head">
            <div class="broker-tickets-head-main">
                <a href="{{ route('events.show', $editing) }}" wire:navigate class="broker-task-notebook-back">← Quadro</a>
                <div>
                    <x-event-category-symbol category="editar" />
                    <h1 id="event-wizard-title" class="broker-cashflow-title">Configuração</h1>
                    <p class="broker-cashflow-sub">{{ $name ?: 'Evento' }}</p>
                </div>
            </div>
            <div class="broker-tickets-head-actions">
                <button type="submit" class="broker-config-glyph broker-config-glyph-save" aria-label="Salvar" title="Salvar">
                    <flux:icon.check variant="mini" class="size-4" />
                </button>
                <button type="button" class="broker-config-glyph broker-config-glyph-delete" wire:click="$set('confirmDelete', true)" aria-label="Excluir" title="Excluir">
                    <flux:icon.trash variant="mini" class="size-4" />
                </button>
            </div>
        </header>

        @if ($errors->any())
            <div class="broker-wizard-alert" role="alert">
                <p class="broker-wizard-alert-title">Revise estes pontos:</p>
                <ul class="broker-wizard-alert-list">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="broker-grid broker-grid-event broker-grid-config" role="list" aria-label="Configuração do evento">
            <article class="broker-grid-item broker-card broker-config-card" role="listitem">
                <x-event-category-symbol category="evento" />
                <h2 class="broker-card-name">Essencial</h2>
                <div class="broker-config-fields">
                    <flux:input wire:model="name" label="Nome" placeholder="Festa X — Outubro" required autofocus />
                    <flux:select wire:model="type" label="Tipo">
                        @foreach ($types as $type)
                            <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="status" label="Status">
                        @foreach ($statuses as $status)
                            <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </article>

            <article class="broker-grid-item broker-card broker-config-card" role="listitem">
                <x-event-category-symbol category="quando" />
                <h2 class="broker-card-name">Quando</h2>
                <div class="broker-config-fields">
                    <p class="broker-config-note">Essas datas alimentam o cronograma sugerido e o plano de produção. O término é calculado quando você informar a execução e a duração.</p>
                    <flux:input wire:model="planningStartsAt" type="date" label="Início do planejamento" />
                    <flux:input wire:model="executionAt" type="datetime-local" label="Data de execução da festa" />
                    <flux:error name="planningStartsAt" />
                    <flux:error name="executionAt" />
                    <flux:select wire:model="durationMinutes" label="Duração da festa" placeholder="Quanto tempo dura">
                        <flux:select.option value="">A definir</flux:select.option>
                        @foreach ($durationOptions as $minutes => $label)
                            <flux:select.option value="{{ $minutes }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="durationMinutes" />
                </div>
            </article>

            <article class="broker-grid-item broker-card broker-config-card broker-config-card-place" role="listitem">
                <x-event-category-symbol category="casa" />
                <h2 class="broker-card-name">Onde</h2>
                <div class="broker-config-fields">
                    <flux:input wire:model="venueName" label="Local" placeholder="Casa, galpão, clube" />
                    <flux:input wire:model="city" label="Cidade" />
                    <flux:input wire:model="address" label="Endereço" placeholder="Rua, número — opcional" />
                </div>
            </article>

            <article class="broker-grid-item broker-card broker-config-card broker-config-card-wide" role="listitem">
                <x-event-category-symbol category="leitura" label="Detalhes" />
                <h2 class="broker-card-name">Detalhes</h2>
                <div class="broker-config-fields-split">
                    <flux:input wire:model="capacity" type="number" min="1" label="Capacidade" placeholder="Opcional" />
                    <flux:select wire:model="currency" label="Moeda">
                        <flux:select.option value="BRL">Real (R$)</flux:select.option>
                        <flux:select.option value="USD">Dólar (US$)</flux:select.option>
                        <flux:select.option value="EUR">Euro (€)</flux:select.option>
                    </flux:select>
                    <flux:field>
                        <flux:label>Capa <span class="font-normal text-steel">(opcional)</span></flux:label>
                        <flux:input.file wire:model="cover" accept="image/jpeg,image/png,image/webp" />
                        <flux:description>JPG, PNG ou WebP · até 12 MB.</flux:description>
                        <flux:error name="cover" />
                        <flux:error name="files.0" />
                        @if ($cover)
                            <div class="mt-2">
                                <flux:button type="button" size="sm" variant="ghost" wire:click="clearCover">Remover capa</flux:button>
                            </div>
                        @endif
                    </flux:field>
                    <div class="broker-config-span-2">
                        <flux:textarea wire:model="description" label="Descrição curta" rows="2" placeholder="Uma linha sobre o evento" />
                    </div>
                    <flux:textarea wire:model="notes" label="Observações" rows="2" placeholder="Só o que precisa lembrar agora" />
                </div>
            </article>
        </div>
    </form>

    @can('viewFinance', $editing)
        <livewire:finance.distribution-board :event="$editing" :key="'event-distribution-'.$editing->id" />
    @endcan

    <flux:modal wire:model="confirmDelete" class="max-w-md">
        <flux:heading size="lg">Excluir este evento?</flux:heading>
        <flux:text class="mt-2">Excluir este evento remove o acesso a custos, convidados, tarefas e demais informações relacionadas. A exclusão fica na lixeira do sistema e não apaga o histórico de auditoria.</flux:text>
        <div class="mt-6 flex justify-end gap-2">
            <flux:button type="button" wire:click="$set('confirmDelete', false)">Cancelar</flux:button>
            <flux:button type="button" variant="danger" wire:click="delete">Excluir evento</flux:button>
        </div>
    </flux:modal>
</div>
@else
<div class="broker-form-stage">
    <article class="broker-wizard-card" aria-labelledby="event-wizard-title">
        <header class="broker-wizard-head">
            <div>
                <x-event-category-symbol category="novo" />
                <h1 id="event-wizard-title" class="broker-wizard-title">Montar evento</h1>
            </div>
            <ol class="broker-wizard-steps" aria-label="Passos">
                @foreach ($steps as $number => $meta)
                    <li @class([
                        'broker-wizard-step',
                        'broker-wizard-step-done' => $number < $step,
                        'broker-wizard-step-current' => $number === $step,
                    ])>
                        <span class="broker-wizard-step-index">{{ $number }}</span>
                        <span class="broker-wizard-step-text">
                            <span class="broker-wizard-step-label">{{ $meta['label'] }}</span>
                            <span class="broker-wizard-step-hint">{{ $meta['hint'] }}</span>
                        </span>
                    </li>
                @endforeach
            </ol>
        </header>

        <form wire:submit="save" class="broker-wizard-body">
            @if ($step === 1)
                <div class="broker-wizard-panel">
                    <flux:input wire:model="name" label="Nome" placeholder="Festa X — Outubro" required autofocus />
                    <flux:select wire:model="type" label="Tipo">
                        @foreach ($types as $type)
                            <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <div class="broker-wizard-model">
                        <div>
                            <p class="broker-card-metric-label">Modelo</p>
                            <p class="broker-wizard-model-name">{{ $templateLabel }}</p>
                        </div>
                        <flux:button type="button" :href="route('events.create')" wire:navigate variant="ghost" size="sm">
                            Trocar
                        </flux:button>
                    </div>
                    <p class="broker-wizard-note">Status inicial: rascunho. Você ajusta depois no quadro do evento.</p>
                </div>
            @elseif ($step === 2)
                <div class="broker-wizard-panel">
                    <p class="broker-wizard-note">Essas datas alimentam o cronograma sugerido e o plano de produção. A duração pode ser definida antes da data da festa; o término é calculado quando você informar execução.</p>
                    <div class="broker-wizard-row">
                        <flux:input wire:model="planningStartsAt" type="date" label="Início do planejamento" />
                        <flux:input wire:model="executionAt" type="datetime-local" label="Data de execução da festa" />
                    </div>
                    <flux:error name="planningStartsAt" />
                    <flux:error name="executionAt" />
                    <flux:select wire:model="durationMinutes" label="Duração da festa" placeholder="Quanto tempo dura">
                        <flux:select.option value="">A definir</flux:select.option>
                        @foreach ($durationOptions as $minutes => $label)
                            <flux:select.option value="{{ $minutes }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="durationMinutes" />
                    <div class="broker-wizard-row broker-wizard-row-spaced">
                        <flux:input wire:model="venueName" label="Local" placeholder="Casa, galpão, clube" />
                        <flux:input wire:model="city" label="Cidade" />
                    </div>
                    <flux:input wire:model="address" label="Endereço" placeholder="Rua, número — opcional" />
                </div>
            @else
                <div class="broker-wizard-panel">
                    <div class="broker-wizard-row">
                        <flux:input wire:model="capacity" type="number" min="1" label="Capacidade" placeholder="Opcional" />
                        <flux:select wire:model="currency" label="Moeda">
                            <flux:select.option value="BRL">Real (R$)</flux:select.option>
                            <flux:select.option value="USD">Dólar (US$)</flux:select.option>
                            <flux:select.option value="EUR">Euro (€)</flux:select.option>
                        </flux:select>
                    </div>
                    <flux:textarea wire:model="description" label="Descrição curta" rows="2" placeholder="Uma linha sobre o evento" />
                    <flux:field>
                        <flux:label>Capa <span class="font-normal text-steel">(opcional)</span></flux:label>
                        <flux:input.file wire:model="cover" accept="image/jpeg,image/png,image/webp" />
                        <flux:description>JPG, PNG ou WebP · até 12 MB. Pode criar o evento sem capa.</flux:description>
                        <flux:error name="cover" />
                        <flux:error name="files.0" />
                        @if ($cover)
                            <div class="mt-2">
                                <flux:button type="button" size="sm" variant="ghost" wire:click="clearCover">Remover capa</flux:button>
                            </div>
                        @endif
                    </flux:field>
                    <flux:textarea wire:model="notes" label="Observações" rows="2" placeholder="Só o que precisa lembrar agora" />
                </div>
            @endif

            @if ($errors->any())
                <div class="broker-wizard-alert" role="alert">
                    <p class="broker-wizard-alert-title">Revise estes pontos:</p>
                    <ul class="broker-wizard-alert-list">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <footer class="broker-wizard-foot">
                <div class="broker-wizard-foot-start">
                    @if ($step > 1)
                        <flux:button type="button" wire:click="previousStep">Voltar</flux:button>
                    @else
                        <flux:button type="button" :href="route('dashboard')" wire:navigate variant="ghost">Cancelar</flux:button>
                    @endif
                </div>
                <div class="broker-wizard-foot-end">
                    @if ($step < 3)
                        <flux:button type="button" variant="primary" wire:click="nextStep">Continuar</flux:button>
                    @else
                        <flux:button variant="primary" type="submit">Criar evento</flux:button>
                    @endif
                </div>
            </footer>
        </form>
    </article>
</div>
@endif
</div>
