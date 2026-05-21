<?php

declare(strict_types=1);

namespace App\DTO\Licensing;

final readonly class LicenseApiResult
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public bool $success,
        public bool $valid,
        public ?string $reasonCode,
        public ?string $message,
        public ?string $licenseKey,
        public ?string $status,
        public ?string $expiresAt,
        public ?string $serverTime,
        public array $client,
        public array $location,
        public array $features,
        public array $limits,
        public array $metadata,
        public array $payload,
        public ?int $httpStatus = null,
        public ?string $errorMessage = null,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromPayload(array $payload, ?int $httpStatus = null): self
    {
        return new self(
            success: (bool) ($payload['success'] ?? false),
            valid: (bool) ($payload['valid'] ?? false),
            reasonCode: isset($payload['reason_code']) ? (string) $payload['reason_code'] : null,
            message: isset($payload['message']) ? (string) $payload['message'] : null,
            licenseKey: isset($payload['license_key']) ? (string) $payload['license_key'] : null,
            status: isset($payload['status']) ? (string) $payload['status'] : null,
            expiresAt: isset($payload['expires_at']) ? (string) $payload['expires_at'] : null,
            serverTime: isset($payload['server_time']) ? (string) $payload['server_time'] : null,
            client: is_array($payload['client'] ?? null) ? $payload['client'] : [],
            location: is_array($payload['location'] ?? null) ? $payload['location'] : [],
            features: is_array($payload['features'] ?? null) ? $payload['features'] : [],
            limits: is_array($payload['limits'] ?? null) ? $payload['limits'] : [],
            metadata: is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
            payload: $payload,
            httpStatus: $httpStatus,
        );
    }

    public static function fromTransportError(string $message, ?int $httpStatus = null): self
    {
        return new self(
            success: false,
            valid: false,
            reasonCode: 'SERVER_ERROR',
            message: 'No fue posible validar la licencia contra la API madre.',
            licenseKey: null,
            status: null,
            expiresAt: null,
            serverTime: null,
            client: [],
            location: [],
            features: [],
            limits: [],
            metadata: [],
            payload: [],
            httpStatus: $httpStatus,
            errorMessage: $message,
        );
    }
}
