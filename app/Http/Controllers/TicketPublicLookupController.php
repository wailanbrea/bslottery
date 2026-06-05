<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Result;
use App\Models\Ticket;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class TicketPublicLookupController extends Controller
{
    /**
     * Vista publica de un ticket por UUID. Sin login.
     * Solo expone informacion no sensible: banca, sucursal, numeros jugados,
     * estado del sorteo y ganadores (si los hay). NO muestra cajero, dispositivo,
     * cash session ni datos administrativos.
     */
    public function show(string $uuid): View|Response
    {
        $ticket = Ticket::query()
            ->with([
                'company:id,name,legal_name',
                'branch:id,company_id,name,address,phone',
                'details.lottery:id,name',
                'details.draw:id,name,draw_date,scheduled_time,status',
                'details.betType:id,name,code',
                'winnerTickets.ticketDetail.lottery:id,name',
                'winnerTickets.ticketDetail.draw:id,name',
            ])
            ->where('uuid', $uuid)
            ->first();

        if (! $ticket) {
            return response()->view('public.ticket.not_found', [], 404);
        }

        $drawIds = $ticket->details->pluck('draw_id')->unique()->all();
        $results = Result::query()
            ->whereIn('draw_id', $drawIds)
            ->get()
            ->keyBy('draw_id');

        $groupedDetails = $ticket->details->groupBy('draw_id')->map(function (Collection $details, int $drawId) use ($results) {
            $first = $details->first();
            $result = $results->get($drawId);

            return [
                'draw' => $first->draw,
                'lottery' => $first->lottery,
                'jugadas' => $details->map(fn ($d) => [
                    'detail_id' => $d->id,
                    'numero' => $d->number_value,
                    'tipo' => $d->betType?->name ?? '—',
                    'codigo' => $d->betType?->code ?? '',
                    'monto' => (string) $d->amount,
                    'posible_premio' => (string) $d->possible_prize,
                ])->values(),
                'resultado' => $result ? [
                    'primero' => $result->first_number,
                    'segundo' => $result->second_number,
                    'tercero' => $result->third_number,
                    'fecha_confirmacion' => $result->confirmed_at?->format('d/m/Y H:i'),
                ] : null,
            ];
        })->values();

        $winnersByDetail = $ticket->winnerTickets->groupBy('ticket_detail_id');

        return view('public.ticket.show', [
            'ticket' => $ticket,
            'company' => $ticket->company,
            'branch' => $ticket->branch,
            'groupedDetails' => $groupedDetails,
            'winnersByDetail' => $winnersByDetail,
            'totalGanado' => $ticket->winnerTickets->sum('prize_amount'),
            'hayGanadores' => $ticket->winnerTickets->isNotEmpty(),
        ]);
    }

    public function showByTicketNumber(string $ticketNumber, string $code): View|Response
    {
        $ticket = Ticket::query()
            ->with([
                'company:id,name,legal_name',
                'branch:id,company_id,name,address,phone',
                'details.lottery:id,name',
                'details.draw:id,name,draw_date,scheduled_time,status',
                'details.betType:id,name,code',
                'winnerTickets.ticketDetail.lottery:id,name',
                'winnerTickets.ticketDetail.draw:id,name',
            ])
            ->where('ticket_number', $ticketNumber)
            ->first();

        if (! $ticket || ! hash_equals($this->validationCode($ticket), strtoupper($code))) {
            return response()->view('public.ticket.not_found', [], 404);
        }

        return $this->show($ticket->uuid);
    }

    private function validationCode(Ticket $ticket): string
    {
        $hash = substr(preg_replace('/[^A-Z0-9]/', '', strtoupper(hash('crc32b', $ticket->uuid.$ticket->ticket_number))), 0, 8);

        return substr($hash, 0, 4).'-'.substr($hash, 4, 4);
    }
}
