<?php

namespace App\Services\Printing;

use App\Models\BetType;
use App\Models\Ticket;
use Illuminate\Support\Collection;

class TicketPrintFormatterService
{
    public function format(Ticket $ticket, string $paperWidth = '58MM', bool $reprint = false): string
    {
        return match (strtoupper($paperWidth)) {
            '88MM', '80MM' => $this->format88mm($ticket, $reprint),
            default => $this->format58mm($ticket, $reprint),
        };
    }

    public function format58mm(Ticket $ticket, bool $reprint = false): string
    {
        $ticket = $this->loadRelations($ticket);
        $width = 32;
        $lines = [];

        if ($reprint) {
            $lines[] = $this->line($width);
            $lines[] = $this->center('REIMPRESION', $width);
            $lines[] = $this->line($width);
        }

        if ($ticket->status === 'CANCELLED') {
            $lines[] = $this->line($width);
            $lines[] = $this->center('ANULADO', $width);
            if ($ticket->cancel_reason) {
                $lines[] = $this->truncate('Motivo: '.$ticket->cancel_reason, $width);
            }
            $lines[] = $this->line($width);
        }

        $company = $ticket->company;
        $branch = $ticket->branch;

        $lines[] = $this->line($width);
        $lines[] = $this->center($company?->name ?: 'BSLottery', $width);
        if ($company?->legal_name && $company->legal_name !== $company->name) {
            $lines[] = $this->center($company->legal_name, $width);
        }
        $lines[] = $this->center('Sucursal: '.($branch?->name ?: 'N/A'), $width);
        if ($branch?->phone || $company?->phone) {
            $lines[] = $this->center('Tel: '.($branch?->phone ?: $company?->phone), $width);
        }
        if ($company?->rnc) {
            $lines[] = $this->center('RNC: '.$company->rnc, $width);
        }
        $lines[] = $this->line($width);
        $lines[] = $this->truncate('Ticket: '.$ticket->ticket_number, $width);
        $lines[] = $this->truncate('Fecha:  '.$ticket->sold_at?->format('d/m/Y h:i A'), $width);
        $lines[] = $this->truncate('Cajero: '.$this->cashierName($ticket), $width);
        $lines[] = $this->truncate('Caja:   '.$this->cashSessionLabel($ticket), $width);
        $lines[] = $this->truncate('Estado: '.$ticket->status, $width);
        $lines[] = $this->line($width);

        foreach ($this->groupDetails($ticket) as $details) {
            $firstDetail = $details->first();
            $lines[] = $this->truncate('LOTERIA: '.$firstDetail->lottery->name, $width);
            $lines[] = $this->truncate('Sorteo: '.$firstDetail->draw->name, $width);

            foreach ($details as $detail) {
                $left = sprintf(
                    '%-3s%-12s',
                    $this->betTypeShortCode($detail->betType),
                    $detail->number_value
                );
                $lines[] = $this->rightAmount($left, $this->money($detail->amount), $width);
            }

            $possiblePrize = $details->sum(fn ($detail): float => (float) $detail->possible_prize);
            $lines[] = $this->rightAmount('Premio Posible:', $this->money($possiblePrize), $width);
            $lines[] = $this->line($width);
        }

        $lines[] = $this->rightAmount('Subtotal', $this->money($ticket->total_amount), $width);
        $lines[] = $this->rightAmount('Descuento', $this->money(0), $width);
        $lines[] = $this->line($width, '=');
        $lines[] = $this->rightAmount('TOTAL', $this->money($ticket->total_amount), $width);
        $lines[] = $this->line($width, '=');
        $lines[] = $this->center('Revise su ticket antes', $width);
        $lines[] = $this->center('de retirarse', $width);
        $lines[] = $this->center('No se aceptan devoluciones', $width);
        $lines[] = $this->center('Conserve este ticket', $width);
        $lines[] = $this->center('Gracias por su preferencia', $width);
        $lines[] = '';
        $lines[] = '[[QR:'.$this->publicTicketUrl($ticket).']]';
        $lines[] = 'VAL: '.$this->validationCode($ticket);
        $lines[] = $this->line($width);
        $lines[] = '';
        $lines[] = '';

        return implode("\n", $lines);
    }

    public function format88mm(Ticket $ticket, bool $reprint = false): string
    {
        $ticket = $this->loadRelations($ticket);
        $width = 56;
        $lines = [];

        if ($reprint) {
            $lines[] = $this->line($width);
            $lines[] = $this->center('REIMPRESION', $width);
            $lines[] = $this->line($width);
        }

        if ($ticket->status === 'CANCELLED') {
            $lines[] = $this->line($width);
            $lines[] = $this->center('ANULADO', $width);
            if ($ticket->cancel_reason) {
                $lines[] = $this->truncate('Motivo: '.$ticket->cancel_reason, $width);
            }
            $lines[] = $this->line($width);
        }

        $company = $ticket->company;
        $branch = $ticket->branch;

        $lines[] = $this->line($width);
        $lines[] = $this->center($company?->name ?: 'BSLottery', $width);
        $lines[] = $this->center($company?->legal_name ?: $branch?->name ?: 'Banca', $width);
        $lines[] = $this->center($branch?->name ?: 'Sucursal', $width);
        if ($branch?->address || $company?->address) {
            $lines[] = $this->center($branch?->address ?: $company?->address, $width);
        }
        if ($branch?->phone || $company?->phone) {
            $lines[] = $this->center('Tel: '.($branch?->phone ?: $company?->phone), $width);
        }
        $lines[] = $this->line($width);
        $lines[] = $this->truncate('Ticket:  '.$ticket->ticket_number, $width);
        $lines[] = $this->truncate('Fecha:   '.$ticket->sold_at?->format('d/m/Y h:i A'), $width);
        $lines[] = $this->truncate('Cajero:  '.$this->cashierName($ticket), $width);
        $lines[] = $this->truncate('Caja:    '.$this->cashSessionLabel($ticket), $width);
        $lines[] = 'Cliente: General';
        $lines[] = $this->truncate('Estado:  '.$ticket->status, $width);
        $lines[] = $this->line($width);
        $lines[] = 'Loteria    Sorteo   Tipo       Jugada   Monto    Premio';
        $lines[] = $this->line($width);

        foreach ($this->groupDetails($ticket) as $details) {
            foreach ($details as $detail) {
                $lines[] = sprintf(
                    '%-10s %-8s %-10s %-8s %7s %9s',
                    $this->truncate($detail->lottery->name, 10),
                    $this->truncate($detail->draw->name, 8),
                    $this->truncate($this->betTypeLabel($detail->betType), 10),
                    $this->truncate($detail->number_value, 8),
                    $this->moneyCompact($detail->amount),
                    $this->moneyCompact($detail->possible_prize)
                );
            }
        }

        $lines[] = $this->line($width);
        $lines[] = 'Jugadas: '.$ticket->details->count();
        $lines[] = $this->rightAmount('Total Apostado:', $this->money($ticket->total_amount), $width);
        $lines[] = $this->rightAmount('Premio Posible Total:', $this->money($ticket->total_possible_prize), $width);
        $lines[] = $this->line($width);
        $lines[] = '';
        $lines[] = 'Firma / Sello';
        $lines[] = '______________________________';
        $lines[] = '';
        $lines[] = '[[QR:'.$this->publicTicketUrl($ticket).']]';
        $lines[] = 'VAL: '.$this->validationCode($ticket);
        $lines[] = '';
        $lines[] = $this->center('Revise su ticket antes de retirarse', $width);
        $lines[] = $this->center('No valido sin sello del sistema', $width);
        $lines[] = $this->center('Pagos sujetos a confirmacion de resultados', $width);
        $lines[] = $this->center('Gracias por usar BSLottery', $width);
        $lines[] = $this->line($width);
        $lines[] = '';
        $lines[] = '';

        return implode("\n", $lines);
    }

    public function center(string $text, int $width): string
    {
        $text = $this->truncate($text, $width);
        $padding = max(0, $width - strlen($text));

        return str_repeat(' ', intdiv($padding, 2)).$text.str_repeat(' ', $padding - intdiv($padding, 2));
    }

    public function line(int $width, string $char = '-'): string
    {
        return str_repeat(substr($char, 0, 1), $width);
    }

    public function money(mixed $amount): string
    {
        return 'RD$'.number_format((float) $amount, 2);
    }

    public function rightAmount(string $label, string $amount, int $width): string
    {
        $label = $this->truncate($label, max(0, $width - strlen($amount) - 1));
        $spaces = max(1, $width - strlen($label) - strlen($amount));

        return $label.str_repeat(' ', $spaces).$amount;
    }

    public function truncate(string $text, int $length): string
    {
        $text = trim($text);

        return strlen($text) > $length ? substr($text, 0, $length) : $text;
    }

    public function betTypeLabel(BetType $betType): string
    {
        return match (strtoupper($betType->code)) {
            'QUINIELA' => 'Quiniela',
            'PALE' => 'Pale',
            'TRIPLETA' => 'Tripleta',
            'SUPER_PALE' => 'SuperPale',
            default => $betType->name,
        };
    }

    public function betTypeShortCode(BetType $betType): string
    {
        return match (strtoupper($betType->code)) {
            'QUINIELA' => 'Q',
            'PALE' => 'P',
            'TRIPLETA' => 'T',
            'SUPER_PALE' => 'SP',
            default => strtoupper(substr($betType->code, 0, 3)),
        };
    }

    /**
     * URL publica de consulta del ticket (sin auth). El QR del ticket impreso codifica
     * esta URL para que el cliente la escanee con cualquier cel y vea el ticket.
     */
    public function publicTicketUrl(Ticket $ticket): string
    {
        return route('ticket.public', ['uuid' => $ticket->uuid]);
    }

    public function validationCode(Ticket $ticket): string
    {
        $hash = substr(preg_replace('/[^A-Z0-9]/', '', strtoupper(hash('crc32b', $ticket->uuid.$ticket->ticket_number))), 0, 8);

        return substr($hash, 0, 4).'-'.substr($hash, 4, 4);
    }

    private function loadRelations(Ticket $ticket): Ticket
    {
        return $ticket->loadMissing([
            'company',
            'branch',
            'user',
            'cashSession',
            'details.lottery',
            'details.draw',
            'details.betType',
            'printJobs',
        ]);
    }

    /**
     * @return Collection<int, Collection<int, \App\Models\TicketDetail>>
     */
    private function groupDetails(Ticket $ticket): Collection
    {
        return $ticket->details
            ->sortBy([
                ['lottery.name', 'asc'],
                ['draw.name', 'asc'],
                ['id', 'asc'],
            ])
            ->groupBy(fn ($detail): string => $detail->lottery_id.'-'.$detail->draw_id);
    }

    private function cashierName(Ticket $ticket): string
    {
        return $ticket->user?->name ?: $ticket->user?->username ?: 'N/A';
    }

    private function cashSessionLabel(Ticket $ticket): string
    {
        return $ticket->cashSession ? str_pad((string) $ticket->cashSession->id, 2, '0', STR_PAD_LEFT) : 'N/A';
    }

    private function moneyCompact(mixed $amount): string
    {
        $value = (float) $amount;

        return 'RD$'.number_format($value, $value === floor($value) ? 0 : 2);
    }
}
