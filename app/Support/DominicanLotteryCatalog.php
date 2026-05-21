<?php

declare(strict_types=1);

namespace App\Support;

class DominicanLotteryCatalog
{
    /**
     * Catalogo base de loterias dominicanas y extranjeras vendidas en RD.
     * Cada entrada es una loteria con UN horario; loterias con multiples sorteos
     * por dia se representan como entradas separadas (ej. ANGUILA-10AM, ANGUILA-1PM).
     *
     * El campo `time` es la hora de cierre operativo en zona horaria local de la empresa.
     *
     * Fuente: lista publica + horarios de Conectate.
     *
     * @var array<int, array{code: string, name: string, country: string, draw: string, time: string}>
     */
    private const ENTRIES = [
        ['code' => 'NAC-GANAMAS', 'name' => 'Gana Más', 'country' => 'DO', 'draw' => 'Gana Más 2:30 PM', 'time' => '14:30'],
        ['code' => 'LOTNAC', 'name' => 'Lotería Nacional', 'country' => 'DO', 'draw' => 'Nacional 9:00 PM', 'time' => '21:00'],
        ['code' => 'LEIDSA-QUINIELA', 'name' => 'Quiniela Leidsa', 'country' => 'DO', 'draw' => 'Leidsa 8:55 PM', 'time' => '20:55'],
        ['code' => 'LEIDSA-PEGA3', 'name' => 'Pega 3 Más', 'country' => 'DO', 'draw' => 'Pega 3 Más', 'time' => '20:55'],
        ['code' => 'LEIDSA-LOTOPOOL', 'name' => 'Loto Pool Leidsa', 'country' => 'DO', 'draw' => 'Loto Pool', 'time' => '20:55'],
        ['code' => 'LEIDSA-SUPERKINO', 'name' => 'Super Kino TV', 'country' => 'DO', 'draw' => 'Super Kino TV', 'time' => '20:55'],
        ['code' => 'LEIDSA-LOTO', 'name' => 'Loto - Loto Más', 'country' => 'DO', 'draw' => 'Loto - Loto Más', 'time' => '20:55'],
        ['code' => 'REAL-QUINIELA', 'name' => 'Quiniela Real', 'country' => 'DO', 'draw' => 'Real 12:55 PM', 'time' => '12:55'],
        ['code' => 'REAL-LOTOPOOL', 'name' => 'Loto Pool Real', 'country' => 'DO', 'draw' => 'Loto Pool Real', 'time' => '12:55'],
        ['code' => 'REAL-LOTO', 'name' => 'Loto Real', 'country' => 'DO', 'draw' => 'Loto Real', 'time' => '12:55'],
        ['code' => 'LOTEKA-QUINIELA', 'name' => 'Quiniela Loteka', 'country' => 'DO', 'draw' => 'Loteka 7:55 PM', 'time' => '19:55'],
        ['code' => 'LOTEKA-MEGACHANCES', 'name' => 'Mega Chances', 'country' => 'DO', 'draw' => 'Mega Chances', 'time' => '19:55'],
        ['code' => 'LOTEKA-MEGALOTTO', 'name' => 'Mega Lotto', 'country' => 'DO', 'draw' => 'Mega Lotto', 'time' => '19:55'],
        ['code' => 'PRIMERA-DIA', 'name' => 'La Primera Día', 'country' => 'DO', 'draw' => 'La Primera 12:00 PM', 'time' => '12:00'],
        ['code' => 'PRIMERA-NOCHE', 'name' => 'Primera Noche', 'country' => 'DO', 'draw' => 'La Primera 8:00 PM', 'time' => '20:00'],
        ['code' => 'PRIMERA-LOTO5', 'name' => 'Loto 5', 'country' => 'DO', 'draw' => 'Loto 5', 'time' => '20:00'],
        ['code' => 'SUERTE-MD', 'name' => 'La Suerte MD', 'country' => 'DO', 'draw' => 'La Suerte 12:30 PM', 'time' => '12:30'],
        ['code' => 'SUERTE-6PM', 'name' => 'La Suerte 6PM', 'country' => 'DO', 'draw' => 'La Suerte 6:00 PM', 'time' => '18:00'],
        ['code' => 'LOTEDOM', 'name' => 'LoteDom', 'country' => 'DO', 'draw' => 'LoteDom 1:55 PM', 'time' => '13:55'],
        ['code' => 'QUEMAITO-MAYOR', 'name' => 'El Quemaito Mayor', 'country' => 'DO', 'draw' => 'El Quemaito Mayor', 'time' => '13:55'],
        ['code' => 'KING-1230', 'name' => 'King Lottery 12:30', 'country' => 'DO', 'draw' => 'King Lottery 12:30', 'time' => '12:30'],
        ['code' => 'KING-730', 'name' => 'King Lottery 7:30', 'country' => 'DO', 'draw' => 'King Lottery 7:30', 'time' => '19:30'],
        ['code' => 'ANGUILA-10AM', 'name' => 'Anguila 10:00 AM', 'country' => 'AI', 'draw' => 'Anguila 10:00 AM', 'time' => '10:00'],
        ['code' => 'ANGUILA-1PM', 'name' => 'Anguila 1:00 PM', 'country' => 'AI', 'draw' => 'Anguila 1:00 PM', 'time' => '13:00'],
        ['code' => 'ANGUILA-6PM', 'name' => 'Anguila 6:00 PM', 'country' => 'AI', 'draw' => 'Anguila 6:00 PM', 'time' => '18:00'],
        ['code' => 'ANGUILA-9PM', 'name' => 'Anguila 9:00 PM', 'country' => 'AI', 'draw' => 'Anguila 9:00 PM', 'time' => '21:00'],
        ['code' => 'FLORIDA-DIA', 'name' => 'Florida Día', 'country' => 'US', 'draw' => 'Florida Día', 'time' => '13:30'],
        ['code' => 'FLORIDA-NOCHE', 'name' => 'Florida Noche', 'country' => 'US', 'draw' => 'Florida Noche', 'time' => '21:50'],
        ['code' => 'NY-330', 'name' => 'New York 3:30', 'country' => 'US', 'draw' => 'New York 3:30', 'time' => '15:30'],
        ['code' => 'NY-1130', 'name' => 'New York 11:30', 'country' => 'US', 'draw' => 'New York 11:30', 'time' => '23:30'],
        ['code' => 'MEGAMILLIONS', 'name' => 'Mega Millions', 'country' => 'US', 'draw' => 'Mega Millions', 'time' => '23:00'],
        ['code' => 'POWERBALL', 'name' => 'PowerBall', 'country' => 'US', 'draw' => 'PowerBall', 'time' => '22:59'],
    ];

    /**
     * Codigos de loterias antiguas que deben retirarse (status INACTIVE) si existen en BD.
     *
     * @var array<int, string>
     */
    private const RETIRED_CODES = ['NAC-NOCHE'];

    /**
     * @return array<int, array{code: string, name: string, country: string, draw: string, time: string}>
     */
    public static function entries(): array
    {
        return self::ENTRIES;
    }

    /**
     * @return array<int, string>
     */
    public static function retiredCodes(): array
    {
        return self::RETIRED_CODES;
    }

    /**
     * Busca una entrada del catalogo por su code; null si no existe.
     *
     * @return array{code: string, name: string, country: string, draw: string, time: string}|null
     */
    public static function findByCode(string $code): ?array
    {
        foreach (self::ENTRIES as $entry) {
            if ($entry['code'] === $code) {
                return $entry;
            }
        }

        return null;
    }
}
