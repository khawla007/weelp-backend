<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    protected function tearDown(): void
    {
        TrustProxies::flushState();
        parent::tearDown();
    }

    public function test_security_headers_emitted_on_health_endpoint(): void
    {
        $response = $this->get('/up');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'no-referrer');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->assertHeader('Cross-Origin-Resource-Policy', 'same-origin');
        $response->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');
        $response->assertHeader(
            'Content-Security-Policy',
            "default-src 'none'; frame-ancestors 'none'; base-uri 'none'"
        );
    }

    public function test_security_headers_emitted_on_api_endpoint(): void
    {
        $response = $this->getJson('/api/customer/profile');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Content-Security-Policy');
    }

    public function test_hsts_header_omitted_on_insecure_request(): void
    {
        $response = $this->get('/up');

        $this->assertFalse(
            $response->headers->has('Strict-Transport-Security'),
            'HSTS must not be sent on plaintext requests.'
        );
    }

    public function test_hsts_header_emitted_on_secure_request(): void
    {
        $response = $this->get('https://localhost/up');

        $response->assertHeader(
            'Strict-Transport-Security',
            'max-age=63072000; includeSubDomains'
        );
    }

    public function test_trust_proxies_honors_x_forwarded_for_when_configured(): void
    {
        TrustProxies::at('*');
        TrustProxies::withHeaders(
            Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PROTO
        );

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->withHeaders([
                'X-Forwarded-For' => '203.0.113.7',
                'X-Forwarded-Proto' => 'https',
            ])
            ->get('/up');

        $request = request();

        $this->assertSame('203.0.113.7', $request->ip());
        $this->assertTrue($request->isSecure());
    }
}
