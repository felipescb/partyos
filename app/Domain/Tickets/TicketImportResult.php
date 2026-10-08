<?php

namespace App\Domain\Tickets;

readonly class TicketImportResult
{
    /**
     * @param  list<string>  $tierNamesCreated
     * @param  list<string>  $tierNamesUpdated
     */
    public function __construct(
        public int $totalTickets,
        public int $tiersCreated,
        public int $tiersUpdated,
        public array $tierNamesCreated,
        public array $tierNamesUpdated,
    ) {}
}
