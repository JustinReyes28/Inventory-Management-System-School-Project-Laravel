<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the missing created_at/updated_at columns across the domain tables and
 * backfills them so recorded history is preserved:
 *
 * - users, batches, activity_log and notifications already recorded
 *   created_at; those values are kept and copied into updated_at.
 * - categories had no timestamp columns at all and no historical creation
 *   times are available, so both columns are backfilled with the migration
 *   time (documented in README.md under "Schema changes").
 *
 * The Eloquent models switch to standard timestamp handling alongside this
 * migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('updated_at')->nullable();
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::table('batches', function (Blueprint $table): void {
            $table->timestamp('updated_at')->nullable();
        });

        Schema::table('activity_log', function (Blueprint $table): void {
            $table->timestamp('updated_at')->nullable();
        });

        Schema::table('notifications', function (Blueprint $table): void {
            $table->timestamp('updated_at')->nullable();
        });

        $now = now();
        DB::table('categories')->update(['created_at' => $now, 'updated_at' => $now]);

        foreach (['users', 'batches', 'activity_log', 'notifications'] as $table) {
            DB::table($table)->whereNotNull('created_at')->update(['updated_at' => DB::raw('created_at')]);
            DB::table($table)->whereNull('updated_at')->update(['updated_at' => $now]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('updated_at');
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->dropColumn(['created_at', 'updated_at']);
        });

        Schema::table('batches', function (Blueprint $table): void {
            $table->dropColumn('updated_at');
        });

        Schema::table('activity_log', function (Blueprint $table): void {
            $table->dropColumn('updated_at');
        });

        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropColumn('updated_at');
        });
    }
};
