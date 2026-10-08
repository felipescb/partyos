<?php

use App\Livewire\Artists\ArtistDirectory;
use App\Livewire\Artists\LineupBoard;
use App\Livewire\Events\CloseBoard;
use App\Livewire\Events\EventForm;
use App\Livewire\Events\EventOverview;
use App\Livewire\Events\ScheduleBoard;
use App\Livewire\Events\TeamBoard;
use App\Livewire\Finance\BudgetBoard;
use App\Livewire\Finance\CashflowBoard;
use App\Livewire\Finance\DistributionBoard;
use App\Livewire\Finance\RevenueBoard;
use App\Livewire\Finance\ScenarioBoard;
use App\Livewire\Guests\GuestBoard;
use App\Livewire\Portfolio;
use App\Livewire\Tasks\TaskBoard;
use App\Livewire\Tickets\TicketBoard;
use App\Livewire\Vendors\VendorDirectory;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

Route::get('/', [AuthenticatedSessionController::class, 'create'])
    ->middleware('guest')
    ->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', Portfolio::class)->name('dashboard');
    Route::get('eventos/novo', EventForm::class)->name('events.create');
    Route::get('fornecedores', VendorDirectory::class)->name('vendors.index');
    Route::get('artistas', ArtistDirectory::class)->name('artists.index');

    Route::prefix('eventos/{event}')->group(function () {
        Route::get('/', EventOverview::class)->name('events.show');
        Route::get('editar', EventForm::class)->name('events.edit');
        Route::get('custos', BudgetBoard::class)->name('events.costs');
        Route::get('receitas', RevenueBoard::class)->name('events.revenues');
        Route::get('fluxo', CashflowBoard::class)->name('events.cashflow');
        Route::get('cenarios', ScenarioBoard::class)->name('events.scenarios');
        Route::get('distribuicao', DistributionBoard::class)->name('events.distribution');
        Route::get('ingressos', TicketBoard::class)->name('events.tickets');
        Route::get('convidados', GuestBoard::class)->name('events.guests');
        Route::get('artistas', LineupBoard::class)->name('events.artists');
        Route::get('tarefas', TaskBoard::class)->name('events.tasks');
        Route::get('cronograma', ScheduleBoard::class)->name('events.schedule');
        Route::get('equipe', TeamBoard::class)->name('events.team');
        Route::get('fechamento', CloseBoard::class)->name('events.close');
    });
});

require __DIR__.'/settings.php';
