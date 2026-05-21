<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exports\ReportExport;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\Company;
use App\Models\AccountingAccount;
use App\Models\CashIncident;
use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeLoan;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\PayrollDetail;
use App\Models\PayrollPeriod;
use App\Models\PrintJob;
use App\Models\Draw;
use App\Models\LimitConsumption;
use App\Models\LimitRule;
use App\Models\Lottery;
use App\Models\PrizePayment;
use App\Models\Ticket;
use App\Models\TicketDetail;
use App\Models\User;
use App\Models\WinnerTicket;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        $companyId = $filters['company_id'];
        $branchId = $filters['branch_id'];

        $ticketBase = Ticket::query()
            ->where('company_id', $companyId)
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
            ->whereBetween('sold_at', [$filters['from'], $filters['to']]);

        $winnerBase = WinnerTicket::query()
            ->where('company_id', $companyId)
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
            ->whereBetween('created_at', [$filters['from'], $filters['to']]);

        $paymentBase = PrizePayment::query()
            ->where('company_id', $companyId)
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
            ->whereBetween('paid_at', [$filters['from'], $filters['to']]);

        $cashBase = CashSession::query()
            ->where('company_id', $companyId)
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
            ->whereBetween('opened_at', [$filters['from'], $filters['to']]);

        return view('admin.reports.index', [
            'filters' => $filters,
            'summary' => [
                'tickets_count' => (clone $ticketBase)->where('status', '!=', 'CANCELLED')->count(),
                'sales_total' => (string) ((clone $ticketBase)->where('status', '!=', 'CANCELLED')->sum('total_amount') ?? '0'),
                'cancelled_count' => (clone $ticketBase)->where('status', 'CANCELLED')->count(),
                'cancelled_total' => (string) ((clone $ticketBase)->where('status', 'CANCELLED')->sum('total_amount') ?? '0'),
                'winners_count' => (clone $winnerBase)->count(),
                'winners_total' => (string) ((clone $winnerBase)->sum('prize_amount') ?? '0'),
                'paid_prizes_total' => (string) ((clone $paymentBase)->sum('amount') ?? '0'),
                'cash_sessions_count' => (clone $cashBase)->count(),
            ],
            ...$this->filterOptions($request),
        ]);
    }

    public function salesByDay(Request $request): View|Response
    {
        $filters = $this->filters($request);

        $query = Ticket::query()
            ->where('company_id', $filters['company_id'])
            ->where('status', '!=', 'CANCELLED')
            ->whereBetween('sold_at', [$filters['from'], $filters['to']]);

        $this->applyTicketFilters($query, $filters);

        if ($request->query('export')) {
            $rows = (clone $query)
                ->selectRaw('DATE(sold_at) as date, COUNT(*) as tickets_count, SUM(total_amount) as total_amount, SUM(total_possible_prize) as possible_prize')
                ->groupBy(DB::raw('DATE(sold_at)'))
                ->orderByDesc('date')
                ->limit(5000)
                ->get()
                ->map(fn ($row) => [
                    CarbonImmutable::parse($row->date)->format('d/m/Y'),
                    (int) $row->tickets_count,
                    number_format((float) $row->total_amount, 2),
                    number_format((float) $row->possible_prize, 2),
                ]);

            return $this->doExport(
                format: (string) $request->query('export'),
                headers: ['Fecha', 'Tickets', 'Total vendido (RD$)', 'Premio posible (RD$)'],
                rows: $rows,
                title: 'Ventas por Día',
                filename: 'ventas-por-dia',
                filters: $filters,
            );
        }

        $sales = $query
            ->selectRaw('DATE(sold_at) as date, COUNT(*) as tickets_count, SUM(total_amount) as total_amount, SUM(total_possible_prize) as possible_prize')
            ->groupBy(DB::raw('DATE(sold_at)'))
            ->orderByDesc('date')
            ->paginate($filters['per_page'])
            ->withQueryString();

        return view('admin.reports.sales-by-day', [
            'sales' => $sales,
            'filters' => $filters,
            ...$this->filterOptions($request),
        ]);
    }

    public function salesByBranch(Request $request): View|Response
    {
        $filters = $this->filters($request);

        $query = Ticket::query()
            ->with('branch')
            ->where('company_id', $filters['company_id'])
            ->where('status', '!=', 'CANCELLED')
            ->whereBetween('sold_at', [$filters['from'], $filters['to']]);

        $this->applyTicketFilters($query, $filters);

        if ($request->query('export')) {
            $rows = (clone $query)
                ->selectRaw('branch_id, COUNT(*) as tickets_count, SUM(total_amount) as total_amount, SUM(total_possible_prize) as possible_prize')
                ->groupBy('branch_id')
                ->orderByDesc('total_amount')
                ->limit(5000)
                ->get()
                ->map(fn ($row) => [
                    $row->branch?->name ?? '—',
                    (int) $row->tickets_count,
                    number_format((float) $row->total_amount, 2),
                    number_format((float) $row->possible_prize, 2),
                ]);

            return $this->doExport(
                format: (string) $request->query('export'),
                headers: ['Sucursal', 'Tickets', 'Total vendido (RD$)', 'Premio posible (RD$)'],
                rows: $rows,
                title: 'Ventas por Sucursal',
                filename: 'ventas-por-sucursal',
                filters: $filters,
            );
        }

        $sales = $query
            ->selectRaw('branch_id, COUNT(*) as tickets_count, SUM(total_amount) as total_amount, SUM(total_possible_prize) as possible_prize')
            ->groupBy('branch_id')
            ->orderByDesc('total_amount')
            ->paginate($filters['per_page'])
            ->withQueryString();

        return view('admin.reports.sales-by-branch', [
            'sales' => $sales,
            'filters' => $filters,
            ...$this->filterOptions($request),
        ]);
    }

    public function salesByLottery(Request $request): View|Response
    {
        $filters = $this->filters($request);

        $query = TicketDetail::query()
            ->with(['lottery', 'draw'])
            ->where('company_id', $filters['company_id'])
            ->where('status', '!=', 'CANCELLED')
            ->whereHas('ticket', fn (Builder $ticket) => $ticket->whereBetween('sold_at', [$filters['from'], $filters['to']]));

        $this->applyDetailFilters($query, $filters);

        if ($request->query('export')) {
            $rows = (clone $query)
                ->selectRaw('lottery_id, draw_id, COUNT(*) as plays_count, SUM(amount) as total_amount, SUM(possible_prize) as possible_prize')
                ->groupBy('lottery_id', 'draw_id')
                ->orderByDesc('total_amount')
                ->limit(5000)
                ->get()
                ->map(fn ($row) => [
                    $row->lottery?->name ?? '—',
                    $row->draw?->name ?? '—',
                    (int) $row->plays_count,
                    number_format((float) $row->total_amount, 2),
                    number_format((float) $row->possible_prize, 2),
                ]);

            return $this->doExport(
                format: (string) $request->query('export'),
                headers: ['Lotería', 'Sorteo', 'Jugadas', 'Total (RD$)', 'Premio posible (RD$)'],
                rows: $rows,
                title: 'Ventas por Lotería',
                filename: 'ventas-por-loteria',
                filters: $filters,
            );
        }

        $sales = $query
            ->selectRaw('lottery_id, draw_id, COUNT(*) as plays_count, SUM(amount) as total_amount, SUM(possible_prize) as possible_prize')
            ->groupBy('lottery_id', 'draw_id')
            ->orderByDesc('total_amount')
            ->paginate($filters['per_page'])
            ->withQueryString();

        return view('admin.reports.sales-by-lottery', [
            'sales' => $sales,
            'filters' => $filters,
            ...$this->filterOptions($request),
        ]);
    }

    public function topNumbers(Request $request): View|Response
    {
        $filters = $this->filters($request, ['per_page' => 50]);

        $query = TicketDetail::query()
            ->with(['lottery', 'draw', 'betType'])
            ->where('company_id', $filters['company_id'])
            ->where('status', '!=', 'CANCELLED')
            ->whereHas('ticket', fn (Builder $ticket) => $ticket->whereBetween('sold_at', [$filters['from'], $filters['to']]));

        $this->applyDetailFilters($query, $filters);

        if ($request->query('export')) {
            $rows = (clone $query)
                ->selectRaw('number_value, lottery_id, draw_id, bet_type_id, COUNT(*) as plays_count, SUM(amount) as total_amount')
                ->groupBy('number_value', 'lottery_id', 'draw_id', 'bet_type_id')
                ->orderByDesc('total_amount')
                ->limit(5000)
                ->get()
                ->map(fn ($row) => [
                    $row->number_value,
                    $row->lottery?->name ?? '—',
                    $row->draw?->name ?? '—',
                    $row->betType?->name ?? '—',
                    (int) $row->plays_count,
                    number_format((float) $row->total_amount, 2),
                ]);

            return $this->doExport(
                format: (string) $request->query('export'),
                headers: ['Número', 'Lotería', 'Sorteo', 'Tipo', 'Jugadas', 'Total (RD$)'],
                rows: $rows,
                title: 'Números Más Jugados',
                filename: 'top-numeros',
                filters: $filters,
            );
        }

        $numbers = $query
            ->selectRaw('number_value, lottery_id, draw_id, bet_type_id, COUNT(*) as plays_count, SUM(amount) as total_amount')
            ->groupBy('number_value', 'lottery_id', 'draw_id', 'bet_type_id')
            ->orderByDesc('total_amount')
            ->paginate($filters['per_page'])
            ->withQueryString();

        return view('admin.reports.top-numbers', [
            'numbers' => $numbers,
            'filters' => $filters,
            ...$this->filterOptions($request),
        ]);
    }

    public function cancelledTickets(Request $request): View|Response
    {
        $filters = $this->filters($request);

        $query = Ticket::query()
            ->with(['user', 'branch', 'cancelledBy'])
            ->where('company_id', $filters['company_id'])
            ->where('status', 'CANCELLED')
            ->whereBetween('cancelled_at', [$filters['from'], $filters['to']]);

        $this->applyTicketFilters($query, $filters);

        if ($request->query('export')) {
            $rows = (clone $query)
                ->orderByDesc('cancelled_at')
                ->limit(5000)
                ->get()
                ->map(fn ($ticket) => [
                    $ticket->ticket_number,
                    $ticket->branch?->name ?? '—',
                    $ticket->user?->username ?? '—',
                    $ticket->sold_at->format('d/m/Y H:i'),
                    $ticket->cancelled_at?->format('d/m/Y H:i') ?? '—',
                    $ticket->cancelledBy?->username ?? '—',
                    $ticket->cancel_reason ?? '—',
                    number_format((float) $ticket->total_amount, 2),
                ]);

            return $this->doExport(
                format: (string) $request->query('export'),
                headers: ['Ticket', 'Sucursal', 'Cajero', 'Vendido', 'Anulado', 'Anulado por', 'Motivo', 'Monto (RD$)'],
                rows: $rows,
                title: 'Tickets Anulados',
                filename: 'tickets-anulados',
                filters: $filters,
            );
        }

        $tickets = $query
            ->orderByDesc('cancelled_at')
            ->paginate($filters['per_page'])
            ->withQueryString();

        return view('admin.reports.cancelled', [
            'tickets' => $tickets,
            'filters' => $filters,
            ...$this->filterOptions($request),
        ]);
    }

    public function reprintedTickets(Request $request): View|Response
    {
        $filters = $this->filters($request);

        $query = PrintJob::query()
            ->with(['ticket.user', 'ticket.branch', 'printerConfig', 'device'])
            ->where('company_id', $filters['company_id'])
            ->where('type', 'REPRINT')
            ->whereBetween('created_at', [$filters['from'], $filters['to']]);

        $query
            ->when($filters['branch_id'], fn (Builder $query, int $branchId) => $query->where('branch_id', $branchId))
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['user_id'], function (Builder $query, int $userId): void {
                $query->whereHas('ticket', fn (Builder $ticket) => $ticket->where('user_id', $userId));
            })
            ->when($filters['lottery_id'] || $filters['draw_id'], function (Builder $query) use ($filters): void {
                $query->whereHas('ticket.details', function (Builder $detail) use ($filters): void {
                    $detail
                        ->when($filters['lottery_id'], fn (Builder $detail, int $lotteryId) => $detail->where('lottery_id', $lotteryId))
                        ->when($filters['draw_id'], fn (Builder $detail, int $drawId) => $detail->where('draw_id', $drawId));
                });
            });

        if ($request->query('export')) {
            $rows = (clone $query)
                ->orderByDesc('created_at')
                ->limit(5000)
                ->get()
                ->map(fn (PrintJob $job) => [
                    $job->ticket?->ticket_number ?? '—',
                    $job->ticket?->branch?->name ?? $job->branch?->name ?? '—',
                    $job->ticket?->user?->username ?? '—',
                    $job->ticket?->print_count ?? 0,
                    $job->printerConfig?->name ?? '—',
                    $job->device?->name ?? '—',
                    $job->status,
                    $job->attempts,
                    $job->printed_at?->format('d/m/Y H:i') ?? '—',
                    $job->created_at?->format('d/m/Y H:i') ?? '—',
                ]);

            return $this->doExport(
                format: (string) $request->query('export'),
                headers: ['Ticket', 'Sucursal', 'Cajero', 'Conteo', 'Impresora', 'Dispositivo', 'Estado', 'Intentos', 'Impreso', 'Creado'],
                rows: $rows,
                title: 'Tickets Reimpresos',
                filename: 'tickets-reimpresos',
                filters: $filters,
            );
        }

        $jobs = $query
            ->orderByDesc('created_at')
            ->paginate($filters['per_page'])
            ->withQueryString();

        return view('admin.reports.reprinted', [
            'jobs' => $jobs,
            'filters' => $filters,
            ...$this->filterOptions($request),
        ]);
    }

    public function cashSummary(Request $request): View|Response
    {
        $filters = $this->filters($request);

        $query = CashSession::query()
            ->with(['user', 'branch', 'closedBy', 'confirmedBy'])
            ->where('company_id', $filters['company_id'])
            ->whereBetween('opened_at', [$filters['from'], $filters['to']]);

        $this->applyCashFilters($query, $filters);

        if ($request->query('export')) {
            $rows = (clone $query)
                ->orderByDesc('opened_at')
                ->limit(5000)
                ->get()
                ->map(fn ($session) => [
                    str_pad((string) $session->id, 2, '0', STR_PAD_LEFT),
                    $session->branch?->name ?? '—',
                    $session->user?->name ?? '—',
                    $session->opened_at->format('d/m/Y H:i'),
                    $session->closed_at?->format('d/m/Y H:i') ?? '—',
                    number_format((float) $session->opening_amount, 2),
                    number_format((float) $session->expected_cash, 2),
                    $session->counted_cash !== null ? number_format((float) $session->counted_cash, 2) : '—',
                    $session->status,
                ]);

            return $this->doExport(
                format: (string) $request->query('export'),
                headers: ['Caja', 'Sucursal', 'Cajero', 'Apertura', 'Cierre', 'Apertura (RD$)', 'Esperado (RD$)', 'Contado (RD$)', 'Estado'],
                rows: $rows,
                title: 'Resumen de Caja',
                filename: 'resumen-caja',
                filters: $filters,
            );
        }

        $sessions = $query
            ->orderByDesc('opened_at')
            ->paginate($filters['per_page'])
            ->withQueryString();

        return view('admin.reports.cash-summary', [
            'sessions' => $sessions,
            'filters' => $filters,
            ...$this->filterOptions($request),
        ]);
    }

    public function winners(Request $request): View|Response
    {
        $filters = $this->filters($request);

        $query = WinnerTicket::query()
            ->with(['ticket', 'ticketDetail.betType', 'ticketDetail.draw.lottery', 'branch'])
            ->where('company_id', $filters['company_id'])
            ->whereBetween('created_at', [$filters['from'], $filters['to']]);

        $this->applyWinnerFilters($query, $filters);

        if ($request->query('export')) {
            $rows = (clone $query)
                ->orderByDesc('created_at')
                ->limit(5000)
                ->get()
                ->map(fn ($winner) => [
                    $winner->created_at?->format('d/m/Y H:i') ?? '—',
                    $winner->ticket?->ticket_number ?? '—',
                    $winner->branch?->name ?? '—',
                    $winner->ticketDetail?->draw?->lottery?->name ?? '—',
                    $winner->ticketDetail?->draw?->name ?? '—',
                    $winner->ticketDetail?->betType?->name ?? '—',
                    $winner->number_value,
                    number_format((float) $winner->amount_played, 2),
                    number_format((float) $winner->payout_multiplier, 2).'x',
                    number_format((float) $winner->prize_amount, 2),
                    $winner->status,
                ]);

            return $this->doExport(
                format: (string) $request->query('export'),
                headers: ['Fecha', 'Ticket', 'Sucursal', 'Lotería', 'Sorteo', 'Tipo', 'Número', 'Jugado (RD$)', 'Multiplicador', 'Premio (RD$)', 'Estado'],
                rows: $rows,
                title: 'Ganadores',
                filename: 'ganadores',
                filters: $filters,
            );
        }

        $winners = $query
            ->orderByDesc('created_at')
            ->paginate($filters['per_page'])
            ->withQueryString();

        return view('admin.reports.winners', [
            'winners' => $winners,
            'filters' => $filters,
            ...$this->filterOptions($request),
        ]);
    }

    public function prizesPaid(Request $request): View|Response
    {
        $filters = $this->filters($request);

        $query = PrizePayment::query()
            ->with(['ticket', 'winnerTicket.ticketDetail.betType', 'winnerTicket.ticketDetail.draw.lottery', 'paidBy', 'branch'])
            ->where('company_id', $filters['company_id'])
            ->whereBetween('paid_at', [$filters['from'], $filters['to']]);

        $this->applyPrizePaymentFilters($query, $filters);

        if ($request->query('export')) {
            $rows = (clone $query)
                ->orderByDesc('paid_at')
                ->limit(5000)
                ->get()
                ->map(fn ($payment) => [
                    $payment->ticket?->ticket_number ?? '—',
                    $payment->winnerTicket?->ticketDetail?->draw?->lottery?->name ?? '—',
                    $payment->winnerTicket?->ticketDetail?->draw?->name ?? '—',
                    $payment->winnerTicket?->ticketDetail?->betType?->name ?? '—',
                    $payment->winnerTicket?->number_value ?? '—',
                    number_format((float) ($payment->winnerTicket?->amount_played ?? 0), 2),
                    number_format((float) $payment->amount, 2),
                    $payment->paid_at->format('d/m/Y H:i'),
                    $payment->paidBy?->username ?? '—',
                ]);

            return $this->doExport(
                format: (string) $request->query('export'),
                headers: ['Ticket', 'Lotería', 'Sorteo', 'Tipo', 'Número', 'Jugado (RD$)', 'Premio pagado (RD$)', 'Pagado', 'Cajero'],
                rows: $rows,
                title: 'Premios Pagados',
                filename: 'premios-pagados',
                filters: $filters,
            );
        }

        $payments = $query
            ->orderByDesc('paid_at')
            ->paginate($filters['per_page'])
            ->withQueryString();

        return view('admin.reports.prizes-paid', [
            'payments' => $payments,
            'filters' => $filters,
            ...$this->filterOptions($request),
        ]);
    }

    public function numbersNearLimit(Request $request): View|Response
    {
        $filters = $this->filters($request, ['per_page' => 50]);
        $threshold = max(1, min(100, (int) $request->query('threshold', 70)));

        $query = LimitConsumption::query()
            ->with(['branch', 'lottery', 'draw', 'betType'])
            ->where('limit_consumptions.company_id', $filters['company_id'])
            ->when($filters['branch_id'], fn (Builder $q, int $b) => $q->where('limit_consumptions.branch_id', $b))
            ->when($filters['lottery_id'], fn (Builder $q, int $l) => $q->where('limit_consumptions.lottery_id', $l))
            ->when($filters['draw_id'], fn (Builder $q, int $d) => $q->where('limit_consumptions.draw_id', $d))
            ->join('limit_rules', function ($join) use ($filters): void {
                $join->on('limit_rules.company_id', '=', 'limit_consumptions.company_id')
                    ->where('limit_rules.status', 'ACTIVE')
                    ->where(function ($sub): void {
                        $sub->whereNull('limit_rules.lottery_id')
                            ->orWhereColumn('limit_rules.lottery_id', 'limit_consumptions.lottery_id');
                    })
                    ->where(function ($sub): void {
                        $sub->whereNull('limit_rules.draw_id')
                            ->orWhereColumn('limit_rules.draw_id', 'limit_consumptions.draw_id');
                    })
                    ->where(function ($sub): void {
                        $sub->whereNull('limit_rules.bet_type_id')
                            ->orWhereColumn('limit_rules.bet_type_id', 'limit_consumptions.bet_type_id');
                    })
                    ->where(function ($sub): void {
                        $sub->whereNull('limit_rules.branch_id')
                            ->orWhereColumn('limit_rules.branch_id', 'limit_consumptions.branch_id');
                    });
            })
            ->selectRaw('
                limit_consumptions.id,
                limit_consumptions.branch_id,
                limit_consumptions.lottery_id,
                limit_consumptions.draw_id,
                limit_consumptions.bet_type_id,
                limit_consumptions.number_value,
                (limit_consumptions.sold_amount + limit_consumptions.reserved_offline_amount - limit_consumptions.cancelled_amount) AS net_consumed,
                limit_rules.max_amount_per_number AS max_allowed,
                limit_rules.scope,
                CASE
                    WHEN limit_rules.max_amount_per_number > 0
                    THEN ROUND(
                        ((limit_consumptions.sold_amount + limit_consumptions.reserved_offline_amount - limit_consumptions.cancelled_amount)
                        / limit_rules.max_amount_per_number) * 100, 1
                    )
                    ELSE 0
                END AS usage_pct
            ')
            ->whereRaw('
                CASE
                    WHEN limit_rules.max_amount_per_number > 0
                    THEN ROUND(
                        ((limit_consumptions.sold_amount + limit_consumptions.reserved_offline_amount - limit_consumptions.cancelled_amount)
                        / limit_rules.max_amount_per_number) * 100, 1
                    )
                    ELSE 0
                END >= ?
            ', [$threshold])
            ->orderByRaw('
                CASE
                    WHEN limit_rules.max_amount_per_number > 0
                    THEN ROUND(
                        ((limit_consumptions.sold_amount + limit_consumptions.reserved_offline_amount - limit_consumptions.cancelled_amount)
                        / limit_rules.max_amount_per_number) * 100, 1
                    )
                    ELSE 0
                END DESC
            ');

        if ($request->query('export')) {
            $rows = (clone $query)->limit(5000)->get()->map(fn ($row) => [
                $row->number_value,
                $row->branch?->name ?? '—',
                $row->lottery?->name ?? '—',
                $row->draw?->name ?? '—',
                $row->betType?->name ?? '—',
                number_format((float) $row->max_allowed, 2),
                number_format((float) $row->net_consumed, 2),
                number_format((float) max(0, $row->max_allowed - $row->net_consumed), 2),
                $row->usage_pct.'%',
            ]);

            return $this->doExport(
                format: (string) $request->query('export'),
                headers: ['Número', 'Sucursal', 'Lotería', 'Sorteo', 'Tipo', 'Máx (RD$)', 'Consumido (RD$)', 'Disponible (RD$)', 'Uso %'],
                rows: $rows,
                title: 'Números Cerca del Límite (≥'.$threshold.'%)',
                filename: 'numeros-cerca-limite',
                filters: $filters,
            );
        }

        $numbers = (clone $query)->paginate($filters['per_page'])->withQueryString();

        return view('admin.reports.numbers-near-limit', [
            'numbers'   => $numbers,
            'filters'   => $filters,
            'threshold' => $threshold,
            ...$this->filterOptions($request),
        ]);
    }

    public function incomeStatement(Request $request): View|Response
    {
        $filters = $this->filters($request);
        $companyId = $filters['company_id'];
        $branchId = $filters['branch_id'];
        $from = $filters['from'];
        $to = $filters['to'];

        $salesTotal = Ticket::where('company_id', $companyId)
            ->where('status', '!=', 'CANCELLED')
            ->when($branchId, fn (Builder $q, int $b) => $q->where('branch_id', $b))
            ->whereBetween('sold_at', [$from, $to])
            ->sum('total_amount') ?? '0';

        $prizesTotal = PrizePayment::where('company_id', $companyId)
            ->when($branchId, fn (Builder $q, int $b) => $q->where('branch_id', $b))
            ->whereBetween('paid_at', [$from, $to])
            ->sum('amount') ?? '0';

        $expensesTotal = CashMovement::where('company_id', $companyId)
            ->where('direction', 'OUT')
            ->whereNotIn('type', ['PAYROLL', 'PRIZE_PAYMENT'])
            ->when($branchId, fn (Builder $q, int $b) => $q->where('branch_id', $b))
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount') ?? '0';

        $payrollTotal = PayrollPeriod::where('company_id', $companyId)
            ->where('status', 'PAID')
            ->when(! $branchId, fn (Builder $q) => $q)
            ->whereBetween('paid_at', [$from, $to])
            ->sum('total_net') ?? '0';

        $grossProfit = bcsub((string) $salesTotal, (string) $prizesTotal, 2);
        $netProfit = bcsub($grossProfit, bcadd((string) $expensesTotal, (string) $payrollTotal, 2), 2);

        $byBranch = Branch::where('company_id', $companyId)
            ->when($branchId, fn (Builder $q) => $q->whereKey($branchId))
            ->orderBy('name')
            ->get()
            ->map(function (Branch $branch) use ($from, $to, $companyId): array {
                $s = Ticket::where('company_id', $companyId)->where('branch_id', $branch->id)->where('status', '!=', 'CANCELLED')->whereBetween('sold_at', [$from, $to])->sum('total_amount') ?? 0;
                $p = PrizePayment::where('company_id', $companyId)->where('branch_id', $branch->id)->whereBetween('paid_at', [$from, $to])->sum('amount') ?? 0;
                $e = CashMovement::where('company_id', $companyId)->where('branch_id', $branch->id)->where('direction', 'OUT')->whereNotIn('type', ['PAYROLL', 'PRIZE_PAYMENT'])->whereBetween('created_at', [$from, $to])->sum('amount') ?? 0;
                return [
                    'name' => $branch->name,
                    'sales' => $s,
                    'prizes' => $p,
                    'expenses' => $e,
                    'net' => bcsub(bcsub((string) $s, (string) $p, 2), (string) $e, 2),
                ];
            });

        if ($request->query('export')) {
            $rows = $byBranch->map(fn ($row) => [
                $row['name'],
                number_format((float) $row['sales'], 2),
                number_format((float) $row['prizes'], 2),
                number_format((float) $row['expenses'], 2),
                number_format((float) $row['net'], 2),
            ]);
            return $this->doExport(
                format: (string) $request->query('export'),
                headers: ['Sucursal', 'Ventas (RD$)', 'Premios (RD$)', 'Gastos (RD$)', 'Utilidad (RD$)'],
                rows: $rows,
                title: 'Estado de Resultados',
                filename: 'estado-resultados',
                filters: $filters,
            );
        }

        return view('admin.reports.income-statement', [
            'filters' => $filters,
            'salesTotal' => $salesTotal,
            'prizesTotal' => $prizesTotal,
            'expensesTotal' => $expensesTotal,
            'payrollTotal' => $payrollTotal,
            'grossProfit' => $grossProfit,
            'netProfit' => $netProfit,
            'byBranch' => $byBranch,
            ...$this->filterOptions($request),
        ]);
    }

    public function payrollReport(Request $request): View|Response
    {
        $filters = $this->filters($request);
        $companyId = $filters['company_id'];

        $query = PayrollPeriod::where('company_id', $companyId)
            ->with(['creator', 'approver'])
            ->withCount('details')
            ->when($filters['status'], fn (Builder $q, string $s) => $q->where('status', $s))
            ->whereBetween('period_start', [$filters['from_date'], $filters['to_date']]);

        if ($request->query('export')) {
            $rows = (clone $query)->orderByDesc('period_start')->limit(5000)->get()
                ->map(fn ($p) => [
                    $p->period_start->format('d/m/Y'),
                    $p->period_end->format('d/m/Y'),
                    $p->frequency,
                    $p->details_count,
                    number_format((float) $p->total_gross, 2),
                    number_format((float) $p->total_deductions, 2),
                    number_format((float) $p->total_net, 2),
                    $p->status,
                    $p->paid_at?->format('d/m/Y') ?? '—',
                ]);
            return $this->doExport(
                format: (string) $request->query('export'),
                headers: ['Período inicio', 'Período fin', 'Frecuencia', 'Empleados', 'Bruto (RD$)', 'Deducciones (RD$)', 'Neto (RD$)', 'Estado', 'Pagado'],
                rows: $rows,
                title: 'Nómina por Período',
                filename: 'nomina-por-periodo',
                filters: $filters,
            );
        }

        $periods = $query->orderByDesc('period_start')->paginate($filters['per_page'])->withQueryString();

        return view('admin.reports.payroll-report', [
            'periods' => $periods,
            'filters' => $filters,
            ...$this->filterOptions($request),
        ]);
    }

    public function cashMovements(Request $request): View|Response
    {
        $filters = $this->filters($request);
        $companyId = $filters['company_id'];
        $branchId = $filters['branch_id'];

        $type = $request->string('mov_type')->trim()->toString() ?: null;
        $direction = $request->string('direction')->trim()->toString() ?: null;

        $query = CashMovement::with(['branch', 'user'])
            ->where('company_id', $companyId)
            ->when($branchId, fn (Builder $q, int $b) => $q->where('branch_id', $b))
            ->when($type, fn (Builder $q, string $t) => $q->where('type', $t))
            ->when($direction, fn (Builder $q, string $d) => $q->where('direction', $d))
            ->whereBetween('created_at', [$filters['from'], $filters['to']])
            ->orderByDesc('created_at');

        if ($request->query('export')) {
            $rows = (clone $query)->limit(5000)->get()->map(fn ($m) => [
                $m->created_at?->format('d/m/Y H:i'),
                $m->branch?->name ?? '—',
                $m->user?->name ?? '—',
                $m->type,
                $m->direction,
                number_format((float) $m->amount, 2),
                $m->description,
            ]);
            return $this->doExport(
                format: (string) $request->query('export'),
                headers: ['Fecha', 'Sucursal', 'Usuario', 'Tipo', 'Dirección', 'Monto (RD$)', 'Descripción'],
                rows: $rows,
                title: 'Movimientos de Caja',
                filename: 'movimientos-caja',
                filters: $filters,
            );
        }

        $movements = $query->paginate($filters['per_page'])->withQueryString();

        return view('admin.reports.cash-movements', [
            'movements' => $movements,
            'filters'   => $filters,
            'mov_type'  => $type,
            'direction' => $direction,
            ...$this->filterOptions($request),
        ]);
    }

    public function expensesByBranch(Request $request): View|Response
    {
        $filters = $this->filters($request);
        $companyId = $filters['company_id'];
        $branchId = $filters['branch_id'];
        $from = $filters['from'];
        $to = $filters['to'];

        $byBranch = Branch::where('company_id', $companyId)
            ->when($branchId, fn (Builder $q) => $q->whereKey($branchId))
            ->orderBy('name')
            ->get()
            ->map(function (Branch $branch) use ($companyId, $from, $to): array {
                $total = CashMovement::where('company_id', $companyId)
                    ->where('branch_id', $branch->id)
                    ->where('direction', 'OUT')
                    ->whereNotIn('type', ['PAYROLL', 'PRIZE_PAYMENT'])
                    ->whereBetween('created_at', [$from, $to])
                    ->sum('amount') ?? 0;

                $breakdown = CashMovement::where('company_id', $companyId)
                    ->where('branch_id', $branch->id)
                    ->where('direction', 'OUT')
                    ->whereNotIn('type', ['PAYROLL', 'PRIZE_PAYMENT'])
                    ->whereBetween('created_at', [$from, $to])
                    ->selectRaw('type, SUM(amount) as subtotal')
                    ->groupBy('type')
                    ->orderByDesc('subtotal')
                    ->pluck('subtotal', 'type');

                return [
                    'name'      => $branch->name,
                    'total'     => $total,
                    'breakdown' => $breakdown,
                ];
            })
            ->filter(fn ($row) => $row['total'] > 0)
            ->values();

        $grandTotal = $byBranch->sum('total');

        if ($request->query('export')) {
            $rows = $byBranch->map(fn ($row) => [
                $row['name'],
                number_format((float) $row['total'], 2),
            ]);
            return $this->doExport(
                format: (string) $request->query('export'),
                headers: ['Sucursal', 'Total gastos (RD$)'],
                rows: $rows,
                title: 'Gastos por Sucursal',
                filename: 'gastos-por-sucursal',
                filters: $filters,
            );
        }

        return view('admin.reports.expenses-by-branch', [
            'byBranch'   => $byBranch,
            'grandTotal' => $grandTotal,
            'filters'    => $filters,
            ...$this->filterOptions($request),
        ]);
    }

    public function cashInOut(Request $request): View|Response
    {
        $filters = $this->filters($request);
        $companyId = $filters['company_id'];
        $branchId = $filters['branch_id'];
        $from = $filters['from'];
        $to = $filters['to'];

        $byBranch = Branch::where('company_id', $companyId)
            ->when($branchId, fn (Builder $q) => $q->whereKey($branchId))
            ->orderBy('name')
            ->get()
            ->map(function (Branch $branch) use ($companyId, $from, $to): array {
                $in = CashMovement::where('company_id', $companyId)
                    ->where('branch_id', $branch->id)
                    ->where('direction', 'IN')
                    ->whereBetween('created_at', [$from, $to])
                    ->sum('amount') ?? 0;

                $out = CashMovement::where('company_id', $companyId)
                    ->where('branch_id', $branch->id)
                    ->where('direction', 'OUT')
                    ->whereBetween('created_at', [$from, $to])
                    ->sum('amount') ?? 0;

                return [
                    'name'    => $branch->name,
                    'in'      => $in,
                    'out'     => $out,
                    'balance' => bcsub((string) $in, (string) $out, 2),
                ];
            });

        $totalIn  = $byBranch->sum('in');
        $totalOut = $byBranch->sum('out');
        $totalBalance = bcsub((string) $totalIn, (string) $totalOut, 2);

        if ($request->query('export')) {
            $rows = $byBranch->map(fn ($row) => [
                $row['name'],
                number_format((float) $row['in'], 2),
                number_format((float) $row['out'], 2),
                number_format((float) $row['balance'], 2),
            ]);
            return $this->doExport(
                format: (string) $request->query('export'),
                headers: ['Sucursal', 'Entradas (RD$)', 'Salidas (RD$)', 'Balance (RD$)'],
                rows: $rows,
                title: 'Entradas y Salidas de Caja',
                filename: 'entradas-salidas',
                filters: $filters,
            );
        }

        return view('admin.reports.cash-in-out', [
            'byBranch'     => $byBranch,
            'totalIn'      => $totalIn,
            'totalOut'     => $totalOut,
            'totalBalance' => $totalBalance,
            'filters'      => $filters,
            ...$this->filterOptions($request),
        ]);
    }

    public function payrollAdvances(Request $request): View|Response
    {
        $filters = $this->filters($request);
        $companyId = $filters['company_id'];
        $branchId = $filters['branch_id'];

        $query = EmployeeAdvance::with(['employee', 'branch', 'approver'])
            ->where('company_id', $companyId)
            ->when($branchId, fn (Builder $q, int $b) => $q->where('branch_id', $b))
            ->when($filters['status'], fn (Builder $q, string $s) => $q->where('status', $s))
            ->whereBetween('requested_at', [$filters['from_date'], $filters['to_date']])
            ->orderByDesc('requested_at');

        if ($request->query('export')) {
            $rows = (clone $query)->limit(5000)->get()->map(fn ($a) => [
                $a->requested_at?->format('d/m/Y'),
                $a->employee?->name ?? '—',
                $a->branch?->name ?? '—',
                number_format((float) $a->amount, 2),
                $a->status,
                $a->paid_at?->format('d/m/Y') ?? '—',
                $a->notes,
            ]);
            return $this->doExport(
                format: (string) $request->query('export'),
                headers: ['Fecha', 'Empleado', 'Sucursal', 'Monto (RD$)', 'Estado', 'Pagado', 'Notas'],
                rows: $rows,
                title: 'Avances a Empleados',
                filename: 'avances-empleados',
                filters: $filters,
            );
        }

        $advances = $query->paginate($filters['per_page'])->withQueryString();

        return view('admin.reports.payroll-advances', [
            'advances' => $advances,
            'filters'  => $filters,
            ...$this->filterOptions($request),
        ]);
    }

    public function payrollLoans(Request $request): View|Response
    {
        $filters = $this->filters($request);
        $companyId = $filters['company_id'];
        $branchId = $filters['branch_id'];

        $query = EmployeeLoan::with(['employee', 'branch', 'approver'])
            ->where('company_id', $companyId)
            ->when($branchId, fn (Builder $q, int $b) => $q->where('branch_id', $b))
            ->when($filters['status'], fn (Builder $q, string $s) => $q->where('status', $s))
            ->orderByDesc('started_at');

        if ($request->query('export')) {
            $rows = (clone $query)->limit(5000)->get()->map(fn ($l) => [
                $l->started_at?->format('d/m/Y'),
                $l->employee?->name ?? '—',
                $l->branch?->name ?? '—',
                number_format((float) $l->principal, 2),
                number_format((float) $l->balance, 2),
                number_format((float) $l->installment, 2),
                $l->status,
            ]);
            return $this->doExport(
                format: (string) $request->query('export'),
                headers: ['Inicio', 'Empleado', 'Sucursal', 'Principal (RD$)', 'Saldo (RD$)', 'Cuota (RD$)', 'Estado'],
                rows: $rows,
                title: 'Préstamos a Empleados',
                filename: 'prestamos-empleados',
                filters: $filters,
            );
        }

        $loans = $query->paginate($filters['per_page'])->withQueryString();

        return view('admin.reports.payroll-loans', [
            'loans'   => $loans,
            'filters' => $filters,
            ...$this->filterOptions($request),
        ]);
    }

    public function payrollPayments(Request $request): View|Response
    {
        $filters = $this->filters($request);
        $companyId = $filters['company_id'];
        $branchId = $filters['branch_id'];

        $query = PayrollDetail::with(['employee', 'branch', 'period'])
            ->where('company_id', $companyId)
            ->when($branchId, fn (Builder $q, int $b) => $q->where('branch_id', $b))
            ->whereHas('period', fn (Builder $q) => $q
                ->where('status', 'PAID')
                ->whereBetween('period_start', [$filters['from_date'], $filters['to_date']])
            )
            ->orderByDesc('id');

        if ($request->query('export')) {
            $rows = (clone $query)->limit(5000)->get()->map(fn ($d) => [
                $d->period?->period_start?->format('d/m/Y') ?? '—',
                $d->period?->period_end?->format('d/m/Y') ?? '—',
                $d->employee?->name ?? '—',
                $d->branch?->name ?? '—',
                number_format((float) $d->gross_pay, 2),
                number_format((float) $d->total_deductions, 2),
                number_format((float) $d->net_pay, 2),
            ]);
            return $this->doExport(
                format: (string) $request->query('export'),
                headers: ['Período inicio', 'Período fin', 'Empleado', 'Sucursal', 'Bruto (RD$)', 'Deducciones (RD$)', 'Neto (RD$)'],
                rows: $rows,
                title: 'Pagos a Empleados',
                filename: 'pagos-empleados',
                filters: $filters,
            );
        }

        $payments = $query->paginate($filters['per_page'])->withQueryString();

        return view('admin.reports.payroll-payments', [
            'payments' => $payments,
            'filters'  => $filters,
            ...$this->filterOptions($request),
        ]);
    }

    public function payrollDeductions(Request $request): View|Response
    {
        $filters = $this->filters($request);
        $companyId = $filters['company_id'];
        $branchId = $filters['branch_id'];

        $query = PayrollDetail::with(['employee', 'branch', 'period'])
            ->where('company_id', $companyId)
            ->when($branchId, fn (Builder $q, int $b) => $q->where('branch_id', $b))
            ->where('total_deductions', '>', 0)
            ->whereHas('period', fn (Builder $q) => $q->whereBetween('period_start', [$filters['from_date'], $filters['to_date']]))
            ->orderByDesc('total_deductions');

        if ($request->query('export')) {
            $rows = (clone $query)->limit(5000)->get()->map(fn ($d) => [
                $d->period?->period_start?->format('d/m/Y') ?? '—',
                $d->employee?->name ?? '—',
                $d->branch?->name ?? '—',
                number_format((float) $d->advance_deduction, 2),
                number_format((float) $d->loan_deduction, 2),
                number_format((float) $d->cash_shortage, 2),
                number_format((float) $d->other_deductions, 2),
                number_format((float) $d->total_deductions, 2),
            ]);
            return $this->doExport(
                format: (string) $request->query('export'),
                headers: ['Período', 'Empleado', 'Sucursal', 'Avance (RD$)', 'Préstamo (RD$)', 'Faltante (RD$)', 'Otros (RD$)', 'Total (RD$)'],
                rows: $rows,
                title: 'Descuentos en Nómina',
                filename: 'descuentos-nomina',
                filters: $filters,
            );
        }

        $deductions = $query->paginate($filters['per_page'])->withQueryString();

        return view('admin.reports.payroll-deductions', [
            'deductions' => $deductions,
            'filters'    => $filters,
            ...$this->filterOptions($request),
        ]);
    }

    public function payrollShortages(Request $request): View|Response
    {
        $filters = $this->filters($request);
        $companyId = $filters['company_id'];
        $branchId = $filters['branch_id'];

        $query = PayrollDetail::with(['employee', 'branch', 'period'])
            ->where('company_id', $companyId)
            ->when($branchId, fn (Builder $q, int $b) => $q->where('branch_id', $b))
            ->where('cash_shortage', '>', 0)
            ->whereHas('period', fn (Builder $q) => $q->whereBetween('period_start', [$filters['from_date'], $filters['to_date']]))
            ->orderByDesc('cash_shortage');

        if ($request->query('export')) {
            $rows = (clone $query)->limit(5000)->get()->map(fn ($d) => [
                $d->period?->period_start?->format('d/m/Y') ?? '—',
                $d->period?->period_end?->format('d/m/Y') ?? '—',
                $d->employee?->name ?? '—',
                $d->branch?->name ?? '—',
                number_format((float) $d->cash_shortage, 2),
                number_format((float) $d->net_pay, 2),
            ]);
            return $this->doExport(
                format: (string) $request->query('export'),
                headers: ['Período inicio', 'Período fin', 'Empleado', 'Sucursal', 'Faltante descontado (RD$)', 'Neto pagado (RD$)'],
                rows: $rows,
                title: 'Faltantes Descontados en Nómina',
                filename: 'faltantes-descontados',
                filters: $filters,
            );
        }

        $shortages = $query->paginate($filters['per_page'])->withQueryString();

        return view('admin.reports.payroll-shortages', [
            'shortages' => $shortages,
            'filters'   => $filters,
            ...$this->filterOptions($request),
        ]);
    }

    public function incomeVsExpenses(Request $request): View|Response
    {
        $filters = $this->filters($request);
        $companyId = $filters['company_id'];
        $branchId = $filters['branch_id'];
        $from = $filters['from'];
        $to = $filters['to'];

        $accounts = AccountingAccount::where('company_id', $companyId)
            ->whereIn('type', ['INCOME', 'EXPENSE'])
            ->orderBy('type')
            ->orderBy('code')
            ->get();

        $rows = $accounts->map(function (AccountingAccount $account) use ($companyId, $branchId, $from, $to): array {
            $base = JournalEntryLine::where('company_id', $companyId)
                ->where('account_id', $account->id)
                ->when($branchId, fn (Builder $q, int $b) => $q->where('branch_id', $b))
                ->whereHas('journalEntry', fn (Builder $q) => $q->whereBetween('entry_date', [$from->toDateString(), $to->toDateString()]));

            $debit  = $base->sum('debit') ?? 0;
            $credit = (clone $base)->sum('credit') ?? 0;

            return [
                'code'    => $account->code,
                'name'    => $account->name,
                'type'    => $account->type,
                'debit'   => $debit,
                'credit'  => $credit,
                'net'     => bcsub((string) $credit, (string) $debit, 2),
            ];
        })->filter(fn ($r) => $r['debit'] > 0 || $r['credit'] > 0);

        $totalIncome  = $rows->where('type', 'INCOME')->sum('credit');
        $totalExpense = $rows->where('type', 'EXPENSE')->sum('debit');
        $netResult    = bcsub((string) $totalIncome, (string) $totalExpense, 2);

        if ($request->query('export')) {
            $exportRows = $rows->map(fn ($r) => [
                $r['code'], $r['name'], $r['type'],
                number_format((float) $r['debit'], 2),
                number_format((float) $r['credit'], 2),
                number_format((float) $r['net'], 2),
            ])->values();
            return $this->doExport(
                format: (string) $request->query('export'),
                headers: ['Código', 'Cuenta', 'Tipo', 'Débito (RD$)', 'Crédito (RD$)', 'Neto (RD$)'],
                rows: $exportRows,
                title: 'Ingresos vs Gastos',
                filename: 'ingresos-vs-gastos',
                filters: $filters,
            );
        }

        return view('admin.reports.income-vs-expenses', [
            'rows'         => $rows,
            'totalIncome'  => $totalIncome,
            'totalExpense' => $totalExpense,
            'netResult'    => $netResult,
            'filters'      => $filters,
            ...$this->filterOptions($request),
        ]);
    }

    public function profitByBranch(Request $request): View|Response
    {
        $filters = $this->filters($request);
        $companyId = $filters['company_id'];
        $branchId = $filters['branch_id'];
        $from = $filters['from'];
        $to = $filters['to'];

        $byBranch = Branch::where('company_id', $companyId)
            ->when($branchId, fn (Builder $q) => $q->whereKey($branchId))
            ->orderBy('name')
            ->get()
            ->map(function (Branch $branch) use ($companyId, $from, $to): array {
                $sales = Ticket::where('company_id', $companyId)->where('branch_id', $branch->id)
                    ->where('status', '!=', 'CANCELLED')->whereBetween('sold_at', [$from, $to])->sum('total_amount') ?? 0;
                $prizes = PrizePayment::where('company_id', $companyId)->where('branch_id', $branch->id)
                    ->whereBetween('paid_at', [$from, $to])->sum('amount') ?? 0;
                $expenses = CashMovement::where('company_id', $companyId)->where('branch_id', $branch->id)
                    ->where('direction', 'OUT')->whereNotIn('type', ['PAYROLL', 'PRIZE_PAYMENT'])
                    ->whereBetween('created_at', [$from, $to])->sum('amount') ?? 0;
                $payroll = PayrollPeriod::where('company_id', $companyId)->where('status', 'PAID')
                    ->whereBetween('paid_at', [$from, $to])->sum('total_net') ?? 0;

                $gross = bcsub((string) $sales, (string) $prizes, 2);
                $net   = bcsub($gross, bcadd((string) $expenses, (string) $payroll, 2), 2);

                return [
                    'name'     => $branch->name,
                    'sales'    => $sales,
                    'prizes'   => $prizes,
                    'expenses' => $expenses,
                    'payroll'  => $payroll,
                    'gross'    => $gross,
                    'net'      => $net,
                ];
            });

        if ($request->query('export')) {
            $rows = $byBranch->map(fn ($r) => [
                $r['name'],
                number_format((float) $r['sales'], 2),
                number_format((float) $r['prizes'], 2),
                number_format((float) $r['expenses'], 2),
                number_format((float) $r['payroll'], 2),
                number_format((float) $r['net'], 2),
            ])->values();
            return $this->doExport(
                format: (string) $request->query('export'),
                headers: ['Sucursal', 'Ventas (RD$)', 'Premios (RD$)', 'Gastos (RD$)', 'Nómina (RD$)', 'Utilidad (RD$)'],
                rows: $rows,
                title: 'Utilidad por Sucursal',
                filename: 'utilidad-por-sucursal',
                filters: $filters,
            );
        }

        return view('admin.reports.profit-by-branch', [
            'byBranch' => $byBranch,
            'filters'  => $filters,
            ...$this->filterOptions($request),
        ]);
    }

    public function profitByCompany(Request $request): View|Response
    {
        $filters = $this->filters($request);
        $companyId = $filters['company_id'];
        $from = $filters['from'];
        $to = $filters['to'];

        $sales = Ticket::where('company_id', $companyId)->where('status', '!=', 'CANCELLED')
            ->whereBetween('sold_at', [$from, $to])->sum('total_amount') ?? 0;
        $prizes = PrizePayment::where('company_id', $companyId)
            ->whereBetween('paid_at', [$from, $to])->sum('amount') ?? 0;
        $expenses = CashMovement::where('company_id', $companyId)->where('direction', 'OUT')
            ->whereNotIn('type', ['PAYROLL', 'PRIZE_PAYMENT'])
            ->whereBetween('created_at', [$from, $to])->sum('amount') ?? 0;
        $payroll = PayrollPeriod::where('company_id', $companyId)->where('status', 'PAID')
            ->whereBetween('paid_at', [$from, $to])->sum('total_net') ?? 0;
        $cancelledTickets = Ticket::where('company_id', $companyId)->where('status', 'CANCELLED')
            ->whereBetween('sold_at', [$from, $to])->sum('total_amount') ?? 0;

        $grossProfit = bcsub((string) $sales, (string) $prizes, 2);
        $netProfit   = bcsub($grossProfit, bcadd((string) $expenses, (string) $payroll, 2), 2);
        $margin      = $sales > 0 ? round(100 * (float) $netProfit / (float) $sales, 1) : 0;

        if ($request->query('export')) {
            $rows = collect([
                ['Ventas brutas', number_format((float) $sales, 2)],
                ['Tickets anulados', number_format((float) $cancelledTickets, 2)],
                ['Premios pagados', number_format((float) $prizes, 2)],
                ['Utilidad bruta', number_format((float) $grossProfit, 2)],
                ['Gastos operativos', number_format((float) $expenses, 2)],
                ['Nómina', number_format((float) $payroll, 2)],
                ['Utilidad neta', number_format((float) $netProfit, 2)],
                ['Margen neto %', $margin.'%'],
            ]);
            return $this->doExport(
                format: (string) $request->query('export'),
                headers: ['Concepto', 'Monto (RD$)'],
                rows: $rows,
                title: 'Utilidad por Empresa',
                filename: 'utilidad-empresa',
                filters: $filters,
            );
        }

        return view('admin.reports.profit-by-company', [
            'sales'           => $sales,
            'cancelledTickets'=> $cancelledTickets,
            'prizes'          => $prizes,
            'expenses'        => $expenses,
            'payroll'         => $payroll,
            'grossProfit'     => $grossProfit,
            'netProfit'       => $netProfit,
            'margin'          => $margin,
            'filters'         => $filters,
            ...$this->filterOptions($request),
        ]);
    }

    public function accountsReceivable(Request $request): View|Response
    {
        $filters = $this->filters($request);
        $companyId = $filters['company_id'];
        $branchId = $filters['branch_id'];

        $loans = EmployeeLoan::with(['employee', 'branch'])
            ->where('company_id', $companyId)
            ->when($branchId, fn (Builder $q, int $b) => $q->where('branch_id', $b))
            ->where('status', 'ACTIVE')
            ->where('balance', '>', 0)
            ->orderByDesc('balance')
            ->get();

        $advances = EmployeeAdvance::with(['employee', 'branch'])
            ->where('company_id', $companyId)
            ->when($branchId, fn (Builder $q, int $b) => $q->where('branch_id', $b))
            ->where('status', 'APPROVED')
            ->whereNull('paid_at')
            ->orderByDesc('amount')
            ->get();

        $totalLoans    = $loans->sum('balance');
        $totalAdvances = $advances->sum('amount');
        $grandTotal    = bcadd((string) $totalLoans, (string) $totalAdvances, 2);

        if ($request->query('export')) {
            $rows = collect();
            foreach ($loans as $l) {
                $rows->push([$l->employee?->name ?? '—', $l->branch?->name ?? '—', 'Préstamo', number_format((float) $l->balance, 2)]);
            }
            foreach ($advances as $a) {
                $rows->push([$a->employee?->name ?? '—', $a->branch?->name ?? '—', 'Avance', number_format((float) $a->amount, 2)]);
            }
            return $this->doExport(
                format: (string) $request->query('export'),
                headers: ['Empleado', 'Sucursal', 'Tipo', 'Saldo (RD$)'],
                rows: $rows,
                title: 'Cuentas por Cobrar',
                filename: 'cuentas-por-cobrar',
                filters: $filters,
            );
        }

        return view('admin.reports.accounts-receivable', [
            'loans'         => $loans,
            'advances'      => $advances,
            'totalLoans'    => $totalLoans,
            'totalAdvances' => $totalAdvances,
            'grandTotal'    => $grandTotal,
            'filters'       => $filters,
            ...$this->filterOptions($request),
        ]);
    }

    public function accountsPayable(Request $request): View|Response
    {
        $filters = $this->filters($request);
        $companyId = $filters['company_id'];
        $branchId = $filters['branch_id'];

        $unpaidWinners = WinnerTicket::with(['branch', 'draw', 'ticket'])
            ->where('company_id', $companyId)
            ->when($branchId, fn (Builder $q, int $b) => $q->where('branch_id', $b))
            ->whereNull('paid_at')
            ->whereBetween('created_at', [$filters['from'], $filters['to']])
            ->orderByDesc('prize_amount')
            ->get();

        $totalOwed = $unpaidWinners->sum('prize_amount');

        $byBranch = $unpaidWinners->groupBy('branch_id')->map(function ($group) {
            return [
                'name'  => $group->first()->branch?->name ?? '—',
                'count' => $group->count(),
                'total' => $group->sum('prize_amount'),
            ];
        })->values();

        if ($request->query('export')) {
            $rows = $unpaidWinners->map(fn ($w) => [
                $w->created_at?->format('d/m/Y'),
                $w->branch?->name ?? '—',
                $w->number_value,
                $w->draw?->name ?? '—',
                number_format((float) $w->prize_amount, 2),
            ]);
            return $this->doExport(
                format: (string) $request->query('export'),
                headers: ['Fecha', 'Sucursal', 'Número', 'Sorteo', 'Premio (RD$)'],
                rows: $rows,
                title: 'Cuentas por Pagar (Premios Pendientes)',
                filename: 'cuentas-por-pagar',
                filters: $filters,
            );
        }

        return view('admin.reports.accounts-payable', [
            'unpaidWinners' => $unpaidWinners,
            'totalOwed'     => $totalOwed,
            'byBranch'      => $byBranch,
            'filters'       => $filters,
            ...$this->filterOptions($request),
        ]);
    }

    public function cashFlow(Request $request): View|Response
    {
        $filters = $this->filters($request);
        $companyId = $filters['company_id'];
        $branchId = $filters['branch_id'];
        $from = $filters['from'];
        $to = $filters['to'];

        $sessions = CashSession::with(['branch', 'user'])
            ->where('company_id', $companyId)
            ->when($branchId, fn (Builder $q, int $b) => $q->where('branch_id', $b))
            ->whereBetween('opened_at', [$from, $to])
            ->orderByDesc('opened_at')
            ->get();

        $byDay = $sessions->groupBy(fn ($s) => $s->opened_at->toDateString())
            ->map(function ($group, string $date): array {
                return [
                    'date'           => $date,
                    'opening'        => $group->sum('opening_amount'),
                    'sales'          => $group->sum('sales_total'),
                    'cash_in'        => $group->sum('cash_in_total'),
                    'prizes_paid'    => $group->sum('prizes_paid_total'),
                    'cash_out'       => $group->sum('cash_out_total'),
                    'expenses'       => $group->sum('expenses_total'),
                    'sessions_count' => $group->count(),
                ];
            })
            ->sortKeysDesc()
            ->values();

        $totals = [
            'opening'     => $sessions->sum('opening_amount'),
            'sales'       => $sessions->sum('sales_total'),
            'cash_in'     => $sessions->sum('cash_in_total'),
            'prizes_paid' => $sessions->sum('prizes_paid_total'),
            'cash_out'    => $sessions->sum('cash_out_total'),
            'expenses'    => $sessions->sum('expenses_total'),
        ];

        if ($request->query('export')) {
            $rows = $byDay->map(fn ($r) => [
                $r['date'],
                number_format((float) $r['opening'], 2),
                number_format((float) $r['sales'], 2),
                number_format((float) $r['cash_in'], 2),
                number_format((float) $r['prizes_paid'], 2),
                number_format((float) $r['cash_out'], 2),
                number_format((float) $r['expenses'], 2),
                $r['sessions_count'],
            ]);
            return $this->doExport(
                format: (string) $request->query('export'),
                headers: ['Fecha', 'Apertura (RD$)', 'Ventas (RD$)', 'Entradas (RD$)', 'Premios (RD$)', 'Salidas (RD$)', 'Gastos (RD$)', 'Sesiones'],
                rows: $rows,
                title: 'Flujo de Efectivo',
                filename: 'flujo-efectivo',
                filters: $filters,
            );
        }

        return view('admin.reports.cash-flow', [
            'byDay'   => $byDay,
            'totals'  => $totals,
            'filters' => $filters,
            ...$this->filterOptions($request),
        ]);
    }

    public function payrollCommissions(Request $request): View|Response
    {
        $filters = $this->filters($request);
        $companyId = $filters['company_id'];
        $branchId = $filters['branch_id'];

        $query = PayrollDetail::with(['employee', 'branch', 'period'])
            ->where('company_id', $companyId)
            ->when($branchId, fn (Builder $q, int $b) => $q->where('branch_id', $b))
            ->where('commission', '>', 0)
            ->whereHas('period', fn (Builder $q) => $q->whereBetween('period_start', [$filters['from_date'], $filters['to_date']]))
            ->orderByDesc('commission');

        if ($request->query('export')) {
            $rows = (clone $query)->limit(5000)->get()->map(fn ($d) => [
                $d->period?->period_start?->format('d/m/Y') ?? '—',
                $d->period?->period_end?->format('d/m/Y') ?? '—',
                $d->employee?->name ?? '—',
                $d->branch?->name ?? '—',
                number_format((float) $d->commission, 2),
                number_format((float) $d->gross_pay, 2),
            ]);
            return $this->doExport(
                format: (string) $request->query('export'),
                headers: ['Período inicio', 'Período fin', 'Empleado', 'Sucursal', 'Comisión (RD$)', 'Bruto (RD$)'],
                rows: $rows,
                title: 'Comisiones por Empleado',
                filename: 'comisiones-empleados',
                filters: $filters,
            );
        }

        $commissions = $query->paginate($filters['per_page'])->withQueryString();

        return view('admin.reports.payroll-commissions', [
            'commissions' => $commissions,
            'filters'     => $filters,
            ...$this->filterOptions($request),
        ]);
    }

    /**
     * @param array<string> $headers
     * @param Collection<int, array<mixed>> $rows
     * @param array<string, mixed> $filters
     */
    private function doExport(
        string $format,
        array $headers,
        Collection $rows,
        string $title,
        string $filename,
        array $filters,
    ): Response {
        $date = now()->format('Y-m-d');

        if ($format === 'excel') {
            return Excel::download(
                new ReportExport($headers, $rows),
                "{$filename}-{$date}.xlsx"
            );
        }

        if ($format === 'csv') {
            return Excel::download(
                new ReportExport($headers, $rows),
                "{$filename}-{$date}.csv",
                \Maatwebsite\Excel\Excel::CSV
            );
        }

        $company = Company::query()->find($filters['company_id']);
        $branch = $filters['branch_id'] ? Branch::query()->find($filters['branch_id']) : null;

        return Pdf::loadView('admin.reports.exports.pdf', [
            'title' => $title,
            'headers' => $headers,
            'rows' => $rows,
            'companyName' => $company?->name ?? 'BSLotery',
            'fromDate' => $filters['from_date'],
            'toDate' => $filters['to_date'],
            'branchName' => $branch?->name,
            'totalRows' => $rows->count(),
        ])
        ->setPaper('a4', 'landscape')
        ->download("{$filename}-{$date}.pdf");
    }

    /**
     * @param array<string, mixed> $defaults
     * @return array<string, mixed>
     */
    private function filters(Request $request, array $defaults = []): array
    {
        $user = $request->user();
        $companyId = (int) session('active_company_id', $user?->company_id);
        $forcedBranchId = $this->forcedBranchId($request);
        $requestedBranchId = $request->integer('branch_id') ?: null;
        $branchId = $forcedBranchId ?: $requestedBranchId;

        $from = $this->dateOrDefault($request->query('from'), now()->startOfMonth()->toDateString())->startOfDay();
        $to = $this->dateOrDefault($request->query('to'), now()->toDateString())->endOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to->startOfDay(), $from->endOfDay()];
        }

        return [
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'forced_branch_id' => $forcedBranchId,
            'user_id' => $request->integer('user_id') ?: null,
            'lottery_id' => $request->integer('lottery_id') ?: null,
            'draw_id' => $request->integer('draw_id') ?: null,
            'status' => $request->string('status')->trim()->toString() ?: null,
            'from' => $from,
            'to' => $to,
            'from_date' => $from->toDateString(),
            'to_date' => $to->toDateString(),
            'per_page' => min(max((int) $request->query('per_page', $defaults['per_page'] ?? 25), 10), 100),
        ];
    }

    private function dateOrDefault(mixed $value, string $default): CarbonImmutable
    {
        if (! is_string($value) || trim($value) === '') {
            return CarbonImmutable::parse($default);
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (\Throwable) {
            return CarbonImmutable::parse($default);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function filterOptions(Request $request): array
    {
        $user = $request->user();
        $companyId = (int) session('active_company_id', $user?->company_id);
        $branchId = $this->forcedBranchId($request);

        return [
            'branches' => Branch::query()
                ->where('company_id', $companyId)
                ->when($branchId, fn (Builder $query) => $query->whereKey($branchId))
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'users' => User::query()
                ->where('company_id', $companyId)
                ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
                ->orderBy('name')
                ->get(['id', 'name', 'username']),
            'lotteries' => Lottery::query()
                ->where(function (Builder $query) use ($companyId): void {
                    $query->where('company_id', $companyId)->orWhereNull('company_id');
                })
                ->orderBy('name')
                ->get(['id', 'name']),
            'draws' => Draw::query()
                ->where('company_id', $companyId)
                ->orderByDesc('draw_date')
                ->orderBy('scheduled_time')
                ->limit(200)
                ->get(['id', 'lottery_id', 'name', 'draw_date', 'scheduled_time']),
        ];
    }

    private function applyTicketFilters(Builder $query, array $filters): void
    {
        $query
            ->when($filters['branch_id'], fn (Builder $query, int $branchId) => $query->where('branch_id', $branchId))
            ->when($filters['user_id'], fn (Builder $query, int $userId) => $query->where('user_id', $userId))
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['lottery_id'] || $filters['draw_id'], function (Builder $query) use ($filters): void {
                $query->whereHas('details', function (Builder $detail) use ($filters): void {
                    $detail
                        ->when($filters['lottery_id'], fn (Builder $detail, int $lotteryId) => $detail->where('lottery_id', $lotteryId))
                        ->when($filters['draw_id'], fn (Builder $detail, int $drawId) => $detail->where('draw_id', $drawId));
                });
            });
    }

    private function applyDetailFilters(Builder $query, array $filters): void
    {
        $query
            ->when($filters['branch_id'], fn (Builder $query, int $branchId) => $query->where('branch_id', $branchId))
            ->when($filters['lottery_id'], fn (Builder $query, int $lotteryId) => $query->where('lottery_id', $lotteryId))
            ->when($filters['draw_id'], fn (Builder $query, int $drawId) => $query->where('draw_id', $drawId))
            ->when($filters['user_id'], fn (Builder $query, int $userId) => $query->whereHas('ticket', fn (Builder $ticket) => $ticket->where('user_id', $userId)));
    }

    private function applyCashFilters(Builder $query, array $filters): void
    {
        $query
            ->when($filters['branch_id'], fn (Builder $query, int $branchId) => $query->where('branch_id', $branchId))
            ->when($filters['user_id'], fn (Builder $query, int $userId) => $query->where('user_id', $userId))
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status));
    }

    private function applyWinnerFilters(Builder $query, array $filters): void
    {
        $query
            ->when($filters['branch_id'], fn (Builder $query, int $branchId) => $query->where('branch_id', $branchId))
            ->when($filters['lottery_id'], fn (Builder $query, int $lotteryId) => $query->where('lottery_id', $lotteryId))
            ->when($filters['draw_id'], fn (Builder $query, int $drawId) => $query->where('draw_id', $drawId))
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['user_id'], fn (Builder $query, int $userId) => $query->whereHas('ticket', fn (Builder $ticket) => $ticket->where('user_id', $userId)));
    }

    private function applyPrizePaymentFilters(Builder $query, array $filters): void
    {
        $query
            ->when($filters['branch_id'], fn (Builder $query, int $branchId) => $query->where('branch_id', $branchId))
            ->when($filters['user_id'], fn (Builder $query, int $userId) => $query->where('paid_by', $userId))
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['lottery_id'] || $filters['draw_id'], function (Builder $query) use ($filters): void {
                $query->whereHas('winnerTicket', function (Builder $winner) use ($filters): void {
                    $winner
                        ->when($filters['lottery_id'], fn (Builder $winner, int $lotteryId) => $winner->where('lottery_id', $lotteryId))
                        ->when($filters['draw_id'], fn (Builder $winner, int $drawId) => $winner->where('draw_id', $drawId));
                });
            });
    }

    private function forcedBranchId(Request $request): ?int
    {
        $user = $request->user();

        if (! $user?->branch_id) {
            return null;
        }

        if ($user->hasPermission('branches.view') || $user->hasPermission('companies.view')) {
            return null;
        }

        return (int) $user->branch_id;
    }
}
