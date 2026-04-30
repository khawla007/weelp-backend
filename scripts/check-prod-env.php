<?php

/**
 * Production env template safety check.
 * Run via: composer run check:prod-env
 * Used as a deploy-time gate to ensure .env.production.example never drifts
 * to debug-on, dev-env, or contains a real secret.
 */

$path = __DIR__ . '/../.env.production.example';

if (! is_file($path)) {
    fwrite(STDERR, "Missing .env.production.example\n");
    exit(1);
}

$contents = file_get_contents($path);
$fail = 0;

if (! preg_match('/^APP_DEBUG=false/m', $contents)) {
    fwrite(STDERR, "APP_DEBUG must be false in prod template\n");
    $fail = 1;
}

if (! preg_match('/^APP_ENV=production/m', $contents)) {
    fwrite(STDERR, "APP_ENV must be production in prod template\n");
    $fail = 1;
}

if (preg_match('/re_[A-Za-z0-9_]{10,}|sk_live_[A-Za-z0-9]{20,}|whsec_[a-f0-9]{20,}/', $contents)) {
    fwrite(STDERR, "Real secret detected in prod template\n");
    $fail = 1;
}

exit($fail);
