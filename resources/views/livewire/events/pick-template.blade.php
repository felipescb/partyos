<div class="broker-form-stage broker-form-stage-wide">
    <article class="broker-wizard-card broker-template-shell" aria-labelledby="template-picker-title">
        <header class="broker-wizard-head">
            <div>
                <x-event-category-symbol category="novo" />
                <h1 id="template-picker-title" class="broker-wizard-title">Escolha um modelo</h1>
                <p class="broker-template-lead">Cada modelo traz checklist e cronograma sugeridos. Você edita tudo depois no quadro.</p>
            </div>
        </header>

        <div class="broker-template-grid" role="list">
            <a
                href="{{ route('events.create.configure', ['template' => 'blank']) }}"
                wire:navigate
                class="broker-template-card broker-template-card-blank"
                role="listitem"
            >
                <span class="broker-template-icon broker-template-icon-blank" aria-hidden="true">
                    <flux:icon.plus variant="outline" class="size-6" />
                </span>
                <span class="broker-template-name">Começar do zero</span>
                <span class="broker-template-desc">Só o evento e o orçamento. Sem tarefas nem horários prontos.</span>
                <ul class="broker-template-includes">
                    <li>Orçamento vazio</li>
                    <li>Checklist vazio</li>
                    <li>Cronograma vazio</li>
                </ul>
            </a>

            @foreach ($templates as $template)
                @php($includes = \App\Support\EventTemplatePresentation::includes($template))
                <a
                    href="{{ route('events.create.configure', ['template' => $template->id]) }}"
                    wire:navigate
                    class="broker-template-card"
                    role="listitem"
                >
                    <span class="broker-template-icon" aria-hidden="true">
                        <x-event-template-icon :slug="$template->slug" variant="outline" class="size-6" />
                    </span>
                    <span class="broker-template-name">{{ $template->name }}</span>
                    <span class="broker-template-desc">{{ $template->description }}</span>
                    <ul class="broker-template-includes">
                        @foreach ($includes as $line)
                            <li>{{ $line }}</li>
                        @endforeach
                    </ul>
                </a>
            @endforeach
        </div>
    </article>
</div>
