<?php

namespace App\Domain\Guests;

use App\Enums\GuestCategory;
use App\Enums\RsvpStatus;

readonly class GuestListRow
{
    public function __construct(
        public string $name,
        public ?string $phone,
        public ?string $email,
        public ?string $instagram,
        public ?GuestCategory $category,
        public RsvpStatus $rsvp,
        public int $plusOnes,
        public ?string $notes,
    ) {}
}
