<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name', 100)->nullable()->after('name');
            $table->string('last_name', 100)->nullable()->after('first_name');
            $table->string('phone', 32)->nullable()->after('last_name');
            $table->timestamp('access_suspended_at')->nullable()->after('remember_token');
            $table->timestamp('invitation_sent_at')->nullable()->after('access_suspended_at');
            $table->timestamp('last_login_at')->nullable()->after('invitation_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'first_name',
                'last_name',
                'phone',
                'access_suspended_at',
                'invitation_sent_at',
                'last_login_at',
            ]);
        });
    }
};
