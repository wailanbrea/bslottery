<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\BetType;
use App\Models\Branch;
use App\Models\LimitRule;
use App\Models\Lottery;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class LimitRulesImport implements ToCollection, WithHeadingRow
{
    private int $companyId;
    private int $createdBy;
    public int $imported = 0;
    public array $errors = [];

    public function __construct(int $companyId, int $createdBy)
    {
        $this->companyId = $companyId;
        $this->createdBy = $createdBy;
    }

    public function collection(Collection $rows): void
    {
        // Preload lookup tables for performance
        $branches  = Branch::where('company_id', $this->companyId)->get()->keyBy(fn ($b) => strtolower($b->name));
        $lotteries = Lottery::where('company_id', $this->companyId)->get()->keyBy(fn ($l) => strtolower($l->name));
        $betTypes  = BetType::where('company_id', $this->companyId)->get()->keyBy(fn ($b) => strtolower($b->code));

        foreach ($rows as $i => $row) {
            $rowNum = $i + 2; // +2 because row 1 is headings

            // Normalize row keys (WithHeadingRow gives snake_case of the header)
            $ruleType  = strtoupper(trim((string) ($row['tipo_globalsingle_numbernumber_rangenumber_list'] ?? $row->first() ?? '')));
            $branchName = strtolower(trim((string) ($row['sucursal_nombre_vaciotodasnull'] ?? $row[1] ?? '')));
            $lotteryName = strtolower(trim((string) ($row['loteria_nombre_vaciotodasnull'] ?? $row[2] ?? '')));
            $betTypeCode = strtolower(trim((string) ($row['jugada_codigo_vaciotodasnull'] ?? $row[3] ?? '')));
            $numberOrFrom = trim((string) ($row['numero_desde'] ?? $row[4] ?? ''));
            $numberTo     = trim((string) ($row['hasta_solo_rango'] ?? $row[5] ?? ''));
            $listRaw      = trim((string) ($row['lista_separada_por_comas'] ?? $row[6] ?? ''));
            $maxAmount    = $row['maximo_por_numero_rds'] ?? $row[7] ?? null;
            $policy       = strtoupper(trim((string) ($row['politica_block_fullallow_available'] ?? $row[8] ?? 'BLOCK_FULL')));
            $status       = strtoupper(trim((string) ($row['estado_activeinactive'] ?? $row[9] ?? 'ACTIVE')));

            if (! in_array($ruleType, ['GLOBAL', 'SINGLE_NUMBER', 'NUMBER_RANGE', 'NUMBER_LIST'], true)) {
                $this->errors[] = "Fila {$rowNum}: Tipo inválido '{$ruleType}'.";
                continue;
            }

            if (! in_array($policy, ['BLOCK_FULL', 'ALLOW_AVAILABLE'], true)) {
                $policy = 'BLOCK_FULL';
            }

            if (! in_array($status, ['ACTIVE', 'INACTIVE'], true)) {
                $status = 'ACTIVE';
            }

            $branchId  = null;
            $lotteryId = null;
            $betTypeId = null;

            if ($branchName !== '') {
                $branch = $branches->get($branchName);
                if (! $branch) {
                    $this->errors[] = "Fila {$rowNum}: Sucursal '{$branchName}' no encontrada.";
                    continue;
                }
                $branchId = $branch->id;
            }

            if ($lotteryName !== '') {
                $lottery = $lotteries->get($lotteryName);
                if (! $lottery) {
                    $this->errors[] = "Fila {$rowNum}: Lotería '{$lotteryName}' no encontrada.";
                    continue;
                }
                $lotteryId = $lottery->id;
            }

            if ($betTypeCode !== '') {
                $betType = $betTypes->get($betTypeCode);
                if (! $betType) {
                    $this->errors[] = "Fila {$rowNum}: Tipo de jugada '{$betTypeCode}' no encontrado.";
                    continue;
                }
                $betTypeId = $betType->id;
            }

            $data = [
                'company_id'           => $this->companyId,
                'branch_id'            => $branchId,
                'lottery_id'           => $lotteryId,
                'bet_type_id'          => $betTypeId,
                'rule_type'            => $ruleType,
                'max_amount_per_number'=> $maxAmount ?: null,
                'policy'               => $policy,
                'status'               => $status,
                'effective_from'       => now(),
                'created_by'           => $this->createdBy,
            ];

            switch ($ruleType) {
                case 'SINGLE_NUMBER':
                    if ($numberOrFrom === '') {
                        $this->errors[] = "Fila {$rowNum}: 'Número' requerido para tipo SINGLE_NUMBER.";
                        continue 2;
                    }
                    $data['number_value'] = $numberOrFrom;
                    break;

                case 'NUMBER_RANGE':
                    if ($numberOrFrom === '' || $numberTo === '') {
                        $this->errors[] = "Fila {$rowNum}: 'Número / Desde' y 'Hasta' requeridos para NUMBER_RANGE.";
                        continue 2;
                    }
                    $data['number_from'] = $numberOrFrom;
                    $data['number_to']   = $numberTo;
                    break;

                case 'NUMBER_LIST':
                    if ($listRaw === '') {
                        $this->errors[] = "Fila {$rowNum}: 'Lista' requerida para tipo NUMBER_LIST.";
                        continue 2;
                    }
                    $data['numbers_json'] = array_filter(array_map('trim', explode(',', $listRaw)));
                    break;
            }

            LimitRule::create($data);
            $this->imported++;
        }
    }
}
