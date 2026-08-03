<?php

namespace App\Providers;

use App\Contracts\StripePaymentIntentGateway;
use App\Services\StripePaymentIntentService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(StripePaymentIntentGateway::class, StripePaymentIntentService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production') && config('app.debug') === true) {
            throw new \RuntimeException('APP_DEBUG must be false in production.');
        }

        if ($this->app->environment('production')) {
            $origins = config('cors.allowed_origins', []);

            if (config('cors.supports_credentials') === true && empty($origins)) {
                throw new \RuntimeException(
                    'CORS supports_credentials=true requires explicit allowed_origins in production.'
                );
            }

            foreach ($origins as $origin) {
                if (trim((string) $origin) === '') {
                    throw new \RuntimeException('CORS allowed_origins contains empty entry in production.');
                }
                if ($origin === '*') {
                    throw new \RuntimeException('CORS allowed_origins cannot be wildcard in production.');
                }
                if (str_starts_with($origin, 'http://')) {
                    throw new \RuntimeException(
                        'CORS allowed_origins must use https:// in production: '.$origin
                    );
                }
            }

            $this->assertProductionMailConfiguration();
        }

        $trustedProxies = config('security.trusted_proxies');

        if ($this->app->environment('production') && empty($trustedProxies)) {
            throw new \RuntimeException(
                'TRUSTED_PROXIES must be a CIDR list (e.g. "10.0.0.0/8,192.168.0.0/16") '
                .'or "*" in production. Without it request->ip() and request->isSecure() '
                .'report the load balancer instead of the real client.'
            );
        }

        if (! empty($trustedProxies)) {
            $proxies = $trustedProxies === '*'
                ? '*'
                : array_map('trim', explode(',', $trustedProxies));

            TrustProxies::at($proxies);
            TrustProxies::withHeaders(
                Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_AWS_ELB,
            );
        }

        RateLimiter::for('login', function (Request $request) {
            $email = mb_strtolower(trim((string) $request->input('email')));

            return [
                Limit::perMinute(5)->by($email.'|'.$request->ip()),
                Limit::perMinute(20)->by($request->ip()),
            ];
        });

        RateLimiter::for('verify_email', function (Request $request) {
            $email = mb_strtolower(trim((string) $request->input('email', '')));
            $key = $email !== '' ? $email.'|'.$request->ip() : $request->ip();

            return Limit::perMinute(5)->by($key);
        });

        RateLimiter::for('support_requests', function (Request $request) {
            $emailInput = $request->input('email');
            $email = is_string($emailInput) ? mb_strtolower(trim($emailInput)) : '';

            return [
                Limit::perMinutes(10, 5)->by($email.'|'.$request->ip()),
                Limit::perMinutes(10, 20)->by($request->ip()),
            ];
        });

        RateLimiter::for('creator_explore_view', function (Request $request) {
            $itineraryId = (string) $request->route('id');

            return Limit::perMinute(10)->by($itineraryId.'|'.$request->ip());
        });

        Relation::enforceMorphMap([
            'activity' => \App\Models\Activity::class,
            'package' => \App\Models\Package::class,
            'itinerary' => \App\Models\Itinerary::class,
            'transfer' => \App\Models\Transfer::class,
            'city' => \App\Models\City::class,
            'place' => \App\Models\Place::class,
        ]);
    }

    private function assertProductionMailConfiguration(): void
    {
        $mailer = trim((string) config('mail.default'));
        $this->assertProductionMailerIsDeliverable($mailer);

        if (config('mail.support_address_configured') !== true) {
            throw new \RuntimeException(
                'SUPPORT_EMAIL must be explicitly configured in production.'
            );
        }

        if (! $this->isValidProductionEmail(config('mail.support_address'))) {
            throw new \RuntimeException(
                'SUPPORT_EMAIL must be a valid non-placeholder email address in production.'
            );
        }

        if (! $this->isValidProductionEmail(config('mail.from.address'))) {
            throw new \RuntimeException(
                'MAIL_FROM_ADDRESS must be a valid non-placeholder email address in production.'
            );
        }
    }

    /**
     * @param  list<string>  $visiting
     */
    private function assertProductionMailerIsDeliverable(
        string $mailer,
        array $visiting = [],
    ): void {
        $mailers = config('mail.mailers');

        if (
            $mailer === ''
            || in_array($mailer, $visiting, true)
            || ! is_array($mailers)
            || ! array_key_exists($mailer, $mailers)
            || ! is_array($mailers[$mailer])
        ) {
            $this->throwUnsafeProductionMailer();
        }

        $configuration = $mailers[$mailer];
        $transport = $configuration['transport'] ?? null;

        if (! is_string($transport) || trim($transport) === '') {
            $this->throwUnsafeProductionMailer();
        }

        $transport = trim($transport);

        if (in_array($transport, ['log', 'array'], true)) {
            $this->throwUnsafeProductionMailer();
        }

        if (in_array($transport, ['failover', 'roundrobin'], true)) {
            $members = $configuration['mailers'] ?? null;

            if (
                ! is_array($members)
                || $members === []
                || ! array_is_list($members)
            ) {
                $this->throwUnsafeProductionMailer();
            }

            $visiting[] = $mailer;

            foreach ($members as $member) {
                if (! is_string($member) || trim($member) === '') {
                    $this->throwUnsafeProductionMailer();
                }

                $this->assertProductionMailerIsDeliverable(
                    trim($member),
                    $visiting,
                );
            }

            return;
        }

        if (! in_array($transport, [
            'smtp',
            'sendmail',
            'mailgun',
            'ses',
            'ses-v2',
            'postmark',
            'resend',
        ], true)) {
            $this->throwUnsafeProductionMailer();
        }
    }

    private function throwUnsafeProductionMailer(): never
    {
        throw new \RuntimeException(
            'MAIL_MAILER must deliver real email in production.'
        );
    }

    private function isValidProductionEmail(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $address = trim($value);

        if (filter_var($address, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        $domain = mb_strtolower((string) strrchr($address, '@'));

        return ! in_array($domain, [
            '@example.com',
            '@example.net',
            '@example.org',
        ], true)
            && ! str_ends_with($domain, '.example')
            && ! str_ends_with($domain, '.invalid')
            && ! str_ends_with($domain, '.localhost')
            && ! str_ends_with($domain, '.test');
    }
}
