<?php

declare(strict_types=1);

namespace App\Services\Licensing;

use App\DTO\Licensing\LicenseApiResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

class LicenseApiClient
{
    /**
     * @param array<string, mixed> $payload
     */
    public function activate(array $payload): LicenseApiResult
    {
        return $this->post('/activation/activate', $payload);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function validate(array $payload): LicenseApiResult
    {
        return $this->post('/activation/validate', $payload);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function post(string $path, array $payload): LicenseApiResult
    {
        try {
            $response = Http::baseUrl((string) config('licensing.api_base_url'))
                ->acceptJson()
                ->asJson()
                ->timeout((int) config('licensing.request_timeout_seconds'))
                ->retry(
                    (int) config('licensing.retry_attempts'),
                    (int) config('licensing.retry_sleep_milliseconds'),
                    throw: false
                )
                ->post($path, $payload);

            $json = $response->json();

            if (! is_array($json)) {
                return LicenseApiResult::fromTransportError('La API madre devolvió una respuesta no JSON.', $response->status());
            }

            return LicenseApiResult::fromPayload($json, $response->status());
        } catch (ConnectionException $exception) {
            return LicenseApiResult::fromTransportError($exception->getMessage());
        } catch (Throwable $exception) {
            return LicenseApiResult::fromTransportError($exception->getMessage());
        }
    }
}
