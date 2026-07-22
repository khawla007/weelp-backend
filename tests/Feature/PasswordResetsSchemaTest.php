<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PasswordResetsSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_resets_table_has_a_primary_key(): void
    {
        $hasPrimaryKey = collect(Schema::getIndexes('password_resets'))
            ->contains(fn (array $index): bool => $index['primary']);

        $this->assertTrue($hasPrimaryKey);
    }
}
