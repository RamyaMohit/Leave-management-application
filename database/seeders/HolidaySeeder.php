<?php

namespace Database\Seeders;

use App\Models\Holiday;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    public function run(): void
    {
        $holidays = [
            ['holiday_date' => '2026-01-01', 'holiday_name' => 'New Year\'s Day'],
            ['holiday_date' => '2026-01-26', 'holiday_name' => 'Republic Day'],
            ['holiday_date' => '2026-08-15', 'holiday_name' => 'Independence Day'],
            ['holiday_date' => '2026-08-21', 'holiday_name' => 'Company Foundation Day Holiday'],
            ['holiday_date' => '2026-10-02', 'holiday_name' => 'Gandhi Jayanti'],
            ['holiday_date' => '2026-12-25', 'holiday_name' => 'Christmas Day'],
        ];

        foreach ($holidays as $holiday) {
            Holiday::updateOrCreate(['holiday_date' => $holiday['holiday_date']], $holiday);
        }
    }
}
