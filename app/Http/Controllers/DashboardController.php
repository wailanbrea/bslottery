<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CashIncident;
use App\Models\CashSession;
use App\Models\Draw;
use App\Models\Result;
use App\Models\SystemNotification;
use App\Models\Ticket;
use App\Models\WinnerTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user       = $request->user();
        $companyId  = (int) session('active_company_id', $user->company_id);
        $branchId   = session('active_branch_id') ? (int) session('active_branch_id') : null;
        $today      = now()->toDateString();
        $yesterday  = now()->subDay()->toDateString();

        // --- KPIs operativos ---
        $ticketBase = fn () => Ticket::query()
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('status', '!=', 'CANCELLED');

        $salesToday     = (float) $ticketBase()->whereDate('sold_at', $today)->sum('total_amount');
        $salesYesterday = (float) $ticketBase()->whereDate('sold_at', $yesterday)->sum('total_amount');
        $ticketsToday   = $ticketBase()->whereDate('sold_at', $today)->count();
        $ticketsYest    = $ticketBase()->whereDate('sold_at', $yesterday)->count();

        $salesTrend   = $salesYesterday > 0 ? round((($salesToday - $salesYesterday) / $salesYesterday) * 100, 1) : null;
        $ticketsTrend = $ticketsYest > 0    ? round((($ticketsToday - $ticketsYest) / $ticketsYest) * 100, 1)     : null;

        $openDrawsCount = Draw::query()
            ->where('company_id', $companyId)
            ->where('status', 'OPEN')
            ->whereDate('draw_date', $today)
            ->count();

        $pendingPrizes = WinnerTicket::query()
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('status', 'RELEASED')
            ->count();

        $pendingResults = Result::query()
            ->where('company_id', $companyId)
            ->where('status', 'REGISTERED')
            ->where('registered_by', '!=', $user->id)
            ->count();

        $activeCash = CashSession::query()
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('user_id', $user->id)
            ->where('status', 'OPEN')
            ->first();

        $cancelledToday = Ticket::query()
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('status', 'CANCELLED')
            ->whereDate('cancelled_at', $today)
            ->count();

        $unreadAlerts = SystemNotification::query()
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('status', 'UNREAD')
            ->count();

        // Incidencias de caja abiertas (faltantes/sobrantes pendientes de revision
        // por el admin). Si no se resuelven, NO se descuentan al cajero en nomina.
        $openIncidentsBase = CashIncident::query()
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('status', 'OPEN');

        $openIncidentsCount = (clone $openIncidentsBase)->count();
        $openShortageAmount = (float) (clone $openIncidentsBase)->where('type', 'CASH_SHORTAGE')->sum('amount');

        // --- Gráfico ventas últimos 7 días ---
        $raw7Days = $ticketBase()
            ->whereBetween('sold_at', [now()->subDays(6)->startOfDay(), now()->endOfDay()])
            ->selectRaw('DATE(sold_at) as day, SUM(total_amount) as total, COUNT(*) as count')
            ->groupBy(DB::raw('DATE(sold_at)'))
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $chartLabels  = [];
        $chartAmounts = [];
        $chartCounts  = [];
        $days = collect(range(6, 0))->map(fn ($i) => now()->subDays($i)->toDateString());

        foreach ($days as $d) {
            $chartLabels[]  = now()->parse($d)->locale('es')->isoFormat('ddd D');
            $chartAmounts[] = round((float) ($raw7Days[$d]->total ?? 0), 2);
            $chartCounts[]  = (int) ($raw7Days[$d]->count ?? 0);
        }

        // --- Sorteos abiertos hoy ---
        $openDraws = Draw::query()
            ->with('lottery')
            ->where('company_id', $companyId)
            ->where('status', 'OPEN')
            ->whereDate('draw_date', $today)
            ->orderBy('close_time')
            ->take(12)
            ->get();

        // --- Últimos tickets ---
        $recentTickets = Ticket::query()
            ->with(['branch', 'user'])
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->latest('sold_at')
            ->take(8)
            ->get();

        // --- Alertas no leídas ---
        $alerts = SystemNotification::query()
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('status', 'UNREAD')
            ->orderByRaw("CASE severity WHEN 'CRITICAL' THEN 1 WHEN 'WARNING' THEN 2 ELSE 3 END")
            ->orderByDesc('created_at')
            ->take(6)
            ->get();

        // --- Top sucursales hoy (solo managers) ---
        $topBranches = collect();
        if ($user->hasPermission('branches.view') && ! $branchId) {
            $topBranches = Ticket::query()
                ->with('branch:id,name')
                ->where('company_id', $companyId)
                ->where('status', '!=', 'CANCELLED')
                ->whereDate('sold_at', $today)
                ->selectRaw('branch_id, SUM(total_amount) as total, COUNT(*) as count')
                ->groupBy('branch_id')
                ->orderByDesc('total')
                ->take(5)
                ->get();
        }

        return view('dashboard', compact(
            'user', 'companyId', 'branchId',
            'salesToday', 'salesYesterday', 'salesTrend',
            'ticketsToday', 'ticketsYest', 'ticketsTrend',
            'openDrawsCount', 'pendingPrizes', 'pendingResults',
            'activeCash', 'cancelledToday', 'unreadAlerts',
            'openIncidentsCount', 'openShortageAmount',
            'chartLabels', 'chartAmounts', 'chartCounts',
            'openDraws', 'recentTickets', 'alerts', 'topBranches',
        ));
    }
}
