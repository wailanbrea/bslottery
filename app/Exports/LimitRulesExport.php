<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\LimitRule;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LimitRulesExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    public function __construct(private readonly Collection $rules) {}

    public function collection(): Collection
    {
        return $this->rules->map(fn (LimitRule $r) => [
            $r->rule_type,
            $r->branch?->name ?? '',
            $r->lottery?->name ?? '',
            $r->betType?->code ?? '',
            match ($r->rule_type) {
                'SINGLE_NUMBER' => $r->number_value ?? '',
                'NUMBER_RANGE'  => $r->number_from ?? '',
                default         => '',
            },
            $r->rule_type === 'NUMBER_RANGE' ? ($r->number_to ?? '') : '',
            $r->rule_type === 'NUMBER_LIST'  ? implode(',', (array) $r->numbers_json) : '',
            $r->max_amount_per_number ?? '',
            $r->policy ?? 'BLOCK_FULL',
            $r->status,
        ]);
    }

    public function headings(): array
    {
        return [
            'Tipo (GLOBAL|SINGLE_NUMBER|NUMBER_RANGE|NUMBER_LIST)',
            'Sucursal (nombre, vacío=todas)',
            'Lotería (nombre, vacío=todas)',
            'Jugada (código, vacío=todas)',
            'Número / Desde',
            'Hasta (solo rango)',
            'Lista (separada por comas)',
            'Máximo por número (RD$)',
            'Política (BLOCK_FULL|ALLOW_AVAILABLE)',
            'Estado (ACTIVE|INACTIVE)',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '344767']],
            ],
        ];
    }
}
