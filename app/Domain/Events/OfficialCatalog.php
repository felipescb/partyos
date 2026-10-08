<?php

namespace App\Domain\Events;

use App\Enums\EventType;
use App\Models\CostCategory;
use App\Models\EventTemplate;
use Illuminate\Support\Str;

final class OfficialCatalog
{
    public static function ensure(): void
    {
        if (! CostCategory::query()->where('is_system', true)->exists()) {
            foreach (self::categories() as $slug => $name) {
                CostCategory::query()->updateOrCreate(
                    ['slug' => $slug],
                    ['name' => $name, 'is_system' => true, 'organization_id' => null],
                );
            }
        }

        self::syncTemplates();
    }

    public static function sync(): void
    {
        foreach (self::categories() as $slug => $name) {
            CostCategory::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'is_system' => true, 'organization_id' => null],
            );
        }

        self::syncTemplates();
    }

    public static function syncTemplates(): void
    {
        $activeSlugs = [];

        foreach (self::templates() as $template) {
            $activeSlugs[] = $template['slug'];

            EventTemplate::query()->updateOrCreate(
                ['slug' => $template['slug'], 'organization_id' => null],
                [
                    'name' => $template['name'],
                    'type' => $template['type'],
                    'description' => $template['description'],
                    'payload' => [
                        'tasks' => $template['tasks'],
                        'schedule' => $template['schedule'],
                    ],
                    'is_official' => true,
                ],
            );
        }

        EventTemplate::query()
            ->whereNull('organization_id')
            ->whereNotIn('slug', $activeSlugs)
            ->update(['is_official' => false]);
    }

    /**
     * @return array<string, string>
     */
    public static function categories(): array
    {
        return [
            'artistas' => 'Artistas',
            'caches' => 'Cachês',
            'venue' => 'Venue',
            'seguranca' => 'Segurança',
            'fotografia' => 'Fotografia',
            'video' => 'Vídeo',
            'som' => 'Som',
            'luz' => 'Luz',
            'cenografia' => 'Cenografia',
            'producao' => 'Produção',
            'staff' => 'Staff',
            'marketing' => 'Marketing',
            'transporte' => 'Transporte',
            'hospedagem' => 'Hospedagem',
            'alimentacao' => 'Alimentação',
            'bar' => 'Bar',
            'taxas' => 'Taxas',
            'impostos' => 'Impostos',
            'licencas' => 'Licenças',
            'outros' => 'Outros',
        ];
    }

    /**
     * @return list<array{slug: string, name: string, type: EventType, description: string, tasks: list<array{title: string, category: string, priority: string}>, schedule: list<array{title: string, offset_minutes: int, duration_minutes: int}>}>
     */
    public static function templates(): array
    {
        return [
            self::template('festa', 'Festa / Rave', EventType::Party, 'Casa ou galpão, lineup, pista, portaria e noite longa.', [
                ['Definir o local ou venue', 'producao', 'high'],
                ['Montar o lineup', 'artistas', 'high'],
                ['Fechar som e luz', 'producao', 'high'],
                ['Contratar segurança e brigada', 'producao', 'high'],
                ['Checar gerador e energia', 'producao', 'high'],
                ['Definir bar e operação de pista', 'producao', 'medium'],
                ['Contratar foto e vídeo', 'producao', 'medium'],
                ['Criar identidade e abrir vendas', 'marketing', 'high'],
                ['Divulgar', 'marketing', 'medium'],
                ['Confirmar riders', 'artistas', 'medium'],
                ['Fechar lista e portaria', 'producao', 'high'],
                ['Preparar caixa', 'financeiro', 'high'],
                ['Briefing da equipe', 'producao', 'medium'],
                ['Fechar a produção', 'producao', 'high'],
            ], [
                ['Chegada da estrutura', -480, 120],
                ['Montagem', -360, 240],
                ['Soundcheck', -90, 60],
                ['Abertura da casa', 0, 120],
                ['Primeiro artista', 120, 75],
                ['Pico da pista', 240, 180],
                ['Encerramento', 480, 30],
                ['Desmontagem', 510, 90],
            ]),
            self::template('jantar', 'Jantar', EventType::Dinner, 'Mesa, cozinha, serviço e convidados.', [
                ['Definir o menu', 'producao', 'high'],
                ['Fechar o espaço', 'producao', 'high'],
                ['Lista de convidados', 'producao', 'high'],
                ['Fornecedor de comida e bebida', 'producao', 'high'],
                ['Mesa e ambientação', 'producao', 'medium'],
                ['Confirmar RSVP', 'producao', 'medium'],
            ], [
                ['Montagem da sala', -180, 90],
                ['Recepção', 0, 40],
                ['Jantar', 40, 90],
                ['Encerramento', 150, 30],
            ]),
            self::template('exposicao', 'Exposição', EventType::Exhibition, 'Montagem, obras, visitação e desmontagem.', [
                ['Lista de obras ou peças', 'producao', 'high'],
                ['Planta da montagem', 'producao', 'high'],
                ['Seguro e licenças', 'producao', 'high'],
                ['Abertura para convidados', 'marketing', 'medium'],
                ['Equipe de mediação', 'producao', 'medium'],
            ], [
                ['Montagem', -480, 360],
                ['Abertura', 0, 120],
                ['Visitação', 120, 180],
            ]),
        ];
    }

    /**
     * @param  list<array{0: string, 1: string, 2: string}>  $tasks
     * @param  list<array{0: string, 1: int, 2: int}>  $schedule
     * @return array{slug: string, name: string, type: EventType, description: string, tasks: list<array{title: string, category: string, priority: string}>, schedule: list<array{title: string, offset_minutes: int, duration_minutes: int}>}
     */
    private static function template(string $slug, string $name, EventType $type, string $description, array $tasks, array $schedule): array
    {
        return [
            'slug' => $slug,
            'name' => $name,
            'type' => $type,
            'description' => $description,
            'tasks' => array_map(fn (array $task): array => [
                'title' => $task[0],
                'category' => $task[1],
                'priority' => $task[2],
            ], $tasks),
            'schedule' => array_map(fn (array $item): array => [
                'title' => $item[0],
                'offset_minutes' => $item[1],
                'duration_minutes' => $item[2],
            ], $schedule),
        ];
    }

    public static function categoryId(string $slug): ?int
    {
        self::ensure();

        $id = CostCategory::query()->where('slug', $slug)->value('id');

        return $id === null ? null : (int) $id;
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $base = $base !== '' ? $base : 'categoria';
        $slug = $base;
        $suffix = 2;

        while (CostCategory::query()
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
