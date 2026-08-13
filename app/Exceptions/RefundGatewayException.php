<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;
use Throwable;

final class RefundGatewayException extends RuntimeException
{
    private function __construct(
        public readonly string $failureDisposition,
        public readonly string $failureCode,
        public readonly string $safeSummary,
        public readonly string $operatorMessage,
        ?Throwable $previous = null,
    ) {
        parent::__construct($safeSummary, 0, $previous);
    }

    public static function definitive(
        string $code,
        string $providerMessage = '',
        ?Throwable $previous = null,
    ): self {
        return new self(
            'definitive',
            self::safeCode($code),
            'The refund provider rejected this refund.',
            $providerMessage,
            $previous,
        );
    }

    public static function indeterminate(
        string $code,
        string $providerMessage = '',
        ?Throwable $previous = null,
    ): self {
        return new self(
            'indeterminate',
            self::safeCode($code),
            'The refund provider did not confirm whether the refund succeeded.',
            $providerMessage,
            $previous,
        );
    }

    private static function safeCode(string $code): string
    {
        $normalized = preg_replace('/[^a-z0-9_\-]/', '_', strtolower(trim($code))) ?? '';

        return mb_substr($normalized !== '' ? $normalized : 'provider_error', 0, 100);
    }
}
