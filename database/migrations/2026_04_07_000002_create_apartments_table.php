<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apartments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->string('number', 16);
            $table->timestamps();

            $table->unique(['building_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apartments');
    }
};
