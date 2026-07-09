<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('water_suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('cold_price_per_m3', 8, 2);
            $table->decimal('hot_price_per_m3', 8, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('water_suppliers');
    }
};
