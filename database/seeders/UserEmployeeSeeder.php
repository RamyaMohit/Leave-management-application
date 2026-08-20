<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\LeaveBalanceService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserEmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $balanceService = app(LeaveBalanceService::class);
        $currentYear = Carbon::now()->year;

        // 1. Admin
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@company.com'],
            ['name' => 'System Admin', 'password' => Hash::make('password'), 'role' => 'ADMIN']
        );
        $adminEmp = Employee::updateOrCreate(
            ['employee_code' => 'EMP001'],
            [
                'user_id' => $adminUser->id,
                'employee_id' => 'EMP001',
                'name' => 'System Admin',
                'email' => 'admin@company.com',
                'department' => 'Management',
                'designation' => 'Director',
                'manager_id' => null,
                'joining_date' => '2020-01-01',
                'status' => 'ACTIVE',
            ]
        );

        // 2. HR
        $hrUser = User::updateOrCreate(
            ['email' => 'hr@company.com'],
            ['name' => 'HR Manager', 'password' => Hash::make('password'), 'role' => 'HR']
        );
        $hrEmp = Employee::updateOrCreate(
            ['employee_code' => 'EMP002'],
            [
                'user_id' => $hrUser->id,
                'employee_id' => 'EMP002',
                'name' => 'HR Manager',
                'email' => 'hr@company.com',
                'department' => 'Human Resources',
                'designation' => 'HR Lead',
                'manager_id' => $adminEmp->id,
                'joining_date' => '2021-03-15',
                'status' => 'ACTIVE',
            ]
        );

        // 3. Manager
        $managerUser = User::updateOrCreate(
            ['email' => 'manager@company.com'],
            ['name' => 'Team Manager', 'password' => Hash::make('password'), 'role' => 'MANAGER']
        );
        $managerEmp = Employee::updateOrCreate(
            ['employee_code' => 'EMP003'],
            [
                'user_id' => $managerUser->id,
                'employee_id' => 'EMP003',
                'name' => 'Team Manager',
                'email' => 'manager@company.com',
                'department' => 'IT Engineering',
                'designation' => 'Engineering Manager',
                'manager_id' => $adminEmp->id,
                'joining_date' => '2021-06-01',
                'status' => 'ACTIVE',
            ]
        );

        // 4. Employee
        $empUser = User::updateOrCreate(
            ['email' => 'employee@company.com'],
            ['name' => 'John Employee', 'password' => Hash::make('password'), 'role' => 'EMPLOYEE']
        );
        $emp = Employee::updateOrCreate(
            ['employee_code' => 'EMP004'],
            [
                'user_id' => $empUser->id,
                'employee_id' => 'EMP004',
                'name' => 'John Employee',
                'email' => 'employee@company.com',
                'department' => 'IT Engineering',
                'designation' => 'Software Engineer',
                'manager_id' => $managerEmp->id,
                'joining_date' => '2022-01-10',
                'status' => 'ACTIVE',
            ]
        );

        // Seed balances for all employees and all leave types for current year
        $allEmps = Employee::all();
        $leaveTypes = LeaveType::all();

        foreach ($allEmps as $e) {
            foreach ($leaveTypes as $t) {
                $balanceService->getOrInitBalance($e, $t, $currentYear);
            }
        }
    }
}
