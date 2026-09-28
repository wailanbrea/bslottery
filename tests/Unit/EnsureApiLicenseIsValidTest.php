<?php

namespace Tests\Unit;

use App\Http\Middleware\EnsureApiLicenseIsValid;
use App\Services\Licensing\LicenseService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Mockery;
use Tests\TestCase;

class EnsureApiLicenseIsValidTest extends TestCase
{
    public function test_it_allows_an_active_license(): void
    {
        $licenses = Mockery::mock(LicenseService::class);
        $licenses->expects('accessDecision')->andReturn([
            'allowed' => true,
            'mode' => 'online',
            'reason' => 'LICENSE_ACTIVE',
        ]);

        $response = (new EnsureApiLicenseIsValid($licenses))->handle(
            Request::create('/api/mobile/tickets', 'GET'),
            fn () => new Response('ok'),
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_it_returns_a_machine_readable_block_for_an_invalid_license(): void
    {
        $licenses = Mockery::mock(LicenseService::class);
        $licenses->expects('accessDecision')->andReturn([
            'allowed' => false,
            'mode' => 'blocked',
            'reason' => 'LICENSE_SUSPENDED',
        ]);

        $response = (new EnsureApiLicenseIsValid($licenses))->handle(
            Request::create('/api/mobile/tickets', 'GET'),
            fn () => new Response('should not execute'),
        );

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('LICENSE_SUSPENDED', $response->getData(true)['code']);
    }
}
