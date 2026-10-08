<?php

namespace App\Domain\Events;

use App\Enums\CostStatus;
use App\Enums\EventRole;
use App\Enums\EventStatus;
use App\Enums\RsvpStatus;
use App\Enums\TaskStatus;
use App\Models\Booking;
use App\Models\BudgetItem;
use App\Models\Event;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class DuplicateSelection
{
    public function __construct(
        public bool $budget = true,
        public bool $artists = true,
        public bool $tasks = true,
        public bool $schedule = true,
        public bool $guests = false,
        public bool $tickets = true,
    ) {}
}

final class DuplicateEvent
{
    public function __construct(private CreateEvent $creator) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(User $user, Event $source, array $attributes, DuplicateSelection $selection): Event
    {
        return DB::transaction(function () use ($user, $source, $attributes, $selection): Event {
            $source->load([
                'budgetItems.payments',
                'bookings',
                'tasks',
                'scheduleItems',
                'guests',
                'ticketTiers',
            ]);

            $copy = $this->creator->handle($user, [
                'name' => $attributes['name'],
                'description' => $source->description,
                'type' => $source->type,
                'status' => EventStatus::Draft,
                'starts_at' => $attributes['starts_at'] ?? null,
                'ends_at' => $attributes['ends_at'] ?? null,
                'venue_name' => $source->venue_name,
                'address' => $source->address,
                'city' => $source->city,
                'capacity' => $source->capacity,
                'currency' => $source->currency,
                'notes' => $source->notes,
                'template_id' => null,
            ]);

            $bookingItemIds = $source->bookings->pluck('budget_item_id')->filter()->all();

            if ($selection->artists) {
                foreach ($source->bookings as $booking) {
                    $this->copyBooking($copy, $booking, $source->starts_at, $copy->starts_at);
                }
            }

            if ($selection->budget) {
                foreach ($source->budgetItems as $item) {
                    if ($selection->artists && in_array($item->id, $bookingItemIds, true)) {
                        continue;
                    }

                    $this->copyCost($copy, $item, $source->starts_at, $copy->starts_at);
                }
            }

            if ($selection->tasks) {
                foreach ($source->tasks as $task) {
                    $copy->tasks()->create([
                        'title' => $task->title,
                        'description' => $task->description,
                        'assignee_id' => $task->assignee_id,
                        'due_on' => $this->shiftDate($task->due_on, $source->starts_at, $copy->starts_at),
                        'status' => TaskStatus::Backlog,
                        'priority' => $task->priority,
                        'category' => $task->category,
                    ]);
                }
            }

            if ($selection->schedule) {
                foreach ($source->scheduleItems as $item) {
                    $copy->scheduleItems()->create([
                        'title' => $item->title,
                        'starts_at' => $this->shiftDateTime($item->starts_at, $source->starts_at, $copy->starts_at),
                        'duration_minutes' => $item->duration_minutes,
                        'assignee_id' => $item->assignee_id,
                        'vendor_id' => $item->vendor_id,
                        'location' => $item->location,
                        'notes' => $item->notes,
                        'sort_order' => $item->sort_order,
                    ]);
                }
            }

            if ($selection->guests) {
                foreach ($source->guests as $guest) {
                    $copy->guests()->create([
                        'name' => $guest->name,
                        'phone' => $guest->phone,
                        'email' => $guest->email,
                        'instagram' => $guest->instagram,
                        'category' => $guest->category,
                        'rsvp_status' => RsvpStatus::NotSent,
                        'plus_ones' => $guest->plus_ones,
                        'notes' => $guest->notes,
                    ]);
                }
            }

            if ($selection->tickets) {
                foreach ($source->ticketTiers as $tier) {
                    $copy->ticketTiers()->create([
                        'name' => $tier->name,
                        'price' => $tier->price,
                        'quantity' => $tier->quantity,
                        'goal' => $tier->goal,
                        'starts_at' => $this->shiftDateTime($tier->starts_at, $source->starts_at, $copy->starts_at),
                        'ends_at' => $this->shiftDateTime($tier->ends_at, $source->starts_at, $copy->starts_at),
                        'sold_quantity' => 0,
                        'sort_order' => $tier->sort_order,
                    ]);
                }
            }

            if (! $copy->members()->where('user_id', $user->id)->exists()) {
                $copy->members()->attach($user->id, ['role' => EventRole::Owner->value]);
            }

            return $copy;
        });
    }

    private function copyCost(Event $copy, BudgetItem $item, ?CarbonInterface $from, ?CarbonInterface $to): void
    {
        $status = $item->status;

        if (in_array($status, [CostStatus::Paid, CostStatus::PartiallyPaid], true)) {
            $status = CostStatus::Contracted;
        }

        $copy->budgetItems()->create([
            'budget_id' => $copy->budget?->id,
            'cost_category_id' => $item->cost_category_id,
            'vendor_id' => $item->vendor_id,
            'description' => $item->description,
            'estimated_amount' => $item->estimated_amount,
            'contracted_amount' => $item->contracted_amount,
            'due_on' => $this->shiftDate($item->due_on, $from, $to),
            'status' => $status,
            'payment_method' => $item->payment_method,
            'notes' => $item->notes,
        ]);
    }

    private function copyBooking(Event $copy, Booking $booking, ?CarbonInterface $from, ?CarbonInterface $to): void
    {
        $sourceItem = $booking->budgetItem;
        $item = null;

        if ($sourceItem instanceof BudgetItem && $sourceItem->status !== CostStatus::Cancelled) {
            $item = $copy->budgetItems()->create([
                'budget_id' => $copy->budget?->id,
                'cost_category_id' => $sourceItem->cost_category_id,
                'description' => $sourceItem->description,
                'estimated_amount' => $sourceItem->estimated_amount,
                'contracted_amount' => $sourceItem->contracted_amount,
                'due_on' => $this->shiftDate($sourceItem->due_on, $from, $to),
                'status' => in_array($sourceItem->status, [CostStatus::Paid, CostStatus::PartiallyPaid], true)
                    ? CostStatus::Contracted
                    : $sourceItem->status,
                'payment_method' => $sourceItem->payment_method,
                'notes' => $sourceItem->notes,
            ]);
        }

        $copy->bookings()->create([
            'artist_id' => $booking->artist_id,
            'budget_item_id' => $item?->id,
            'starts_at' => $this->shiftDateTime($booking->starts_at, $from, $to),
            'ends_at' => $this->shiftDateTime($booking->ends_at, $from, $to),
            'status' => $booking->status,
            'notes' => $booking->notes,
        ]);
    }

    private function shiftDateTime(?CarbonInterface $value, ?CarbonInterface $from, ?CarbonInterface $to): ?CarbonInterface
    {
        if ($value === null) {
            return null;
        }

        if ($from === null || $to === null) {
            return $value;
        }

        return $to->addSeconds($from->diffInSeconds($value, false));
    }

    private function shiftDate(mixed $value, ?CarbonInterface $from, ?CarbonInterface $to): mixed
    {
        if ($value === null || $from === null || $to === null) {
            return $value;
        }

        $date = $value instanceof CarbonInterface ? $value : Carbon::parse($value);

        return $to->addSeconds($from->diffInSeconds($date, false))->toDateString();
    }
}
