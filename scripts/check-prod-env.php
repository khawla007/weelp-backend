<?php

/**
 * Production env template safety check.
 * Run via: composer run check:prod-env
 * Used as a deploy-time gate to ensure committed env templates never drift
 * to debug-on, dev-env, root DB user, empty DB password, or leaked secrets.
 *
 * TODO: promote to .github/workflows step once GitHub Actions is added.
 */

$root = __DIR__ . '/..';
$prodPath = $root . '/.env.production.example';
$examplePath = $root . '/.env.example';

if (! is_file($prodPath)) {
    fwrite(STDERR, "Missing .env.production.example\n");
    exit(1);
}

$prodContents = file_get_contents($prodPath);
$exampleContents = is_file($examplePath) ? file_get_contents($examplePath) : '';
$fail = 0;

if (! preg_match('/^APP_DEBUG=false/m', $prodContents)) {
    fwrite(STDERR, "APP_DEBUG must be false in prod template\n");
    $fail = 1;
}

if (! preg_match('/^APP_ENV=production/m', $prodContents)) {
    fwrite(STDERR, "APP_ENV must be production in prod template\n");
    $fail = 1;
}

if (preg_match('/re_[A-Za-z0-9_]{10,}|sk_live_[A-Za-z0-9]{20,}|whsec_[a-f0-9]{20,}/', $prodContents)) {
    fwrite(STDERR, "Real secret detected in prod template\n");
    $fail = 1;
}

foreach (['.env.production.example' => $prodContents, '.env.example' => $exampleContents] as $name => $contents) {
    if ($contents === '') {
        continue;
    }

    if (preg_match('/^DB_USERNAME=root\s*$/m', $contents)) {
        fwrite(STDERR, "DB_USERNAME=root forbidden in committed template: {$name}\n");
        $fail = 1;
    }

    if (preg_match('/^DB_PASSWORD=\s*$/m', $contents)) {
        fwrite(STDERR, "DB_PASSWORD must not be empty in committed template: {$name}\n");
        $fail = 1;
    }
}

exit($fail);
