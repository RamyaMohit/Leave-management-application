<?php

namespace App\Services;

use App\Models\Holiday;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class HolidayCalculatorService
{
    /**
     * Calculate actual working leave days between fromDate and toDate.
     * Excludes weekends (Saturdays and Sundays) and registered company holidays.
     */
    public function calculateWorkingDays($fromDate, $toDate): float
    {
        $start = Carbon::parse($fromDate)->startOfDay();
        $end = Carbon::parse($toDate)->startOfDay();

        if ($start->gt($end)) {
            return 0.0;
        }

        // Fetch all holiday dates within range
        $holidayDates = Holiday::whereBetween('holiday_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                        ->pluck('holiday_date')
                        ->map(fn($date) => Carbon::parse($date)->format('Y-m-d'))
                        ->toArray();

        $period = CarbonPeriod::create($start, $end);
        $workingDays = 0;

        foreach ($period as $date) {
            // Exclude Saturday (6) and Sunday (0)
            if ($date->isWeekend()) {
                continue;
            }

            // Exclude holidays
            if (in_array($date->format('Y-m-d'), $holidayDates)) {
                continue;
            }

            $workingDays++;
        }

        return (float) $workingDays;
    }
}
