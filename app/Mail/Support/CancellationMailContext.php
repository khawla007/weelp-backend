<?php

namespace App\Mail\Support;

use App\Models\CancellationRequest;
use Illuminate\Support\Carbon;

final class CancellationMailContext
{
    private const SAFE_FALLBACK_ORIGIN = 'https://weelp.netlify.app';

    /** @return array<string, string> */
    public static function forCustomer(CancellationRequest $request): array
    {
        return self::context($request, '/dashboard/customer?order='.$request->order_id);
    }

    /** @return array<string, string> */
    public static function forAdmin(CancellationRequest $request): array
    {
        return self::context($request, '/dashboard/admin/orders?order='.$request->order_id);
    }

    /** @return array<string, string> */
    private static function context(CancellationRequest $request, string $path): array
    {
        $request->loadMissing(['order', 'customer']);
        $travelStartsAt = $request->travel_starts_at;

        return [
            'actionUrl' => self::frontendOrigin().$path,
            'customerName' => self::markdownLiteral((string) $request->customer?->name),
            'customerEmail' => self::markdownLiteral((string) $request->customer?->email),
            'itemName' => self::markdownLiteral(self::itemName($request)),
            'reason' => self::markdownLiteral((string) $request->reason),
            'decisionExplanation' => self::markdownLiteral((string) $request->decision_explanation),
            'failureCode' => self::markdownLiteral(
                (string) ($request->failure_code ?: 'local_confirmation_failed'),
            ),
            'failureSummary' => self::markdownLiteral(
                (string) ($request->failure_summary ?: 'The refund could not be confirmed locally.'),
            ),
            'travelDate' => $travelStartsAt instanceof Carbon
                ? $travelStartsAt->format('F j, Y')
                : 'Not available',
            'preferredTime' => $travelStartsAt instanceof Carbon
                ? $travelStartsAt->format('g:i A')
                : 'Not available',
        ];
    }

    private static function itemName(CancellationRequest $request): string
    {
        $snapshot = $request->order?->item_snapshot_json;
        if (is_string($snapshot)) {
            $snapshot = json_decode($snapshot, true);
        }

        $name = is_array($snapshot) ? ($snapshot['name'] ?? null) : null;

        return is_string($name) && trim($name) !== ''
            ? trim($name)
            : "Booking #{$request->order_id}";
    }

    private static function frontendOrigin(): string
    {
        return self::normalizeOrigin(config('app.frontend_url'))
            ?? self::normalizeOrigin(config('app.frontend_fallback_url'))
            ?? self::SAFE_FALLBACK_ORIGIN;
    }

    private static function normalizeOrigin(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);
        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $parts = parse_url($value);
        if (! is_array($parts)) {
            return null;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = $parts['path'] ?? '';
        if (! in_array($scheme, ['http', 'https'], true)
            || $host === ''
            || ! in_array($path, ['', '/'], true)
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['query'])
            || isset($parts['fragment'])) {
            return null;
        }

        $port = $parts['port'] ?? null;
        $defaultPort = $scheme === 'http' ? 80 : 443;
        $portSuffix = is_int($port) && $port !== $defaultPort ? ":{$port}" : '';

        return "{$scheme}://{$host}{$portSuffix}";
    }

    private static function markdownLiteral(string $value): string
    {
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? '';

        return preg_replace('/([!"#$%&\'()*+,\-.\/:;=?@\[\\\\\]^_`{|}~])/u', '\\\\$1', $value)
            ?? '';
    }
}
