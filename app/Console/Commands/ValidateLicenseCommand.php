<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Licensing\LicenseService;
use Illuminate\Console\Command;

class ValidateLicenseCommand extends Command
{
    protected $signature = 'license:validate';

    protected $description = 'Validate the local BSLotery license against the BSolutions licensing API.';

    public function handle(LicenseService $licenses): int
    {
        $result = $licenses->validateCurrent();

        if ($result->success && $result->valid) {
            $this->info($result->message ?: 'Licencia válida.');

            return self::SUCCESS;
        }

        $this->error(sprintf(
            '[%s] %s',
            $result->reasonCode ?: 'UNKNOWN',
            $result->message ?: $result->errorMessage ?: 'Licencia inválida.'
        ));

        return self::FAILURE;
    }
}
