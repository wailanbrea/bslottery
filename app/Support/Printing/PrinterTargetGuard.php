<?php

declare(strict_types=1);

namespace App\Support\Printing;

final class PrinterTargetGuard
{
    /**
     * @var list<string>
     */
    private const VIRTUAL_NAME_FRAGMENTS = [
        'ANYDESK',
        'PRINT TO PDF',
        'MICROSOFT XPS',
        'FAX',
        'ONENOTE',
        'PDF',
        'XPS DOCUMENT WRITER',
    ];

    public static function isAllowedForConnector(?string $printerName): bool
    {
        if ($printerName === null || trim($printerName) === '') {
            return false;
        }

        $normalized = mb_strtoupper(trim($printerName));

        foreach (self::VIRTUAL_NAME_FRAGMENTS as $fragment) {
            if (str_contains($normalized, $fragment)) {
                return false;
            }
        }

        return true;
    }

    public static function connectorValidationMessage(): string
    {
        return 'La impresora seleccionada no es válida para BSolutions Print Connector. Elige una impresora física o un puerto COM, no una impresora virtual como AnyDesk, PDF, XPS o Fax.';
    }
}
