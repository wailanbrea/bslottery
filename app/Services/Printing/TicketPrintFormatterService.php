<?php

namespace App\Services\Printing;

use App\Models\BetType;
use App\Models\PrinterConfig;
use App\Models\PrintJob;
use App\Models\Ticket;
use Illuminate\Support\Collection;

class TicketPrintFormatterService
{
    public function format(
        Ticket $ticket,
        string $paperWidth = '58MM',
        bool $reprint = false,
        ?PrinterConfig $printerConfig = null,
    ): string {
        $settings = $this->settings($printerConfig, $paperWidth);
        $paperWidth = strtoupper($settings['paper_width']);

        return match ($paperWidth) {
            '80MM' => $this->format80mm($ticket, $reprint, $settings),
            '88MM' => $this->format88mm($ticket, $reprint, $settings),
            default => $this->format58mm($ticket, $reprint, $settings),
        };
    }

    public function format58mm(Ticket $ticket, bool $reprint = false, array $settings = []): string
    {
        $ticket = $this->loadRelations($ticket);
        $width = 32;
        $groups = $this->groupDetails($ticket);
        $lines = [];

        $this->appendStatusBanner($lines, $ticket, $reprint, $width);
        $this->appendCompanyHeader($lines, $ticket, $settings, $width, compactMode: true);
        $lines[] = $this->center('TICKET DE APUESTA', $width);
        $lines[] = $this->line($width);
        $this->appendTicketMeta58($lines, $ticket, $reprint, $width);
        $lines[] = $this->line($width, '=');

        foreach ($groups as $group) {
            $first = $group->first();
            $groupTitle = trim(($first->lottery->name ?? 'Lotería').' '.($first->draw->name ?? ''));
            $lines[] = $this->truncate($groupTitle, $width);
            $lines[] = $this->line($width);

            foreach ($group as $detail) {
                $left = sprintf(
                    '%-3s%-12s',
                    $this->betTypeShortCode($detail->betType),
                    $detail->number_value
                );
                $lines[] = $this->rightAmount($left, $this->money($detail->amount), $width);
            }

            $lines[] = $this->rightAmount('Subtotal', $this->money($this->groupSubtotal($group)), $width);

            if ($settings['show_potential_prize']) {
                $possiblePrize = $group->sum(fn ($detail): float => (float) $detail->possible_prize);
                $lines[] = $this->rightAmount('Premio pot.', $this->money($possiblePrize), $width);
            }

            $lines[] = '';
        }

        $lines[] = $this->line($width, '=');
        $lines[] = $this->rightAmount('Jugadas:', (string) $ticket->details->count(), $width);
        $lines[] = $this->rightAmount('TOTAL:', $this->money($ticket->total_amount), $width);
        $lines[] = $this->line($width, '=');

        $this->appendQrAndValidation($lines, $ticket, $settings, $width);
        $this->appendFooter($lines, $settings, $width, compact: true);
        $this->appendInvalidFooter($lines, $ticket, $width);
        $lines[] = '';
        $lines[] = '';

        return implode("\n", $this->trimTrailingBlanks($lines));
    }

    public function format80mm(Ticket $ticket, bool $reprint = false, array $settings = []): string
    {
        $ticket = $this->loadRelations($ticket);
        $width = 48;
        $groups = $this->groupDetails($ticket);
        $lines = [];

        $this->appendStatusBanner($lines, $ticket, $reprint, $width);
        $this->appendCompanyHeader($lines, $ticket, $settings, $width, compactMode: false);
        $lines[] = $this->center('TICKET DE APUESTA', $width);
        $lines[] = $this->line($width, '=');
        $this->appendTicketMetaWide($lines, $ticket, $reprint, $width, includeBranch: false);
        $lines[] = $this->line($width);

        foreach ($groups as $group) {
            $first = $group->first();
            $groupTitle = trim(($first->lottery->name ?? 'Lotería').' '.($first->draw->name ?? ''));
            $lines[] = $this->truncate($groupTitle, $width - 12).str_repeat(' ', max(1, $width - strlen($this->truncate($groupTitle, $width - 12)) - strlen($group->count().' jugadas'))).$group->count().' jugadas';
            $lines[] = $this->line($width);
            $lines[] = $settings['show_potential_prize']
                ? sprintf('%-14s %-10s %10s %10s', 'Tipo', 'Numero', 'Monto', 'Premio')
                : sprintf('%-16s %-12s %16s', 'Tipo', 'Numero', 'Monto');

            foreach ($group as $detail) {
                if ($settings['show_potential_prize']) {
                    $lines[] = sprintf(
                        '%-14s %-10s %10s %10s',
                        $this->truncate($this->betTypeLabel($detail->betType), 14),
                        $this->truncate($detail->number_value, 10),
                        $this->moneyCompact($detail->amount),
                        $this->moneyCompact($detail->possible_prize)
                    );
                } else {
                    $lines[] = sprintf(
                        '%-16s %-12s %16s',
                        $this->truncate($this->betTypeLabel($detail->betType), 16),
                        $this->truncate($detail->number_value, 12),
                        $this->moneyCompact($detail->amount)
                    );
                }
            }

            $lines[] = $this->line($width);
            $lines[] = $this->rightAmount('Subtotal '.($first->lottery->name ?? ''), $this->money($this->groupSubtotal($group)), $width);
            $lines[] = '';
        }

        $lines[] = $this->line($width, '=');
        $lines[] = $this->rightAmount('Cantidad de jugadas:', (string) $ticket->details->count(), $width);
        $lines[] = $this->rightAmount('Loterias:', (string) $groups->count(), $width);
        $lines[] = $this->rightAmount('TOTAL PAGADO:', $this->money($ticket->total_amount), $width);
        $lines[] = $this->line($width, '=');

        $this->appendQrAndValidation($lines, $ticket, $settings, $width);
        $this->appendFooter($lines, $settings, $width);
        $this->appendInvalidFooter($lines, $ticket, $width);
        $lines[] = '';
        $lines[] = '';

        return implode("\n", $this->trimTrailingBlanks($lines));
    }

    public function format88mm(Ticket $ticket, bool $reprint = false, array $settings = []): string
    {
        $ticket = $this->loadRelations($ticket);
        $width = 56;
        $groups = $this->groupDetails($ticket);
        $lines = [];

        $this->appendStatusBanner($lines, $ticket, $reprint, $width);
        $this->appendCompanyHeader($lines, $ticket, $settings, $width, compactMode: false);
        $lines[] = $this->center('TICKET DE APUESTA', $width);
        $lines[] = $this->line($width, '=');
        $this->appendTicketMetaWide($lines, $ticket, $reprint, $width, includeBranch: true);
        $lines[] = $this->line($width);

        foreach ($groups as $group) {
            $first = $group->first();
            $groupTitle = trim(($first->lottery->name ?? 'Lotería').' '.($first->draw->name ?? ''));
            $suffix = $group->count().' jugadas';
            $lines[] = $this->truncate($groupTitle, $width - 14).str_repeat(' ', max(1, $width - strlen($this->truncate($groupTitle, $width - 14)) - strlen($suffix))).$suffix;
            $lines[] = $this->line($width);
            $lines[] = $settings['show_potential_prize']
                ? sprintf('%-16s %-12s %12s %12s', 'Tipo', 'Numero', 'Apuesta', 'Premio')
                : sprintf('%-18s %-14s %20s', 'Tipo', 'Numero', 'Apuesta');

            foreach ($group as $detail) {
                if ($settings['show_potential_prize']) {
                    $lines[] = sprintf(
                        '%-16s %-12s %12s %12s',
                        $this->truncate($this->betTypeLabel($detail->betType), 16),
                        $this->truncate($detail->number_value, 12),
                        $this->moneyCompact($detail->amount),
                        $this->moneyCompact($detail->possible_prize)
                    );
                } else {
                    $lines[] = sprintf(
                        '%-18s %-14s %20s',
                        $this->truncate($this->betTypeLabel($detail->betType), 18),
                        $this->truncate($detail->number_value, 14),
                        $this->moneyCompact($detail->amount)
                    );
                }
            }

            $lines[] = $this->line($width);
            $lines[] = $this->rightAmount('Subtotal '.($first->lottery->name ?? ''), $this->money($this->groupSubtotal($group)), $width);
            $lines[] = '';
        }

        $lines[] = $this->line($width, '=');
        $lines[] = $this->rightAmount('Jugadas registradas:', (string) $ticket->details->count(), $width);
        $lines[] = $this->rightAmount('Loterias:', (string) $groups->count(), $width);
        $lines[] = $this->rightAmount('TOTAL PAGADO:', $this->money($ticket->total_amount), $width);
        $lines[] = $this->line($width, '=');

        $this->appendQrAndValidation($lines, $ticket, $settings, $width, includeLabel: true);
        $this->appendFooter($lines, $settings, $width);
        $this->appendInvalidFooter($lines, $ticket, $width);
        $lines[] = '';
        $lines[] = '';

        return implode("\n", $this->trimTrailingBlanks($lines));
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
        return 'RD$ '.number_format((float) $amount, 2);
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
            'PALE' => 'Palé',
            'TRIPLETA' => 'Tripleta',
            'SUPER_PALE' => 'Super Palé',
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

    public function publicTicketUrl(Ticket $ticket): string
    {
        return route('ticket.public.short', [
            'ticketNumber' => $ticket->ticket_number,
            'code' => $this->validationCode($ticket),
        ]);
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
            'device',
            'cashSession',
            'cancelledBy',
            'details.lottery',
            'details.draw',
            'details.betType',
            'printJobs',
        ]);
    }

    private function settings(?PrinterConfig $printerConfig, string $paperWidth): array
    {
        return [
            'paper_width' => strtoupper($printerConfig?->paper_width ?: $paperWidth ?: '58MM'),
            'show_logo' => (bool) ($printerConfig?->show_logo ?? false),
            'show_qr' => (bool) ($printerConfig?->show_qr ?? true),
            'show_phone' => (bool) ($printerConfig?->show_phone ?? true),
            'show_address' => (bool) ($printerConfig?->show_address ?? false),
            'show_potential_prize' => (bool) ($printerConfig?->show_potential_prize ?? false),
            'footer_text' => trim((string) ($printerConfig?->footer_text ?? '')),
            'open_cash_drawer' => (bool) ($printerConfig?->open_cash_drawer ?? false),
            'print_copies' => (int) ($printerConfig?->print_copies ?? 1),
        ];
    }

    private function appendStatusBanner(array &$lines, Ticket $ticket, bool $reprint, int $width): void
    {
        if ($reprint) {
            $lines[] = $this->line($width, '=');
            $lines[] = $this->center('*** REIMPRESION ***', $width);
            $reprintJob = $ticket->printJobs
                ->where('type', 'REPRINT')
                ->sortByDesc('created_at')
                ->first();

            if ($reprintJob) {
                $lines[] = $this->truncate('Reimpreso: '.optional($reprintJob->created_at)->format('d/m/Y h:i A'), $width);
            }

            $lines[] = $this->line($width, '=');
        }

        if ($ticket->status === 'CANCELLED') {
            $lines[] = $this->line($width, '=');
            $lines[] = $this->center('*** ANULADO ***', $width);
            $lines[] = $this->truncate('Fecha anul.: '.optional($ticket->cancelled_at)->format('d/m/Y h:i A'), $width);
            $lines[] = $this->truncate('Usuario: '.($ticket->cancelledBy?->name ?: $ticket->cancelledBy?->username ?: 'N/A'), $width);
            if ($ticket->cancel_reason) {
                foreach ($this->wrapText('Motivo: '.$ticket->cancel_reason, $width) as $line) {
                    $lines[] = $line;
                }
            }
            $lines[] = $this->line($width, '=');
        }
    }

    private function appendCompanyHeader(array &$lines, Ticket $ticket, array $settings, int $width, bool $compactMode): void
    {
        $company = $ticket->company;
        $branch = $ticket->branch;

        $lines[] = $this->line($width);
        $lines[] = $this->center($company?->name ?: 'BSLottery', $width);
        $lines[] = $this->center($branch?->name ?: 'Sucursal', $width);

        if (! $compactMode && $settings['show_address']) {
            $address = $branch?->address ?: $company?->address;
            if ($address) {
                foreach ($this->wrapText($address, $width) as $line) {
                    $lines[] = $this->center($line, $width);
                }
            }
        }

        if ($settings['show_phone']) {
            $phone = $branch?->phone ?: $company?->phone;
            if ($phone) {
                $lines[] = $this->center('Tel: '.$phone, $width);
            }
        }

        if ($compactMode && $company?->rnc) {
            $lines[] = $this->center('RNC: '.$company->rnc, $width);
        }

        $lines[] = $this->line($width);
    }

    private function appendTicketMeta58(array &$lines, Ticket $ticket, bool $reprint, int $width): void
    {
        $lines[] = $this->truncate('Ticket: '.$ticket->ticket_number, $width);
        $lines[] = $this->truncate('Fecha:  '.$ticket->sold_at?->format('d/m/Y h:i A'), $width);
        $lines[] = $this->truncate('Caja:   '.$this->terminalLabel($ticket), $width);
        $lines[] = $this->truncate('Cajero: '.$this->cashierName($ticket), $width);
        $lines[] = $this->truncate('Estado: '.$this->ticketStatusLabel($ticket, $reprint), $width);
    }

    private function appendTicketMetaWide(array &$lines, Ticket $ticket, bool $reprint, int $width, bool $includeBranch): void
    {
        $lines[] = $this->truncate('Ticket No.: '.$ticket->ticket_number, $width);
        $lines[] = $this->truncate('Fecha/Hora: '.$ticket->sold_at?->format('d/m/Y h:i:s A'), $width);
        $lines[] = $this->truncate('Cajero:     '.$this->cashierName($ticket), $width);
        $lines[] = $this->truncate('Terminal:   '.$this->terminalLabel($ticket), $width);

        if ($includeBranch) {
            $lines[] = $this->truncate('Sucursal:   '.($ticket->branch?->name ?: 'N/A'), $width);
        }

        $lines[] = $this->truncate('Estado:     '.$this->ticketStatusLabel($ticket, $reprint), $width);
    }

    private function appendQrAndValidation(array &$lines, Ticket $ticket, array $settings, int $width, bool $includeLabel = false): void
    {
        if ($settings['show_qr']) {
            $lines[] = '';
            $lines[] = '[[QR:'.$this->publicTicketUrl($ticket).']]';
        }

        if ($includeLabel) {
            $lines[] = 'Validacion: '.$ticket->ticket_number;
            $lines[] = 'Codigo:     '.$this->validationCode($ticket);
        } else {
            $lines[] = $this->center($ticket->ticket_number, $width);
            $lines[] = 'VAL: '.$this->validationCode($ticket);
        }
    }

    private function appendFooter(array &$lines, array $settings, int $width, bool $compact = false): void
    {
        $footerText = $settings['footer_text'] ?: (
            $compact
                ? "Conserve este ticket.\nPremios sujetos a validacion\ny reglas de la banca."
                : "Verifique sus jugadas antes de retirarse.\nConserve este ticket para reclamar premios.\nPremios sujetos a validacion y reglas vigentes."
        );

        $lines[] = '';
        foreach (preg_split('/\r\n|\r|\n/', $footerText) as $paragraph) {
            foreach ($this->wrapText(trim($paragraph), $width) as $line) {
                $lines[] = $compact ? $line : $this->center($line, $width);
            }
        }

        $lines[] = '';
        $lines[] = $this->center('GRACIAS POR JUGAR', $width);
    }

    private function appendInvalidFooter(array &$lines, Ticket $ticket, int $width): void
    {
        if ($ticket->status !== 'CANCELLED') {
            return;
        }

        $lines[] = $this->line($width, '=');
        $lines[] = $this->center('ESTE TICKET NO TIENE VALIDEZ', $width);
        $lines[] = $this->line($width, '=');
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

    private function groupSubtotal(Collection $details): float
    {
        return (float) $details->sum(fn ($detail): float => (float) $detail->amount);
    }

    private function cashierName(Ticket $ticket): string
    {
        return $ticket->user?->name ?: $ticket->user?->username ?: 'N/A';
    }

    private function terminalLabel(Ticket $ticket): string
    {
        if ($ticket->device?->name) {
            return $ticket->device->name;
        }

        if ($ticket->device?->device_name) {
            return $ticket->device->device_name;
        }

        return $ticket->cashSession ? 'Caja '.str_pad((string) $ticket->cashSession->id, 2, '0', STR_PAD_LEFT) : 'N/A';
    }

    private function moneyCompact(mixed $amount): string
    {
        return 'RD$ '.number_format((float) $amount, 2);
    }

    private function ticketStatusLabel(Ticket $ticket, bool $reprint = false): string
    {
        if ($ticket->status === 'CANCELLED') {
            return 'ANULADO';
        }

        if ($reprint) {
            return 'REIMPRESION';
        }

        return 'VALIDO';
    }

    /**
     * @return list<string>
     */
    private function wrapText(string $text, int $width): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        return preg_split('/\r\n|\r|\n/', wordwrap($text, $width, "\n", true)) ?: [$text];
    }

    /**
     * @param  list<string>  $lines
     * @return list<string>
     */
    private function trimTrailingBlanks(array $lines): array
    {
        while ($lines !== [] && trim((string) end($lines)) === '') {
            array_pop($lines);
        }

        return $lines;
    }
}
