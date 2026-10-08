<?php

namespace App\Support;

use App\Models\EventTemplate;

final class EventTemplatePresentation
{
    /**
     * @return list<string>
     */
    public static function includes(EventTemplate $template): array
    {
        $tasks = count($template->payload['tasks'] ?? []);
        $schedule = count($template->payload['schedule'] ?? []);

        $lines = [
            "{$tasks} ".($tasks === 1 ? 'tarefa' : 'tarefas').' no checklist',
            "{$schedule} ".($schedule === 1 ? 'horário' : 'horários').' sugeridos no cronograma',
            'Orçamento vazio pronto para custos',
        ];

        $preview = array_slice(array_map(
            fn (array $task): string => $task['title'],
            $template->payload['tasks'] ?? [],
        ), 0, 3);

        if ($preview !== []) {
            $lines[] = 'Ex.: '.implode(' · ', $preview);
        }

        return $lines;
    }

    public static function icon(string $slug): string
    {
        return match ($slug) {
            'festa' => 'sparkles',
            'jantar' => 'building-storefront',
            'exposicao' => 'photo',
            default => 'document-text',
        };
    }

    /**
     * @return list<string>
     */
    public static function orderedSlugs(): array
    {
        return [
            'festa',
            'jantar',
            'exposicao',
        ];
    }
}
