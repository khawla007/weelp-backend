<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Legacy rows hash the JWT with bcrypt (random salt) so they cannot be
        // looked up by deterministic hash. Drop them — affected users simply
        // resend verification. Tokens are 24h-scoped so blast radius is small.
        $purged = DB::table('email_verifications')->count();
        DB::table('email_verifications')->delete();
        if ($purged > 0) {
            Log::warning('email_verifications hardening migration purged legacy bcrypt rows', [
                'rows_deleted' => $purged,
            ]);
        }

        Schema::table('email_verifications', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('token');
            $table->timestamp('used_at')->nullable()->after('expires_at');
        });

        Schema::table('email_verifications', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable(false)->change();
            $table->unique('token', 'email_verifications_token_unique');
        });
    }

    public function down(): void
    {
        Schema::table('email_verifications', function (Blueprint $table) {
            $table->dropUnique('email_verifications_token_unique');
            $table->dropColumn(['expires_at', 'used_at']);
        });
    }
};
