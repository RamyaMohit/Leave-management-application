<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => 'CASUAL',
                'annual_quota' => 12.00,
                'requires_approval' => true,
                'can_carry_forward' => false,
                'max_consecutive_days' => 5,
                'notice_period_days' => 1,
            ],
            [
                'name' => 'SICK',
                'annual_quota' => 10.00,
                'requires_approval' => true,
                'can_carry_forward' => false,
                'max_consecutive_days' => 7,
                'notice_period_days' => 0,
            ],
            [
                'name' => 'EARNED',
                'annual_quota' => 15.00,
                'requires_approval' => true,
                'can_carry_forward' => true,
                'max_consecutive_days' => 14,
                'notice_period_days' => 3,
            ],
        ];

        foreach ($types as $type) {
            LeaveType::updateOrCreate(['name' => $type['name']], $type);
        }
    }
}
