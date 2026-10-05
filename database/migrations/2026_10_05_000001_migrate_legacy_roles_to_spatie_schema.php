<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Resolves the roles-table collision before the Spatie permission tables run.
 *
 * The legacy roles table (id, role_name) is migrated in place to the schema
 * spatie/laravel-permission requires (id, name, guard_name, timestamps),
 * preserving primary keys so existing users.role_id references stay valid.
 *
 * role_name is retained temporarily for legacy readers; a later migration
 * drops it once every reader has moved to Spatie.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            // role_name is retained only for legacy readers during the
            // conversion; it becomes optional for newly created roles.
            $table->string('role_name', 50)->nullable()->change();
            $table->string('name', 125)->nullable();
            $table->string('guard_name', 125)->nullable();
            $table->timestamps();
        });

        $now = now();
        DB::table('roles')
            ->orderBy('id')
            ->get()
            ->each(function (object $role) use ($now): void {
                DB::table('roles')->where('id', $role->id)->update([
                    'name' => $role->role_name,
                    'guard_name' => 'web',
                    'created_at' => $role->created_at ?? $now,
                    'updated_at' => $role->updated_at ?? $now,
                ]);
            });

        Schema::table('roles', function (Blueprint $table): void {
            $table->unique(['name', 'guard_name']);
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->dropUnique(['name', 'guard_name']);
            $table->dropColumn(['name', 'guard_name', 'created_at', 'updated_at']);
        });
    }
};
