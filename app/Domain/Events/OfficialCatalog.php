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
        if (CostCategory::query()->where('is_system', true)->exists()) {
            return;
        }

        self::sync();
    }

    public static function sync(): void
    {
        foreach (self::categories() as $slug => $name) {
            CostCategory::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'is_system' => true, 'organization_id' => null],
            );
        }

        foreach (self::templates() as $template) {
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
            self::template('festa', 'Festa', EventType::Party, 'Estrutura de uma festa com casa, artistas e portaria.', [
                ['Definir o local', 'producao', 'high'],
                ['Fechar artistas', 'artistas', 'high'],
                ['Contratar segurança', 'producao', 'high'],
                ['Contratar foto e vídeo', 'producao', 'medium'],
                ['Criar a identidade', 'marketing', 'medium'],
                ['Abrir as vendas', 'marketing', 'high'],
                ['Divulgar', 'marketing', 'medium'],
                ['Confirmar riders', 'artistas', 'medium'],
                ['Fechar a lista de convidados', 'producao', 'medium'],
                ['Preparar a portaria', 'producao', 'high'],
                ['Preparar o caixa', 'financeiro', 'high'],
                ['Fechar a produção', 'producao', 'high'],
            ], [
                ['Montagem', -240, 180],
                ['Soundcheck', -90, 60],
                ['Abertura da casa', 0, 90],
                ['Primeiro artista', 90, 75],
                ['Encerramento', 360, 30],
                ['Desmontagem', 390, 90],
            ]),
            self::template('rave', 'Rave', EventType::Rave, 'Noite longa, som, luz e operação de pista.', [
                ['Fechar o galpão ou a venue', 'producao', 'high'],
                ['Montar o lineup', 'artistas', 'high'],
                ['Fechar som e luz', 'producao', 'high'],
                ['Contratar segurança e brigada', 'producao', 'high'],
                ['Definir bar e copos', 'producao', 'medium'],
                ['Abrir a pré-venda', 'marketing', 'high'],
                ['Checar gerador e energia', 'producao', 'high'],
                ['Briefing da equipe de pista', 'producao', 'medium'],
            ], [
                ['Chegada da estrutura', -480, 120],
                ['Montagem', -360, 240],
                ['Soundcheck', -90, 60],
                ['Abertura', 0, 120],
                ['Pico da pista', 180, 180],
                ['Encerramento', 480, 30],
            ]),
            self::template('show', 'Show', EventType::Show, 'Apresentação com horário de palco e camarim.', [
                ['Fechar o artista principal', 'artistas', 'high'],
                ['Fechar venue e horário', 'producao', 'high'],
                ['Rider técnico', 'artistas', 'high'],
                ['Som, luz e palco', 'producao', 'high'],
                ['Camarim e hospitalidade', 'producao', 'medium'],
                ['Plano de vendas', 'marketing', 'high'],
                ['Ensaio ou passagem de som', 'producao', 'high'],
            ], [
                ['Montagem', -300, 180],
                ['Passagem de som', -120, 60],
                ['Abertura da casa', -30, 30],
                ['Show', 0, 90],
                ['Encore', 90, 15],
                ['Saída do público', 110, 40],
            ]),
            self::template('festival', 'Festival', EventType::Festival, 'Vários palcos, dias e frentes de produção.', [
                ['Mapa do evento', 'producao', 'high'],
                ['Grade de horários', 'producao', 'high'],
                ['Fornecedores por frente', 'producao', 'high'],
                ['Plano de segurança', 'producao', 'high'],
                ['Bilheteria e lotes', 'marketing', 'high'],
                ['Operação de palco', 'producao', 'medium'],
                ['Plano de contingência', 'producao', 'medium'],
            ], [
                ['Abertura dos portões', 0, 60],
                ['Primeiro show', 60, 50],
                ['Troca de palco', 110, 20],
                ['Headliner', 240, 80],
                ['Encerramento', 360, 30],
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
            self::template('corporativo', 'Evento corporativo', EventType::Corporate, 'Briefing, pauta e fornecedores com contrato.', [
                ['Alinhar o briefing com o cliente', 'producao', 'high'],
                ['Fechar local e capacidade', 'producao', 'high'],
                ['Orçamento aprovado', 'financeiro', 'high'],
                ['Fornecedores e contratos', 'producao', 'high'],
                ['Roteiro do dia', 'producao', 'medium'],
                ['Credenciamento', 'producao', 'medium'],
            ], [
                ['Credenciamento', -60, 60],
                ['Abertura', 0, 20],
                ['Conteúdo', 20, 90],
                ['Intervalo', 110, 20],
                ['Encerramento', 180, 20],
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
            self::template('casamento', 'Casamento', EventType::Wedding, 'Cerimônia, festa e fornecedores da celebração.', [
                ['Confirmar local e data', 'producao', 'high'],
                ['Lista de convidados', 'producao', 'high'],
                ['Buffet', 'producao', 'high'],
                ['Música', 'artistas', 'medium'],
                ['Foto e vídeo', 'producao', 'medium'],
                ['Decoração', 'producao', 'medium'],
                ['Cronograma do dia', 'producao', 'high'],
            ], [
                ['Chegada dos fornecedores', -240, 120],
                ['Cerimônia', 0, 40],
                ['Recepção', 40, 50],
                ['Festa', 90, 180],
                ['Encerramento', 270, 30],
            ]),
            self::template('privado', 'Evento privado', EventType::PrivateEvent, 'Um evento fechado, com lista e operação curta.', [
                ['Definir o formato', 'producao', 'high'],
                ['Lista de convidados', 'producao', 'high'],
                ['Local', 'producao', 'high'],
                ['Comida e bebida', 'producao', 'medium'],
                ['Quem recebe na porta', 'producao', 'medium'],
            ], [
                ['Preparação', -120, 90],
                ['Chegada', 0, 45],
                ['Evento', 45, 150],
                ['Encerramento', 195, 30],
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
