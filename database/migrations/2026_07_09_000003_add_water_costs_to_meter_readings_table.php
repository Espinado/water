<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meter_readings', function (Blueprint $table) {
            $table->decimal('cold_consumption_m3', 10, 3)->nullable()->after('hot_m3');
            $table->decimal('hot_consumption_m3', 10, 3)->nullable()->after('cold_consumption_m3');
            $table->decimal('cold_price_per_m3', 8, 2)->nullable()->after('hot_consumption_m3');
            $table->decimal('hot_price_per_m3', 8, 2)->nullable()->after('cold_price_per_m3');
            $table->decimal('cold_cost', 10, 2)->nullable()->after('hot_price_per_m3');
            $table->decimal('hot_cost', 10, 2)->nullable()->after('cold_cost');
            $table->decimal('total_water_cost', 10, 2)->nullable()->after('hot_cost');
        });
    }

    public function down(): void
    {
        Schema::table('meter_readings', function (Blueprint $table) {
            $table->dropColumn([
                'cold_consumption_m3',
                'hot_consumption_m3',
                'cold_price_per_m3',
                'hot_price_per_m3',
                'cold_cost',
                'hot_cost',
                'total_water_cost',
            ]);
        });
    }
};
