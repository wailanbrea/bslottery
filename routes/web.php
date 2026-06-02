<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\AccountingController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BankTransferController;
use App\Http\Controllers\Admin\BetTypeController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\CashController;
use App\Http\Controllers\Admin\CashFundingTransferController;
use App\Http\Controllers\Admin\CashIncidentController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\DeviceController;
use App\Http\Controllers\Admin\DrawController;
use App\Http\Controllers\Admin\LimitRuleController;
use App\Http\Controllers\Admin\LotteryController;
use App\Http\Controllers\Admin\MonitoringController;
use App\Http\Controllers\Admin\PayoutRuleController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PrinterController;
use App\Http\Controllers\Admin\PrizeController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ResultController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SystemNotificationController;
use App\Http\Controllers\Admin\TicketController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\EmployeeAdvanceController;
use App\Http\Controllers\Admin\EmployeeLoanController;
use App\Http\Controllers\Admin\PayrollController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InitialSetupController;
use App\Http\Controllers\LicenseActivationController;
use App\Http\Controllers\TicketPublicLookupController;
use App\Http\Controllers\Api\PrintJobController;
use Illuminate\Support\Facades\Route;

Route::get('/license/activate', [LicenseActivationController::class, 'create'])->name('license.activate');
Route::post('/license/activate', [LicenseActivationController::class, 'store'])->name('license.activate.store');
Route::get('/license/blocked', [LicenseActivationController::class, 'blocked'])->name('license.blocked');
Route::post('/license/revalidate', [LicenseActivationController::class, 'revalidate'])->name('license.revalidate');

Route::get('/setup/initial', [InitialSetupController::class, 'create'])->name('setup.initial');
Route::post('/setup/initial', [InitialSetupController::class, 'store'])->name('setup.initial.store');

Route::get('/login', [AuthController::class, 'create'])->middleware('guest')->name('login');
Route::post('/login', [AuthController::class, 'store'])->middleware('guest')->name('login.store');
Route::get('/password/change', [AuthController::class, 'editPassword'])->middleware('auth')->name('password.edit');
Route::put('/password/change', [AuthController::class, 'updatePassword'])->middleware('auth')->name('password.update');
Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');

Route::get('/', DashboardController::class)->middleware(['auth', 'active.context'])->name('dashboard');

// Consulta publica de ticket por UUID (sin auth). El QR del ticket apunta aqui.
Route::get('/t/{uuid}', [TicketPublicLookupController::class, 'show'])
    ->where('uuid', '[0-9a-fA-F-]{36}')
    ->name('ticket.public');

Route::middleware(['auth', 'active.context'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::resource('companies', CompanyController::class)
            ->except(['show', 'destroy'])
            ->middlewareFor('index', 'permission:companies.view')
            ->middlewareFor(['create', 'store'], 'permission:companies.create')
            ->middlewareFor(['edit', 'update'], 'permission:companies.update');

        Route::resource('branches', BranchController::class)
            ->except(['show', 'destroy'])
            ->middlewareFor('index', 'permission:branches.view')
            ->middlewareFor(['create', 'store'], 'permission:branches.create')
            ->middlewareFor(['edit', 'update'], 'permission:branches.update');

        Route::resource('users', UserController::class)
            ->except(['show', 'destroy'])
            ->middlewareFor('index', 'permission:users.view')
            ->middlewareFor(['create', 'store'], 'permission:users.create')
            ->middlewareFor(['edit', 'update'], 'permission:users.update');

        Route::get('roles', [RoleController::class, 'index'])
            ->middleware('permission:roles.view')
            ->name('roles.index');
        Route::get('roles/{role}/permissions', [RoleController::class, 'edit'])
            ->middleware('permission:roles.assign_permissions')
            ->name('roles.permissions.edit');
        Route::put('roles/{role}/permissions', [RoleController::class, 'update'])
            ->middleware('permission:roles.assign_permissions')
            ->name('roles.permissions.update');

        Route::get('devices', [DeviceController::class, 'index'])
            ->middleware('permission:devices.view')
            ->name('devices.index');
        Route::post('devices/{device}/authorize', [DeviceController::class, 'authorizeDevice'])
            ->middleware('permission:devices.authorize')
            ->name('devices.authorize');
        Route::post('devices/{device}/block', [DeviceController::class, 'block'])
            ->middleware('permission:devices.block')
            ->name('devices.block');

        Route::get('audit-logs', [AuditLogController::class, 'index'])
            ->middleware('permission:audit.view')
            ->name('audit.index');
        Route::get('audit-logs/{auditLog}', [AuditLogController::class, 'show'])
            ->middleware('permission:audit.view')
            ->name('audit.show');

        Route::get('monitoring', [MonitoringController::class, 'index'])
            ->middleware('permission:monitoring.view')
            ->name('monitoring.index');
        Route::get('monitoring/settings', [MonitoringController::class, 'settings'])
            ->middleware('permission:monitoring.configure')
            ->name('monitoring.settings');
        Route::put('monitoring/settings', [MonitoringController::class, 'updateSettings'])
            ->middleware('permission:monitoring.configure')
            ->name('monitoring.settings.update');
        Route::get('notifications', [SystemNotificationController::class, 'index'])
            ->middleware('permission:notifications.view')
            ->name('notifications.index');
        Route::post('notifications/{notification}/read', [SystemNotificationController::class, 'markRead'])
            ->middleware('permission:notifications.manage')
            ->name('notifications.read');

        // Fase 2 â€” CatÃ¡logos de loterÃ­a
        Route::post('lotteries/bulk-status', [LotteryController::class, 'bulkUpdateStatus'])
            ->middleware('permission:lotteries.toggle')
            ->name('lotteries.bulk-status');
        Route::post('lotteries/{lottery}/status', [LotteryController::class, 'updateStatus'])
            ->middleware('permission:lotteries.toggle')
            ->name('lotteries.status');
        Route::resource('lotteries', LotteryController::class)
            ->except(['show', 'destroy'])
            ->middlewareFor('index', 'permission:lotteries.view')
            ->middlewareFor(['create', 'store'], 'permission:lotteries.create')
            ->middlewareFor(['edit', 'update'], 'permission:lotteries.update');

        Route::resource('draws', DrawController::class)
            ->except(['show', 'destroy'])
            ->middlewareFor('index', 'permission:draws.view')
            ->middlewareFor(['create', 'store'], 'permission:draws.create')
            ->middlewareFor(['edit', 'update'], 'permission:draws.update');
        Route::post('draws/open-time', [DrawController::class, 'updateOpenTimeForCompany'])
            ->middleware('permission:draws.update')
            ->name('draws.open-time.update');
        Route::post('draws/{draw}/close', [DrawController::class, 'close'])
            ->middleware('permission:draws.close')
            ->name('draws.close');
        Route::post('draws/{draw}/cancel', [DrawController::class, 'cancel'])
            ->middleware('permission:draws.close')
            ->name('draws.cancel');

        Route::resource('bet-types', BetTypeController::class)
            ->except(['show', 'destroy'])
            ->middlewareFor('index', 'permission:lotteries.view')
            ->middlewareFor(['create', 'store'], 'permission:lotteries.create')
            ->middlewareFor(['edit', 'update'], 'permission:lotteries.update');

        Route::resource('payout-rules', PayoutRuleController::class)
            ->except(['show', 'destroy'])
            ->middlewareFor('index', 'permission:payout_rules.view')
            ->middlewareFor(['create', 'store'], 'permission:payout_rules.create')
            ->middlewareFor(['edit', 'update'], 'permission:payout_rules.update');
        Route::post('payout-rules/{rule}/approve', [PayoutRuleController::class, 'approve'])
            ->middleware('permission:payout_rules.approve')
            ->name('payout-rules.approve');
        Route::post('payout-rules/copy-branch', [PayoutRuleController::class, 'copyBranch'])
            ->middleware('permission:payout_rules.create')
            ->name('payout-rules.copy-branch');

        Route::resource('limit-rules', LimitRuleController::class)
            ->except(['show', 'destroy'])
            ->middlewareFor('index', 'permission:limit_rules.view')
            ->middlewareFor(['create', 'store'], 'permission:limit_rules.create')
            ->middlewareFor(['edit', 'update'], 'permission:limit_rules.update');
        Route::get('limit-consumptions', [LimitRuleController::class, 'consumptions'])
            ->middleware('permission:limit_rules.view')
            ->name('limit-rules.consumptions');
        Route::post('limit-rules/copy-branch', [LimitRuleController::class, 'copyBranch'])
            ->middleware('permission:limit_rules.create')
            ->name('limit-rules.copy-branch');

        // Fase 3 â€” Caja
        Route::get('cash', [CashController::class, 'index'])
            ->middleware('permission:cash.view')
            ->name('cash.index');
        Route::get('cash/current', [CashController::class, 'current'])
            ->middleware('permission:cash.view')
            ->name('cash.current');
        Route::get('cash/open', [CashController::class, 'openForm'])
            ->middleware('permission:cash.open')
            ->name('cash.open');
        Route::post('cash/open', [CashController::class, 'open'])
            ->middleware('permission:cash.open')
            ->name('cash.open.store');
        Route::get('cash/movement', [CashController::class, 'movementForm'])
            ->middleware('permission:cash.movement')
            ->name('cash.movement');
        Route::post('cash/movement', [CashController::class, 'recordMovement'])
            ->middleware('permission:cash.movement')
            ->name('cash.movement.store');
        Route::get('cash/close', [CashController::class, 'closeForm'])
            ->middleware('permission:cash.close')
            ->name('cash.close');
        Route::post('cash/close', [CashController::class, 'close'])
            ->middleware('permission:cash.close')
            ->name('cash.close.store');
        Route::get('cash/transfers', [BankTransferController::class, 'index'])
            ->middleware('permission:cash.transfers.view')
            ->name('cash.transfers.index');
        Route::get('cash/transfers/create', [BankTransferController::class, 'create'])
            ->middleware('permission:cash.transfers.create')
            ->name('cash.transfers.create');
        Route::post('cash/transfers', [BankTransferController::class, 'store'])
            ->middleware('permission:cash.transfers.create')
            ->name('cash.transfers.store');
        Route::post('cash/transfers/{transfer}/confirm', [BankTransferController::class, 'confirm'])
            ->middleware('permission:cash.transfers.verify')
            ->name('cash.transfers.confirm');
        Route::post('cash/transfers/{transfer}/reject', [BankTransferController::class, 'reject'])
            ->middleware('permission:cash.transfers.verify')
            ->name('cash.transfers.reject');
        Route::get('cash/funding', [CashFundingTransferController::class, 'index'])
            ->middleware('permission:cash.funding.view')
            ->name('cash.funding.index');
        Route::get('cash/funding/create', [CashFundingTransferController::class, 'create'])
            ->middleware('permission:cash.funding.create')
            ->name('cash.funding.create');
        Route::post('cash/funding', [CashFundingTransferController::class, 'store'])
            ->middleware('permission:cash.funding.create')
            ->name('cash.funding.store');
        Route::get('cash/incidents', [CashIncidentController::class, 'index'])
            ->middleware('permission:cash.incidents.view')
            ->name('cash.incidents.index');
        Route::post('cash/incidents/{incident}/resolve', [CashIncidentController::class, 'resolve'])
            ->middleware('permission:cash.incidents.resolve')
            ->name('cash.incidents.resolve');
        Route::post('cash/incidents/{incident}/dismiss', [CashIncidentController::class, 'dismiss'])
            ->middleware('permission:cash.incidents.resolve')
            ->name('cash.incidents.dismiss');

        // Fase 3 â€” Contabilidad
        Route::get('cash/{session}', [CashController::class, 'show'])
            ->middleware('permission:cash.view')
            ->name('cash.show');
        Route::post('cash/{session}/confirm', [CashController::class, 'confirm'])
            ->middleware('permission:cash.confirm')
            ->name('cash.confirm');
        Route::post('cash/{session}/reopen', [CashController::class, 'reopen'])
            ->middleware('permission:cash.reopen')
            ->name('cash.reopen');
        Route::get('accounting/accounts', [AccountingController::class, 'accounts'])
            ->middleware('permission:accounting.view')
            ->name('accounting.accounts');
        Route::get('accounting/journal', [AccountingController::class, 'journal'])
            ->middleware('permission:accounting.view')
            ->name('accounting.journal');
        Route::get('accounting/journal/{entry}', [AccountingController::class, 'showEntry'])
            ->middleware('permission:accounting.view')
            ->name('accounting.entry');

        // Fase 4 â€” Ventas
        Route::get('tickets', [TicketController::class, 'index'])
            ->middleware('permission:tickets.view')
            ->name('tickets.index');
        Route::get('tickets/create', [TicketController::class, 'create'])
            ->middleware('permission:sales.create')
            ->name('tickets.create');
        Route::post('tickets/preview', [TicketController::class, 'preview'])
            ->middleware('permission:sales.preview')
            ->name('tickets.preview');
        Route::post('tickets', [TicketController::class, 'store'])
            ->middleware('permission:sales.create')
            ->name('tickets.store');
        Route::get('tickets/lookup/by-token', [TicketController::class, 'lookup'])
            ->middleware('permission:tickets.view')
            ->name('tickets.lookup');
        Route::get('tickets/check-limit', [TicketController::class, 'checkLimit'])
            ->middleware('permission:sales.create')
            ->name('tickets.check-limit');
        Route::get('tickets/{ticket}', [TicketController::class, 'show'])
            ->middleware('permission:tickets.view')
            ->name('tickets.show');
        Route::post('tickets/{ticket}/cancel', [TicketController::class, 'cancel'])
            ->middleware('permission:sales.cancel')
            ->name('tickets.cancel');
        Route::post('tickets/{ticket}/reprint', [TicketController::class, 'reprint'])
            ->middleware('permission:sales.reprint')
            ->name('tickets.reprint');

        // Fase 5 â€” Resultados y premios
        Route::get('results', [ResultController::class, 'index'])
            ->middleware('permission:results.view')
            ->name('results.index');
        Route::get('results/create', [ResultController::class, 'create'])
            ->middleware('permission:results.create')
            ->name('results.create');
        Route::post('results', [ResultController::class, 'store'])
            ->middleware('permission:results.create')
            ->name('results.store');
        Route::post('results/{result}/confirm', [ResultController::class, 'confirm'])
            ->middleware('permission:results.confirm')
            ->name('results.confirm');
        Route::post('draws/{draw}/calculate-winners', [ResultController::class, 'calculateWinners'])
            ->middleware('permission:winners.calculate')
            ->name('results.calculate');
        Route::post('draws/{draw}/authorize-payments', [ResultController::class, 'authorizePayments'])
            ->middleware('permission:payments.authorize')
            ->name('results.authorize');

        Route::get('prizes/pending', [PrizeController::class, 'pending'])
            ->middleware('permission:prizes.pay')
            ->name('prizes.pending');
        Route::post('prizes/{winner}/pay', [PrizeController::class, 'pay'])
            ->middleware('permission:prizes.pay')
            ->name('prizes.pay');
        Route::post('tickets/{ticket}/pay-released-prizes', [PrizeController::class, 'payTicket'])
            ->middleware('permission:prizes.pay')
            ->name('tickets.pay-released-prizes');
        Route::get('prizes/history', [PrizeController::class, 'history'])
            ->middleware('permission:prizes.pay')
            ->name('prizes.history');

        // Impresoras
        Route::get('printers/connector/install-script', [PrinterController::class, 'downloadConnectorInstallScript'])
            ->middleware('permission:printers.configure')
            ->name('printers.connector.script');
        Route::get('printers/queue', [PrinterController::class, 'queue'])
            ->middleware('permission:printers.view')
            ->name('printers.queue');
        Route::post('printers/queue/clear', [PrinterController::class, 'clearQueue'])
            ->middleware('permission:printers.configure')
            ->name('printers.queue.clear');
        Route::post('printers/queue/purge', [PrinterController::class, 'purgeQueue'])
            ->middleware('permission:printers.configure')
            ->name('printers.queue.purge');
        Route::post('printers/queue/{job}/retry', [PrinterController::class, 'retryJob'])
            ->middleware('permission:printers.configure')
            ->name('printers.queue.retry');
        Route::resource('printers', PrinterController::class)->except(['show', 'destroy'])
            ->middlewareFor('index', 'permission:printers.view')
            ->middlewareFor(['create', 'store'], 'permission:printers.configure')
            ->middlewareFor(['edit', 'update'], 'permission:printers.configure');
        Route::post('printers/{printer}/test', [PrinterController::class, 'test'])
            ->middleware('permission:printers.test')
            ->name('printers.test');

        // Configuracion
        Route::get('settings/results', [SettingsController::class, 'results'])
            ->middleware('permission:settings.view')
            ->name('settings.results');
        Route::put('settings/results', [SettingsController::class, 'updateResults'])
            ->middleware('permission:settings.update')
            ->name('settings.results.update');

        // Fase 8 â€” Reportes
        Route::get('reports', [ReportController::class, 'index'])
            ->middleware('permission:reports.view')
            ->name('reports.index');
        Route::get('reports/sales-by-day', [ReportController::class, 'salesByDay'])
            ->middleware('permission:reports.view')
            ->name('reports.sales-by-day');
        Route::get('reports/sales-by-branch', [ReportController::class, 'salesByBranch'])
            ->middleware('permission:reports.view')
            ->name('reports.sales-by-branch');
        Route::get('reports/sales-by-lottery', [ReportController::class, 'salesByLottery'])
            ->middleware('permission:reports.view')
            ->name('reports.sales-by-lottery');
        Route::get('reports/top-numbers', [ReportController::class, 'topNumbers'])
            ->middleware('permission:reports.view')
            ->name('reports.top-numbers');
        Route::get('reports/cancelled', [ReportController::class, 'cancelledTickets'])
            ->middleware('permission:reports.view')
            ->name('reports.cancelled');
        Route::get('reports/reprinted', [ReportController::class, 'reprintedTickets'])
            ->middleware('permission:reports.view')
            ->name('reports.reprinted');
        Route::get('reports/cash-summary', [ReportController::class, 'cashSummary'])
            ->middleware('permission:reports.view')
            ->name('reports.cash-summary');
        Route::get('reports/winners', [ReportController::class, 'winners'])
            ->middleware('permission:reports.view')
            ->name('reports.winners');
        Route::get('reports/prizes-paid', [ReportController::class, 'prizesPaid'])
            ->middleware('permission:reports.view')
            ->name('reports.prizes-paid');
        Route::get('reports/numbers-near-limit', [ReportController::class, 'numbersNearLimit'])
            ->middleware('permission:reports.view')
            ->name('reports.numbers-near-limit');
        Route::get('reports/income-statement', [ReportController::class, 'incomeStatement'])
            ->middleware('permission:reports.view')
            ->name('reports.income-statement');
        Route::get('reports/payroll', [ReportController::class, 'payrollReport'])
            ->middleware('permission:reports.view')
            ->name('reports.payroll');
        Route::get('reports/cash-movements', [ReportController::class, 'cashMovements'])
            ->middleware('permission:reports.view')
            ->name('reports.cash-movements');
        Route::get('reports/expenses-by-branch', [ReportController::class, 'expensesByBranch'])
            ->middleware('permission:reports.view')
            ->name('reports.expenses-by-branch');
        Route::get('reports/cash-in-out', [ReportController::class, 'cashInOut'])
            ->middleware('permission:reports.view')
            ->name('reports.cash-in-out');
        Route::get('reports/payroll-advances', [ReportController::class, 'payrollAdvances'])
            ->middleware('permission:reports.view')
            ->name('reports.payroll-advances');
        Route::get('reports/payroll-loans', [ReportController::class, 'payrollLoans'])
            ->middleware('permission:reports.view')
            ->name('reports.payroll-loans');
        Route::get('reports/payroll-commissions', [ReportController::class, 'payrollCommissions'])
            ->middleware('permission:reports.view')
            ->name('reports.payroll-commissions');
        Route::get('reports/payroll-payments', [ReportController::class, 'payrollPayments'])
            ->middleware('permission:reports.view')
            ->name('reports.payroll-payments');
        Route::get('reports/payroll-deductions', [ReportController::class, 'payrollDeductions'])
            ->middleware('permission:reports.view')
            ->name('reports.payroll-deductions');
        Route::get('reports/payroll-shortages', [ReportController::class, 'payrollShortages'])
            ->middleware('permission:reports.view')
            ->name('reports.payroll-shortages');
        Route::get('reports/income-vs-expenses', [ReportController::class, 'incomeVsExpenses'])
            ->middleware('permission:reports.view')
            ->name('reports.income-vs-expenses');
        Route::get('reports/profit-by-branch', [ReportController::class, 'profitByBranch'])
            ->middleware('permission:reports.view')
            ->name('reports.profit-by-branch');
        Route::get('reports/profit-by-company', [ReportController::class, 'profitByCompany'])
            ->middleware('permission:reports.view')
            ->name('reports.profit-by-company');
        Route::get('reports/accounts-receivable', [ReportController::class, 'accountsReceivable'])
            ->middleware('permission:reports.view')
            ->name('reports.accounts-receivable');
        Route::get('reports/accounts-payable', [ReportController::class, 'accountsPayable'])
            ->middleware('permission:reports.view')
            ->name('reports.accounts-payable');
        Route::get('reports/cash-flow', [ReportController::class, 'cashFlow'])
            ->middleware('permission:reports.view')
            ->name('reports.cash-flow');

        // Fase 9 â€” NÃ³mina
        Route::resource('employees', EmployeeController::class)
            ->except(['show', 'destroy'])
            ->middlewareFor('index', 'permission:payroll.view')
            ->middlewareFor(['create', 'store'], 'permission:payroll.manage')
            ->middlewareFor(['edit', 'update'], 'permission:payroll.manage');

        Route::get('employees/advances', [EmployeeAdvanceController::class, 'index'])
            ->middleware('permission:payroll.view')
            ->name('employees.advances');
        Route::post('employees/advances', [EmployeeAdvanceController::class, 'store'])
            ->middleware('permission:payroll.manage')
            ->name('employees.advances.store');
        Route::patch('employees/advances/{advance}/approve', [EmployeeAdvanceController::class, 'approve'])
            ->middleware('permission:payroll.approve')
            ->name('employees.advances.approve');

        Route::get('employees/loans', [EmployeeLoanController::class, 'index'])
            ->middleware('permission:payroll.view')
            ->name('employees.loans');
        Route::post('employees/loans', [EmployeeLoanController::class, 'store'])
            ->middleware('permission:payroll.manage')
            ->name('employees.loans.store');
        Route::patch('employees/loans/{loan}/cancel', [EmployeeLoanController::class, 'cancel'])
            ->middleware('permission:payroll.manage')
            ->name('employees.loans.cancel');

        Route::get('payroll', [PayrollController::class, 'index'])
            ->middleware('permission:payroll.view')
            ->name('payroll.index');
        Route::get('payroll/{payrollPeriod}', [PayrollController::class, 'show'])
            ->middleware('permission:payroll.view')
            ->name('payroll.show');
        Route::post('payroll/generate', [PayrollController::class, 'generate'])
            ->middleware('permission:payroll.manage')
            ->name('payroll.generate');
        Route::patch('payroll/{payrollPeriod}/approve', [PayrollController::class, 'approve'])
            ->middleware('permission:payroll.approve')
            ->name('payroll.approve');
        Route::patch('payroll/{payrollPeriod}/pay', [PayrollController::class, 'pay'])
            ->middleware('permission:payroll.approve')
            ->name('payroll.pay');
        Route::patch('payroll/{payrollPeriod}/details/{payrollDetail}', [PayrollController::class, 'updateDetail'])
            ->middleware('permission:payroll.manage')
            ->name('payroll.detail.update');
    });
