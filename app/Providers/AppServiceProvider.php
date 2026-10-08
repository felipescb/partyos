<?php

namespace App\Providers;

use App\Models\BudgetItem;
use App\Models\EventFee;
use App\Models\Payment;
use App\Models\Revenue;
use App\Models\RevenueDistribution;
use App\Models\TicketTier;
use App\Models\User;
use App\Observers\FinancialAuditObserver;
use App\Observers\UserObserver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configurePartyOs();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    protected function configurePartyOs(): void
    {
        CarbonImmutable::setLocale((string) config('app.locale'));

        User::observe(UserObserver::class);

        $audit = FinancialAuditObserver::class;
        BudgetItem::observe($audit);
        Payment::observe($audit);
        Revenue::observe($audit);
        TicketTier::observe($audit);
        RevenueDistribution::observe($audit);
        EventFee::observe($audit);
    }
}
