<?php

namespace Tests\Feature;

use App\Support\SafeHttp;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SafeHttpTest extends TestCase
{
    public function test_rejects_loopback_literal(): void
    {
        $this->expectException(\DomainException::class);
        SafeHttp::fetch('http://127.0.0.1/file.csv');
    }

    public function test_rejects_aws_imds(): void
    {
        $this->expectException(\DomainException::class);
        SafeHttp::fetch('http://169.254.169.254/latest/meta-data/iam/security-credentials/role');
    }

    public function test_rejects_rfc1918_ten_dot(): void
    {
        $this->expectException(\DomainException::class);
        SafeHttp::fetch('http://10.0.0.5/file.csv');
    }

    public function test_allows_public_url(): void
    {
        Http::fake([
            '1.2.3.4/*' => Http::response('col1,col2\nA,B', 200),
        ]);

        $response = SafeHttp::fetch('http://1.2.3.4/file.csv');
        $this->assertSame(200, $response->status());
        $this->assertStringContainsString('col1,col2', $response->body());
    }

    public function test_rejects_redirect_to_internal(): void
    {
        Http::fake([
            '1.2.3.4/*' => Http::response('', 302, ['Location' => 'http://127.0.0.1/internal']),
        ]);

        $this->expectException(\DomainException::class);
        SafeHttp::fetch('http://1.2.3.4/file.csv');
    }

    public function test_rejects_oversize_response(): void
    {
        Http::fake([
            '1.2.3.4/*' => Http::response('small', 200, ['Content-Length' => (string) (11 * 1024 * 1024)]),
        ]);

        $this->expectException(\DomainException::class);
        SafeHttp::fetch('http://1.2.3.4/big.csv');
    }

    public function test_rejects_non_http_scheme(): void
    {
        $this->expectException(\DomainException::class);
        SafeHttp::fetch('file:///etc/passwd');
    }

    public function test_is_blocked_ip_helper(): void
    {
        $this->assertTrue(SafeHttp::isBlockedIp('127.0.0.1'));
        $this->assertTrue(SafeHttp::isBlockedIp('10.0.0.1'));
        $this->assertTrue(SafeHttp::isBlockedIp('172.16.0.1'));
        $this->assertTrue(SafeHttp::isBlockedIp('192.168.1.1'));
        $this->assertTrue(SafeHttp::isBlockedIp('169.254.169.254'));
        $this->assertTrue(SafeHttp::isBlockedIp('0.0.0.0'));
        $this->assertTrue(SafeHttp::isBlockedIp('100.64.0.1'));
        $this->assertTrue(SafeHttp::isBlockedIp('224.0.0.1'));
        $this->assertTrue(SafeHttp::isBlockedIp('255.255.255.255'));
        $this->assertTrue(SafeHttp::isBlockedIp('::1'));
        $this->assertTrue(SafeHttp::isBlockedIp('fe80::1'));
        $this->assertTrue(SafeHttp::isBlockedIp('fc00::1'));
        $this->assertTrue(SafeHttp::isBlockedIp('::ffff:127.0.0.1'));
        $this->assertFalse(SafeHttp::isBlockedIp('8.8.8.8'));
        $this->assertFalse(SafeHttp::isBlockedIp('1.2.3.4'));
    }
}
