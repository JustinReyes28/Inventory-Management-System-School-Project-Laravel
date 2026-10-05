<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Removes the obsolete legacy role columns.
 *
 * Safe from this point on: every reader and writer has moved to
 * spatie/laravel-permission, and the migrated assignments in model_has_roles
 * were verified (one row per user, IDs preserved) before this runs.
 *
 * - users.role_id      -> model_has_roles (Spatie assignments)
 * - roles.role_name    -> roles.name (Spatie schema)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_role_name_index');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('role_id');
        });

        Schema::table('roles', function (Blueprint $table): void {
            $table->dropUnique(['role_name']);
        });

        Schema::table('roles', function (Blueprint $table): void {
            $table->dropColumn('role_name');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->string('role_name', 50)->nullable();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('role_id')->nullable()->constrained('roles')->restrictOnDelete();
            $table->index(['role_id', 'full_name'], 'users_role_name_index');
        });
    }
};
