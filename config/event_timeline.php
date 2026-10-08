<?php

return [

    /*
    | Marcos padrão por semana antes da semana da festa (0 = semana da execução).
    | Ajuste aqui para auto-configurar o grid de timeline por evento.
    */
    'milestones' => [
        ['weeks_before_event' => 12, 'title' => 'Kickoff de produção', 'slug' => 'kickoff'],
        ['weeks_before_event' => 8, 'title' => 'Venue e lineup', 'slug' => 'venue_lineup'],
        ['weeks_before_event' => 6, 'title' => 'Orçamento fechado', 'slug' => 'budget'],
        ['weeks_before_event' => 4, 'title' => 'Ingressos no ar', 'slug' => 'tickets'],
        ['weeks_before_event' => 2, 'title' => 'Divulgação intensiva', 'slug' => 'promo'],
        ['weeks_before_event' => 1, 'title' => 'Semana D-1', 'slug' => 'd_minus_1'],
        ['weeks_before_event' => 0, 'title' => 'Semana da festa', 'slug' => 'event_week'],
    ],

    /** Semanas de planejamento quando só a data da festa está definida. */
    'default_planning_weeks' => 12,

];
