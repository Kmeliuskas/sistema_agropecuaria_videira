<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_id')->nullable()->after('id');
            $table->boolean('is_active')->default(true)->after('password');
            $table->string('avatar')->nullable()->after('is_active');
            $table->timestamp('last_login_at')->nullable()->after('avatar');
            $table->timestamp('password_changed_at')->nullable()->after('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['employee_id', 'is_active', 'avatar', 'last_login_at', 'password_changed_at']);
        });
    }
};
