<?php

return [
    'version' => 'general-v1',
    'fixture_enabled' => env('CANCELLATION_FIXTURES_ENABLED', false),
    'fixture_database' => env('CANCELLATION_FIXTURE_DATABASE', 'weelp_local_cancellation'),
    'fixture_actual_database_override' => null,
    'reason_min' => 10,
    'reason_max' => 1000,
    'refund_processing_stale_after_seconds' => 300,

    'bands' => [
        ['minimum_seconds' => 30 * 24 * 60 * 60, 'deduction_percentage' => 10],
        ['minimum_seconds' => 15 * 24 * 60 * 60, 'deduction_percentage' => 25],
        ['minimum_seconds' => 7 * 24 * 60 * 60, 'deduction_percentage' => 50],
        ['minimum_seconds' => 48 * 60 * 60, 'deduction_percentage' => 75],
        ['minimum_seconds' => 0, 'deduction_percentage' => 100],
    ],
];
