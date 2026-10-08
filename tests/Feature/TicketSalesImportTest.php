<?php

namespace Tests\Feature;

use App\Domain\Events\CreateEvent;
use App\Domain\Tickets\ApplyTicketSalesImport;
use App\Domain\Tickets\ImportSoldTicketsFromShotgun;
use App\Enums\EventType;
use App\Livewire\Tickets\TicketBoard;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class TicketSalesImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_apply_import_creates_missing_tiers(): void
    {
        $user = User::factory()->create();
        $event = app(CreateEvent::class)->handle($user, [
            'name' => 'Festa import',
            'type' => EventType::Party,
        ]);

        $csv = file_get_contents(base_path('tests/fixtures/shotgun_valid_orders.csv'));
        $imports = app(ImportSoldTicketsFromShotgun::class)->parse($csv);

        $result = app(ApplyTicketSalesImport::class)->apply($event, $imports);

        $this->assertSame(15, $result->totalTickets);
        $this->assertSame(1, $result->tiersCreated);
        $this->assertSame(0, $result->tiersUpdated);

        $tier = $event->ticketTiers()->where('name', 'Keep Walking')->first();
        $this->assertNotNull($tier);
        $this->assertSame(15, $tier->sold_quantity);
        $this->assertSame(15, $tier->goal);
    }

    public function test_apply_import_updates_existing_tier_by_name(): void
    {
        $user = User::factory()->create();
        $event = app(CreateEvent::class)->handle($user, [
            'name' => 'Festa import',
            'type' => EventType::Party,
        ]);

        $event->ticketTiers()->create([
            'name' => 'keep walking',
            'price' => 5000,
            'quantity' => 100,
            'goal' => 50,
            'sold_quantity' => 2,
            'sort_order' => 1,
        ]);

        $csv = file_get_contents(base_path('tests/fixtures/shotgun_valid_orders.csv'));
        $imports = app(ImportSoldTicketsFromShotgun::class)->parse($csv);

        $result = app(ApplyTicketSalesImport::class)->apply($event, $imports);

        $this->assertSame(0, $result->tiersCreated);
        $this->assertSame(1, $result->tiersUpdated);

        $tier = $event->ticketTiers()->first();
        $this->assertSame(15, $tier->sold_quantity);
        $this->assertSame(5000, $tier->price);
        $this->assertSame(100, $tier->quantity);
    }

    public function test_ticket_board_imports_shotgun_csv_via_livewire(): void
    {
        $user = User::factory()->create();
        $event = app(CreateEvent::class)->handle($user, [
            'name' => 'Festa import',
            'type' => EventType::Party,
        ]);

        $csv = file_get_contents(base_path('tests/fixtures/shotgun_valid_orders.csv'));
        $file = UploadedFile::fake()->createWithContent('orders.csv', $csv, 'text/csv');

        Livewire::actingAs($user)
            ->test(TicketBoard::class, ['event' => $event])
            ->call('openImportModal')
            ->set('importPlatform', 'shotgun')
            ->set('importFile', $file)
            ->call('importSold')
            ->assertHasNoErrors();

        $this->assertSame(15, (int) $event->fresh()->ticketTiers()->sum('sold_quantity'));
    }
}
