<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meter_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apartment_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->decimal('cold_m3', 12, 3);
            $table->decimal('hot_m3', 12, 3);
            $table->foreignId('recorded_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('entered_by_manager')->default(false);
            $table->timestamps();

            $table->unique(['apartment_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meter_readings');
    }
};
