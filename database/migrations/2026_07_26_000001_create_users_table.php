<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('full_name', 100);
            $table->string('username', 50)->unique();
            $table->string('password_hash', 255);
            $table->rememberToken();
            $table->foreignId('role_id')->constrained('roles')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['role_id', 'full_name'], 'users_role_name_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
