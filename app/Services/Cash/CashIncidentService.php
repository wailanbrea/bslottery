<?php

namespace App\Services\Cash;

use App\Models\CashIncident;
use App\Models\User;

class CashIncidentService
{
    public function resolve(CashIncident $incident, User $resolvedBy, string $notes): CashIncident
    {
        if ($incident->status !== 'OPEN') {
            throw new \RuntimeException('Solo se pueden resolver incidencias abiertas.');
        }

        $incident->update([
            'status' => 'RESOLVED',
            'resolved_by' => $resolvedBy->id,
            'resolved_at' => now(),
            'resolution_notes' => trim($notes),
        ]);

        return $incident;
    }

    /**
     * Desestima una incidencia: estado terminal pero NO descuenta del cajero
     * en la proxima nomina (PayrollService filtra por RESOLVED unicamente).
     * Util cuando el faltante no es imputable al cajero (ej. robo, error
     * administrativo, factura mal cobrada por terceros).
     */
    public function dismiss(CashIncident $incident, User $dismissedBy, string $reason): CashIncident
    {
        if ($incident->status !== 'OPEN') {
            throw new \RuntimeException('Solo se pueden desestimar incidencias abiertas.');
        }

        $incident->update([
            'status' => 'DISMISSED',
            'resolved_by' => $dismissedBy->id,
            'resolved_at' => now(),
            'resolution_notes' => trim($reason),
        ]);

        return $incident;
    }
}
