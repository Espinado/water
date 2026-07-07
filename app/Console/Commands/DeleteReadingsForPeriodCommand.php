<?php

namespace App\Console\Commands;

use App\Models\MeterReading;
use Illuminate\Console\Command;

class DeleteReadingsForPeriodCommand extends Command
{
    protected $signature = 'readings:delete-period
                            {year : Год периода (например, 2026)}
                            {month : Месяц периода 1-12 (например, 7)}
                            {--apartment= : Ограничить удаление одной квартирой (id)}
                            {--force : Удалить без интерактивного подтверждения}';

    protected $description = 'Удалить ошибочные показания счётчиков за указанный расчётный период';

    public function handle(): int
    {
        $year = (int) $this->argument('year');
        $month = (int) $this->argument('month');
        $apartmentId = $this->option('apartment') !== null ? (int) $this->option('apartment') : null;

        if ($month < 1 || $month > 12) {
            $this->components->error('Месяц должен быть в диапазоне 1-12.');

            return self::FAILURE;
        }

        if ($year < 2000 || $year > 2100) {
            $this->components->error('Год выглядит некорректно (ожидается 2000-2100).');

            return self::FAILURE;
        }

        $query = MeterReading::query()
            ->where('year', $year)
            ->where('month', $month);

        if ($apartmentId !== null) {
            $query->where('apartment_id', $apartmentId);
        }

        $rows = $query->get(['id', 'apartment_id', 'year', 'month', 'cold_m3', 'hot_m3', 'entered_by_manager']);

        if ($rows->isEmpty()) {
            $this->components->info(sprintf('За период %04d-%02d%s записей не найдено — удалять нечего.',
                $year,
                $month,
                $apartmentId !== null ? " (квартира #{$apartmentId})" : '',
            ));

            return self::SUCCESS;
        }

        $this->components->warn(sprintf('Найдено записей за %04d-%02d: %d', $year, $month, $rows->count()));
        $this->table(
            ['id', 'apartment_id', 'год', 'мес', 'ХВС', 'ГВС', 'ввёл управляющий'],
            $rows->map(fn (MeterReading $r) => [
                $r->id,
                $r->apartment_id,
                $r->year,
                $r->month,
                $r->cold_m3,
                $r->hot_m3,
                $r->entered_by_manager ? 'да' : 'нет',
            ])->all(),
        );

        if (! $this->option('force') && ! $this->confirm('Удалить эти записи безвозвратно?', false)) {
            $this->components->info('Отменено. Ничего не удалено.');

            return self::SUCCESS;
        }

        $deleted = $query->delete();

        $this->components->info("Удалено записей: {$deleted}.");

        return self::SUCCESS;
    }
}
