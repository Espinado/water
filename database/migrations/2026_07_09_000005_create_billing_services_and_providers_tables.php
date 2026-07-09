<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('services')) {
            Schema::create('services', function (Blueprint $table) {
                $table->string('code', 64)->primary();
                $table->string('name_ru');
                $table->string('name_lv')->nullable();
                $table->string('unit', 16);
                $table->string('calc_type', 32);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('service_providers')) {
            Schema::create('service_providers', function (Blueprint $table) {
                $table->id();
                $table->string('code', 64)->unique();
                $table->string('name');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('provider_service_rates')) {
            Schema::create('provider_service_rates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('service_provider_id')->constrained('service_providers')->cascadeOnDelete();
                $table->string('service_code', 64);
                $table->decimal('price', 10, 2);
                $table->timestamps();

                $table->foreign('service_code')->references('code')->on('services')->cascadeOnDelete();
                $table->unique(['service_provider_id', 'service_code']);
            });
        }

        if (! Schema::hasTable('building_service_providers')) {
            Schema::create('building_service_providers', function (Blueprint $table) {
                $table->foreignId('building_id')->constrained()->cascadeOnDelete();
                $table->string('service_code', 64);
                $table->foreignId('service_provider_id')->constrained('service_providers')->cascadeOnDelete();
                $table->timestamps();

                $table->foreign('service_code')->references('code')->on('services')->cascadeOnDelete();
                $table->primary(['building_id', 'service_code']);
            });
        }

        if (DB::table('services')->count() === 0) {
            $this->seedServiceCatalog();
        }

        if (Schema::hasTable('water_suppliers')) {
            if (DB::table('water_suppliers')->exists() && DB::table('service_providers')->count() === 0) {
                $this->migrateWaterSuppliers();
            }

            $this->dropWaterSupplierFromBuildings();
            Schema::drop('water_suppliers');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('building_service_providers');
        Schema::dropIfExists('provider_service_rates');
        Schema::dropIfExists('service_providers');
        Schema::dropIfExists('services');
    }

    protected function dropWaterSupplierFromBuildings(): void
    {
        if (! Schema::hasColumn('buildings', 'water_supplier_id')) {
            return;
        }

        $foreignKey = collect(DB::select(
            'SELECT CONSTRAINT_NAME AS name
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL
             LIMIT 1',
            ['buildings', 'water_supplier_id'],
        ))->value('name');

        Schema::table('buildings', function (Blueprint $table) use ($foreignKey) {
            if ($foreignKey !== null) {
                $table->dropForeign($foreignKey);
            }

            $table->dropColumn('water_supplier_id');
        });
    }

    protected function seedServiceCatalog(): void
    {
        $now = now();
        $rows = [
            ['management', 'Apsaimniekošanas izdevumi', 'Управление', 'm2', 'area', 1],
            ['utility_repair', 'Atskaitījumi komunālo tīklu remontiem', 'Ремонт коммунальных сетей', 'm2', 'area', 2],
            ['garbage', 'Atkritumu izvešana', 'Вывоз мусора', 'pcs', 'fixed', 3],
            ['door_lock', 'Durvju elektronisko slēdžu apkalpošana', 'Обслуживание дверных замков', 'pcs', 'fixed', 4],
            ['water_cold', 'Aukstā ūdens skaitītājs', 'Холодная вода', 'm3', 'meter_cold', 5],
            ['water_hot', 'Karstā ūdens skaitītājs', 'Горячая вода', 'm3', 'meter_hot', 6],
            ['water_heating', 'Ūdens uzsildīšana', 'Подогрев воды', 'm3', 'meter_hot_heating', 7],
            ['hot_water_circulation', 'Karstā ūdens cirkulācija', 'Циркуляция ГВС', 'pcs', 'fixed', 8],
            ['heat', 'Apkure', 'Отопление', 'm2', 'area', 9],
            ['water_sewage_correction', 'Ūdens un kanalizācijas izmaksu korekcija', 'Коррекция воды и канализации', 'pcs', 'correction', 10],
            ['common_area_repair', 'Koplietošanas telpu remonts', 'Ремонт общих помещений', 'pcs', 'fixed', 11],
            ['roof_repair', 'Jumta remonts un ūdens notekcaurules maiņa', 'Ремонт крыши и водостоков', 'pcs', 'fixed', 12],
            ['landscaping', 'Atskaitījumi teritorijas labiekārtošanai', 'Благоустройство территории', 'm2', 'area', 13],
            ['common_electricity', 'Koplietošanas elektroenerģija', 'Электроэнергия общих зон', 'pcs', 'fixed', 14],
        ];

        foreach ($rows as [$code, $nameLv, $nameRu, $unit, $calcType, $sort]) {
            DB::table('services')->insert([
                'code' => $code,
                'name_ru' => $nameRu,
                'name_lv' => $nameLv,
                'unit' => $unit,
                'calc_type' => $calcType,
                'sort_order' => $sort,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    protected function migrateWaterSuppliers(): void
    {
        $suppliers = DB::table('water_suppliers')->get();
        $supplierIdMap = [];

        foreach ($suppliers as $supplier) {
            $code = Str::upper(Str::slug($supplier->name, '_'));
            $baseCode = $code !== '' ? $code : 'SUPPLIER_'.$supplier->id;
            $uniqueCode = $baseCode;
            $suffix = 1;

            while (DB::table('service_providers')->where('code', $uniqueCode)->exists()) {
                $uniqueCode = $baseCode.'_'.$suffix;
                $suffix++;
            }

            $providerId = DB::table('service_providers')->insertGetId([
                'code' => $uniqueCode,
                'name' => $supplier->name,
                'created_at' => $supplier->created_at,
                'updated_at' => $supplier->updated_at,
            ]);

            $supplierIdMap[$supplier->id] = $providerId;

            DB::table('provider_service_rates')->insert([
                [
                    'service_provider_id' => $providerId,
                    'service_code' => 'water_cold',
                    'price' => $supplier->cold_price_per_m3,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'service_provider_id' => $providerId,
                    'service_code' => 'water_hot',
                    'price' => $supplier->hot_price_per_m3,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        $buildingLinks = DB::table('buildings')->whereNotNull('water_supplier_id')->get();

        foreach ($buildingLinks as $building) {
            $providerId = $supplierIdMap[$building->water_supplier_id] ?? null;

            if (! $providerId) {
                continue;
            }

            foreach (['water_cold', 'water_hot'] as $serviceCode) {
                DB::table('building_service_providers')->updateOrInsert(
                    [
                        'building_id' => $building->id,
                        'service_code' => $serviceCode,
                    ],
                    [
                        'service_provider_id' => $providerId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }
        }
    }
};
