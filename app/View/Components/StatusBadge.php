<?php

declare(strict_types=1);

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class StatusBadge extends Component
{
    public string $label;

    public string $cssClass;

    private const LABELS = [
        // Ticket
        'ACTIVE' => 'Activo',
        'WINNER' => 'Ganador',
        'LOSER' => 'Perdedor',
        'PARTIALLY_PAID' => 'Pago parcial',
        'PAID' => 'Pagado',
        'CANCELLED' => 'Cancelado',
        // Draw
        'OPEN' => 'Abierto',
        'CLOSING_SOON' => 'Por cerrar',
        'CLOSED' => 'Cerrado',
        'RESULT_PENDING' => 'Resultado pendiente',
        'RESULT_REGISTERED' => 'Resultado registrado',
        'RESULT_CONFIRMED' => 'Resultado confirmado',
        'CALCULATING_WINNERS' => 'Calculando ganadores',
        'WINNERS_CALCULATED' => 'Ganadores calculados',
        'PAYMENTS_RELEASED' => 'Pagos liberados',
        'FINALIZED' => 'Finalizado',
        // Result
        'DRAFT' => 'Borrador',
        'REGISTERED' => 'Registrado',
        'CONFIRMED' => 'Confirmado',
        // Winner ticket
        'PENDING_RELEASE' => 'Pendiente de liberación',
        'HELD' => 'Retenido',
        'RELEASED' => 'Liberado',
        // Cash session
        'REOPENED' => 'Reabierto',
        // Bank transfer
        'PENDING' => 'Pendiente',
        'REJECTED' => 'Rechazado',
        // Cash reconciliation
        'PENDING_REVIEW' => 'En revisión',
        'MATCHED' => 'Conciliado',
        // Cash incident
        'RESOLVED' => 'Resuelto',
        'DISMISSED' => 'Desestimada',
        // Company/Branch/User/etc
        'INACTIVE' => 'Inactivo',
        'SUSPENDED' => 'Suspendido',
        // Device
        'AUTHORIZED' => 'Autorizado',
        'BLOCKED' => 'Bloqueado',
        'REVOKED' => 'Revocado',
        // Notifications
        'UNREAD' => 'No leída',
        'READ' => 'Leída',
        // Severity
        'CRITICAL' => 'Crítico',
        'WARNING' => 'Advertencia',
        'INFO' => 'Información',
    ];

    private const CLASSES = [
        'ACTIVE' => 'bg-success',
        'OPEN' => 'bg-success',
        'CONFIRMED' => 'bg-success',
        'AUTHORIZED' => 'bg-success',
        'MATCHED' => 'bg-success',
        'RELEASED' => 'bg-success',
        'PAID' => 'bg-primary',
        'REGISTERED' => 'bg-primary',
        'RESULT_REGISTERED' => 'bg-primary',
        'RESULT_CONFIRMED' => 'bg-primary',
        'WINNERS_CALCULATED' => 'bg-primary',
        'PAYMENTS_RELEASED' => 'bg-primary',
        'WINNER' => 'bg-warning text-dark',
        'PARTIALLY_PAID' => 'bg-warning text-dark',
        'CLOSING_SOON' => 'bg-warning text-dark',
        'PENDING' => 'bg-warning text-dark',
        'PENDING_RELEASE' => 'bg-warning text-dark',
        'PENDING_REVIEW' => 'bg-warning text-dark',
        'RESULT_PENDING' => 'bg-info text-dark',
        'CALCULATING_WINNERS' => 'bg-info text-dark',
        'HELD' => 'bg-info text-dark',
        'DRAFT' => 'bg-info text-dark',
        'REOPENED' => 'bg-info text-dark',
        'UNREAD' => 'bg-info text-dark',
        'CLOSED' => 'bg-secondary',
        'FINALIZED' => 'bg-secondary',
        'INACTIVE' => 'bg-secondary',
        'LOSER' => 'bg-secondary',
        'READ' => 'bg-secondary',
        'CANCELLED' => 'bg-danger',
        'BLOCKED' => 'bg-danger',
        'REVOKED' => 'bg-danger',
        'REJECTED' => 'bg-danger',
        'SUSPENDED' => 'bg-danger',
        'CRITICAL' => 'bg-danger',
        'RESOLVED' => 'bg-success',
        'DISMISSED' => 'bg-secondary',
        'WARNING' => 'bg-warning text-dark',
        'INFO' => 'bg-info text-dark',
    ];

    public function __construct(public string $status)
    {
        $this->label = self::labelFor($status);
        $this->cssClass = self::cssClassFor($status);
    }

    public function render(): View|Closure|string
    {
        return view('components.status-badge');
    }

    /**
     * Traduce un codigo de status a su etiqueta en espanol. Util en dropdowns/forms.
     * Los nombres terminan en "For" para no colisionar con las properties publicas
     * `$label` / `$cssClass` que Blade extrae al renderizar el componente.
     */
    public static function labelFor(string $status): string
    {
        return self::LABELS[$status] ?? $status;
    }

    /** Clase CSS para el badge. */
    public static function cssClassFor(string $status): string
    {
        return self::CLASSES[$status] ?? 'bg-secondary';
    }
}
