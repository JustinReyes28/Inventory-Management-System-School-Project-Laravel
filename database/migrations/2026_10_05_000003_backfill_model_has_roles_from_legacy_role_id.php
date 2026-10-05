<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfills Spatie role assignments from the legacy users.role_id column.
 *
 * Every existing user keeps their role: one model_has_roles row per user,
 * using the User model's actual morph type so Spatie's HasRoles relation
 * resolves the assignments without any morph-map surprises.
 */
return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $morphKey = config('permission.column_names')['model_morph_key'] ?? 'model_id';
        $modelType = (new User)->getMorphClass();

        $assignments = DB::table('users')
            ->whereNotNull('role_id')
            ->orderBy('id')
            ->get(['id', 'role_id'])
            ->map(fn (object $user): array => [
                'role_id' => (int) $user->role_id,
                $morphKey => (int) $user->id,
                'model_type' => $modelType,
            ])
            ->all();

        foreach (array_chunk($assignments, 250) as $chunk) {
            DB::table($tableNames['model_has_roles'])->insertOrIgnore($chunk);
        }

        $expected = count($assignments);
        $assigned = DB::table($tableNames['model_has_roles'])
            ->where('model_type', $modelType)
            ->whereIn('model_id', array_column($assignments, $morphKey))
            ->count();

        if ($assigned < $expected) {
            throw new \RuntimeException(
                "Role migration incomplete: expected {$expected} Spatie assignments from users.role_id, found {$assigned}."
            );
        }
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');
        $modelType = (new User)->getMorphClass();

        DB::table($tableNames['model_has_roles'])->where('model_type', $modelType)->delete();
    }
};
