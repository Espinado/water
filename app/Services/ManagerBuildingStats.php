<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Apartment;
use Illuminate\Support\Facades\DB;

class ManagerBuildingStats
{
    /**
     * @return array{
     *     total: int,
     *     submitted: int,
     *     debt: int,
     *     no_login: int,
     *     no_resident: int,
     *     total_area_m2: float
     * }
     */
    public function forBuilding(int $buildingId, int $year, int $month): array
    {
        $sub = DB::table('users')
            ->select('apartment_id', DB::raw('MIN(id) as resident_id'))
            ->where('role', UserRole::Resident->value)
            ->whereNotNull('apartment_id')
            ->groupBy('apartment_id');

        $rows = Apartment::query()
            ->where('apartments.building_id', $buildingId)
            ->leftJoin('meter_readings as mr_p', function ($j) use ($year, $month) {
                $j->on('mr_p.apartment_id', '=', 'apartments.id')
                    ->where('mr_p.year', '=', $year)
                    ->where('mr_p.month', '=', $month);
            })
            ->leftJoinSub($sub, 'pr', 'pr.apartment_id', '=', 'apartments.id')
            ->leftJoin('users', 'users.id', '=', 'pr.resident_id')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN mr_p.id IS NOT NULL THEN 1 ELSE 0 END) as submitted')
            ->selectRaw('SUM(CASE WHEN mr_p.id IS NULL THEN 1 ELSE 0 END) as debt')
            ->selectRaw('SUM(CASE WHEN users.id IS NOT NULL AND users.last_login_at IS NULL THEN 1 ELSE 0 END) as no_login')
            ->selectRaw('SUM(CASE WHEN users.id IS NULL THEN 1 ELSE 0 END) as no_resident')
            ->selectRaw('COALESCE(SUM(apartments.area_m2), 0) as total_area_m2')
            ->first();

        return [
            'total' => (int) ($rows->total ?? 0),
            'submitted' => (int) ($rows->submitted ?? 0),
            'debt' => (int) ($rows->debt ?? 0),
            'no_login' => (int) ($rows->no_login ?? 0),
            'no_resident' => (int) ($rows->no_resident ?? 0),
            'total_area_m2' => (float) ($rows->total_area_m2 ?? 0),
        ];
    }
}
