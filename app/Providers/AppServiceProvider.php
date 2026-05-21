<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\BetType;
use App\Models\Branch;
use App\Models\CashSession;
use App\Models\Company;
use App\Models\Device;
use App\Models\Draw;
use App\Models\JournalEntry;
use App\Models\LimitRule;
use App\Models\Lottery;
use App\Models\PayoutRule;
use App\Models\PrinterConfig;
use App\Models\PrizePayment;
use App\Models\Result;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use App\Models\WinnerTicket;
use App\Policies\AuditLogPolicy;
use App\Policies\BetTypePolicy;
use App\Policies\BranchPolicy;
use App\Policies\CashSessionPolicy;
use App\Policies\CompanyPolicy;
use App\Policies\DevicePolicy;
use App\Policies\DrawPolicy;
use App\Policies\JournalEntryPolicy;
use App\Policies\LimitRulePolicy;
use App\Policies\LotteryPolicy;
use App\Policies\PayoutRulePolicy;
use App\Policies\PrinterConfigPolicy;
use App\Policies\ResultPolicy;
use App\Policies\RolePolicy;
use App\Policies\TicketPolicy;
use App\Policies\UserPolicy;
use App\Policies\WinnerTicketPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        Gate::policy(Company::class, CompanyPolicy::class);
        Gate::policy(Branch::class, BranchPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Device::class, DevicePolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
        Gate::policy(Lottery::class, LotteryPolicy::class);
        Gate::policy(Draw::class, DrawPolicy::class);
        Gate::policy(BetType::class, BetTypePolicy::class);
        Gate::policy(PayoutRule::class, PayoutRulePolicy::class);
        Gate::policy(LimitRule::class, LimitRulePolicy::class);
        Gate::policy(CashSession::class, CashSessionPolicy::class);
        Gate::policy(JournalEntry::class, JournalEntryPolicy::class);
        Gate::policy(Ticket::class, TicketPolicy::class);
        Gate::policy(Result::class, ResultPolicy::class);
        Gate::policy(WinnerTicket::class, WinnerTicketPolicy::class);
        Gate::policy(PrinterConfig::class, PrinterConfigPolicy::class);

        Gate::before(function (User $user): ?bool {
            return $user->isSuperAdmin() ? true : null;
        });
    }
}
