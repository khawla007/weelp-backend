<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class ProductionMailConfigurationTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     */
    #[DataProvider('unsafeMailConfigurationProvider')]
    public function test_production_boot_rejects_unsafe_mail_configuration(
        array $overrides,
        string $expectedMessage,
    ): void {
        $this->setProductionPrerequisites($overrides);
        $originalEnvironment = $this->app->environment();

        try {
            $this->app->detectEnvironment(fn (): string => 'production');

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage($expectedMessage);

            (new AppServiceProvider($this->app))->boot();
        } finally {
            $this->app->detectEnvironment(
                fn (): string => $originalEnvironment,
            );
        }
    }

    public function test_production_boot_accepts_explicit_valid_smtp_mail_configuration(): void
    {
        $this->setProductionPrerequisites();
        $originalEnvironment = $this->app->environment();

        try {
            $this->app->detectEnvironment(fn (): string => 'production');

            (new AppServiceProvider($this->app))->boot();

            $this->addToAssertionCount(1);
        } finally {
            $this->app->detectEnvironment(
                fn (): string => $originalEnvironment,
            );
        }
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    #[DataProvider('safeAggregateMailerProvider')]
    public function test_production_boot_accepts_aggregates_with_only_delivery_mailers(
        array $overrides,
    ): void {
        $this->setProductionPrerequisites($overrides);
        $originalEnvironment = $this->app->environment();

        try {
            $this->app->detectEnvironment(fn (): string => 'production');

            (new AppServiceProvider($this->app))->boot();

            $this->addToAssertionCount(1);
        } finally {
            $this->app->detectEnvironment(
                fn (): string => $originalEnvironment,
            );
        }
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function unsafeMailConfigurationProvider(): iterable
    {
        yield 'log mailer' => [
            ['mail.default' => 'log'],
            'MAIL_MAILER must deliver real email in production.',
        ];
        yield 'array mailer' => [
            ['mail.default' => 'array'],
            'MAIL_MAILER must deliver real email in production.',
        ];
        yield 'failover containing log mailer' => [
            ['mail.default' => 'failover'],
            'MAIL_MAILER must deliver real email in production.',
        ];
        yield 'unknown selected mailer' => [
            ['mail.default' => 'missing-mailer'],
            'MAIL_MAILER must deliver real email in production.',
        ];
        yield 'nested unsafe aggregate' => [
            [
                'mail.default' => 'outer-safe-looking',
                'mail.mailers.outer-safe-looking' => [
                    'transport' => 'failover',
                    'mailers' => ['smtp', 'nested-unsafe'],
                ],
                'mail.mailers.nested-unsafe' => [
                    'transport' => 'roundrobin',
                    'mailers' => ['postmark', 'array'],
                ],
            ],
            'MAIL_MAILER must deliver real email in production.',
        ];
        yield 'aggregate cycle' => [
            [
                'mail.default' => 'cycle-a',
                'mail.mailers.cycle-a' => [
                    'transport' => 'failover',
                    'mailers' => ['cycle-b'],
                ],
                'mail.mailers.cycle-b' => [
                    'transport' => 'roundrobin',
                    'mailers' => ['cycle-a'],
                ],
            ],
            'MAIL_MAILER must deliver real email in production.',
        ];
        yield 'aggregate with empty mailer list' => [
            [
                'mail.default' => 'empty-aggregate',
                'mail.mailers.empty-aggregate' => [
                    'transport' => 'failover',
                    'mailers' => [],
                ],
            ],
            'MAIL_MAILER must deliver real email in production.',
        ];
        yield 'aggregate with malformed mailer list' => [
            [
                'mail.default' => 'malformed-aggregate',
                'mail.mailers.malformed-aggregate' => [
                    'transport' => 'roundrobin',
                    'mailers' => ['primary' => 'smtp'],
                ],
            ],
            'MAIL_MAILER must deliver real email in production.',
        ];
        yield 'aggregate with unknown member' => [
            [
                'mail.default' => 'unknown-member-aggregate',
                'mail.mailers.unknown-member-aggregate' => [
                    'transport' => 'failover',
                    'mailers' => ['smtp', 'missing-member'],
                ],
            ],
            'MAIL_MAILER must deliver real email in production.',
        ];
        yield 'support email absent' => [
            ['mail.support_address_configured' => false],
            'SUPPORT_EMAIL must be explicitly configured in production.',
        ];
        yield 'support email blank' => [
            [
                'mail.support_address_configured' => true,
                'mail.support_address' => ' ',
            ],
            'SUPPORT_EMAIL must be a valid non-placeholder email address in production.',
        ];
        yield 'support email invalid' => [
            ['mail.support_address' => 'not-an-email'],
            'SUPPORT_EMAIL must be a valid non-placeholder email address in production.',
        ];
        yield 'support email placeholder' => [
            ['mail.support_address' => 'support@example.com'],
            'SUPPORT_EMAIL must be a valid non-placeholder email address in production.',
        ];
        yield 'from address invalid' => [
            ['mail.from.address' => 'not-an-email'],
            'MAIL_FROM_ADDRESS must be a valid non-placeholder email address in production.',
        ];
        yield 'from address placeholder' => [
            ['mail.from.address' => 'hello@example.com'],
            'MAIL_FROM_ADDRESS must be a valid non-placeholder email address in production.',
        ];
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function safeAggregateMailerProvider(): iterable
    {
        yield 'safe failover' => [[
            'mail.default' => 'safe-failover',
            'mail.mailers.safe-failover' => [
                'transport' => 'failover',
                'mailers' => ['smtp', 'postmark'],
            ],
        ]];
        yield 'safe round robin' => [[
            'mail.default' => 'safe-roundrobin',
            'mail.mailers.safe-roundrobin' => [
                'transport' => 'roundrobin',
                'mailers' => ['ses', 'resend'],
            ],
        ]];
        yield 'safe nested aggregates' => [[
            'mail.default' => 'safe-nested-failover',
            'mail.mailers.safe-nested-failover' => [
                'transport' => 'failover',
                'mailers' => ['smtp', 'safe-nested-roundrobin'],
            ],
            'mail.mailers.safe-nested-roundrobin' => [
                'transport' => 'roundrobin',
                'mailers' => ['postmark', 'resend'],
            ],
        ]];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function setProductionPrerequisites(array $overrides = []): void
    {
        config()->set(array_merge([
            'app.debug' => false,
            'cors.allowed_origins' => ['https://weelp.test'],
            'cors.supports_credentials' => true,
            'security.trusted_proxies' => '10.0.0.0/8',
            'mail.default' => 'smtp',
            'mail.support_address_configured' => true,
            'mail.support_address' => 'support@weelp.com',
            'mail.from.address' => 'hello@weelp.com',
        ], $overrides));
    }
}
