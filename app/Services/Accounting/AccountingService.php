<?php

namespace App\Services\Accounting;

use App\Models\AccountingAccount;
use App\Models\Branch;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

class AccountingService
{
    /**
     * Crea un asiento contable automático.
     *
     * @param array<int, array{account_code: string, debit: float, credit: float, description?: string}> $lines
     */
    public function createEntry(int $companyId, ?int $branchId, string $description, array $lines, User $createdBy, ?string $sourceType = null, ?int $sourceId = null): JournalEntry
    {
        return DB::transaction(function () use ($companyId, $branchId, $description, $lines, $createdBy, $sourceType, $sourceId): JournalEntry {
            $entryNumber = $this->generateEntryNumber($companyId);

            $entry = JournalEntry::create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'entry_number' => $entryNumber,
                'entry_date' => now()->toDateString(),
                'description' => $description,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'status' => 'POSTED',
                'created_by' => $createdBy->id,
            ]);

            foreach ($lines as $line) {
                $account = AccountingAccount::where('company_id', $companyId)
                    ->where('code', $line['account_code'])
                    ->first();

                if (! $account) {
                    throw new \RuntimeException("Cuenta contable no encontrada: {$line['account_code']}");
                }

                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                    'account_id' => $account->id,
                    'debit' => Money::normalize($line['debit'] ?? 0),
                    'credit' => Money::normalize($line['credit'] ?? 0),
                    'description' => $line['description'] ?? null,
                ]);
            }

            return $entry;
        });
    }

    /**
     * Asiento de venta: Débito Caja sucursal / Crédito Ingresos por ventas.
     */
    public function entryForSale(int $companyId, int $branchId, int|float|string $amount, User $createdBy, ?int $ticketId = null): JournalEntry
    {
        $amount = Money::normalize($amount);

        return $this->createEntry(
            companyId: $companyId,
            branchId: $branchId,
            description: "Venta de tickets — RD\$ " . number_format($amount, 2),
            lines: [
                ['account_code' => '1100', 'debit' => $amount, 'credit' => 0, 'description' => 'Ingreso a caja por venta'],
                ['account_code' => '4000', 'debit' => 0, 'credit' => $amount, 'description' => 'Ingreso por venta de lotería'],
            ],
            createdBy: $createdBy,
            sourceType: $ticketId ? 'Ticket' : null,
            sourceId: $ticketId,
        );
    }

    /**
     * Asiento de pago de premio: Débito Premios pagados / Crédito Caja sucursal.
     */
    public function entryForPrizePayment(int $companyId, int $branchId, int|float|string $amount, User $createdBy, ?int $prizePaymentId = null): JournalEntry
    {
        $amount = Money::normalize($amount);

        return $this->createEntry(
            companyId: $companyId,
            branchId: $branchId,
            description: "Pago de premio — RD\$ " . number_format($amount, 2),
            lines: [
                ['account_code' => '5000', 'debit' => $amount, 'credit' => 0, 'description' => 'Premio pagado'],
                ['account_code' => '1100', 'debit' => 0, 'credit' => $amount, 'description' => 'Salida de caja por premio'],
            ],
            createdBy: $createdBy,
            sourceType: $prizePaymentId ? 'PrizePayment' : null,
            sourceId: $prizePaymentId,
        );
    }

    /**
     * Asiento de gasto: Débito Gastos operativos / Crédito Caja sucursal.
     */
    public function entryForExpense(int $companyId, int $branchId, int|float|string $amount, string $expenseDescription, User $createdBy, ?int $movementId = null): JournalEntry
    {
        $amount = Money::normalize($amount);

        return $this->createEntry(
            companyId: $companyId,
            branchId: $branchId,
            description: "Gasto — {$expenseDescription} — RD\$ " . number_format($amount, 2),
            lines: [
                ['account_code' => '5100', 'debit' => $amount, 'credit' => 0, 'description' => $expenseDescription],
                ['account_code' => '1100', 'debit' => 0, 'credit' => $amount, 'description' => 'Salida de caja por gasto'],
            ],
            createdBy: $createdBy,
            sourceType: $movementId ? 'CashMovement' : null,
            sourceId: $movementId,
        );
    }

    /**
     * Asiento de faltante confirmado: Débito Cuentas por cobrar / Crédito Caja.
     */
    public function entryForShortage(int $companyId, int $branchId, int|float|string $amount, User $createdBy, ?int $sessionId = null): JournalEntry
    {
        $amount = Money::normalize($amount);

        return $this->createEntry(
            companyId: $companyId,
            branchId: $branchId,
            description: "Faltante de caja confirmado — RD\$ " . number_format($amount, 2),
            lines: [
                ['account_code' => '1300', 'debit' => $amount, 'credit' => 0, 'description' => 'Cuenta por cobrar por faltante'],
                ['account_code' => '5300', 'debit' => 0, 'credit' => $amount, 'description' => 'Ajuste por faltante de caja'],
            ],
            createdBy: $createdBy,
            sourceType: $sessionId ? 'CashSession' : null,
            sourceId: $sessionId,
        );
    }

    /**
     * Asiento de nómina: Débito Nómina / Crédito Caja/Banco.
     */
    public function entryForPayroll(int $companyId, int $branchId, int|float|string $amount, User $createdBy, ?int $payrollId = null): JournalEntry
    {
        $amount = Money::normalize($amount);

        return $this->createEntry(
            companyId: $companyId,
            branchId: $branchId,
            description: "Pago de nómina — RD\$ " . number_format($amount, 2),
            lines: [
                ['account_code' => '5200', 'debit' => $amount, 'credit' => 0, 'description' => 'Gasto de nómina'],
                ['account_code' => '1100', 'debit' => 0, 'credit' => $amount, 'description' => 'Salida de caja por nómina'],
            ],
            createdBy: $createdBy,
            sourceType: $payrollId ? 'PayrollPeriod' : null,
            sourceId: $payrollId,
        );
    }

    private function generateEntryNumber(int $companyId): string
    {
        $prefix = now()->format('Ym');
        $count = JournalEntry::where('company_id', $companyId)
            ->where('entry_number', 'like', "JE-{$prefix}-%")
            ->count();

        return sprintf('JE-%s-%04d', $prefix, $count + 1);
    }
}
