<?php

declare(strict_types=1);

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    /** @param Collection<int, array<mixed>> $rows */
    public function __construct(
        private readonly array $headers,
        private readonly Collection $rows,
    ) {}

    /** @return Collection<int, array<mixed>> */
    public function collection(): Collection
    {
        return $this->rows;
    }

    /** @return array<string> */
    public function headings(): array
    {
        return $this->headers;
    }

    /** @return array<mixed> */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '344767'],
                ],
            ],
        ];
    }
}
