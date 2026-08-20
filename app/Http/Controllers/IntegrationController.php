<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\LeaveBalanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IntegrationController extends Controller
{
    protected LeaveBalanceService $balanceService;

    public function __construct(LeaveBalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    public function syncEmployee(Request $request)
    {
        $validated = $request->validate([
            'employee_code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'department' => ['required', 'string', 'max:100'],
            'designation' => ['required', 'string', 'max:100'],
            'manager_code' => ['nullable', 'string', 'max:50'],
            'joining_date' => ['nullable', 'date'],
        ]);

        return DB::transaction(function () use ($validated) {
            $managerId = null;
            if (!empty($validated['manager_code'])) {
                $manager = Employee::where('employee_code', $validated['manager_code'])->first();
                if ($manager) {
                    $managerId = $manager->id;
                }
            }

            $user = User::where('email', $validated['email'])->first();
            if (!$user) {
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => bcrypt('password'),
                    'role' => 'EMPLOYEE',
                ]);
            }

            $existing = Employee::where('employee_code', $validated['employee_code'])->first();
            $isCreated = !$existing;

            $joiningDate = $validated['joining_date'] ?? ($existing->joining_date ?? Carbon::now()->format('Y-m-d'));

            $employee = Employee::updateOrCreate(
                ['employee_code' => $validated['employee_code']],
                [
                    'user_id' => $user->id,
                    'employee_id' => $validated['employee_code'],
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'department' => $validated['department'],
                    'designation' => $validated['designation'],
                    'manager_id' => $managerId,
                    'joining_date' => $joiningDate,
                    'status' => 'ACTIVE',
                ]
            );

            // Initialize leave balances if new employee
            if ($isCreated) {
                $currentYear = Carbon::now()->year;
                $leaveTypes = LeaveType::all();
                foreach ($leaveTypes as $type) {
                    $this->balanceService->getOrInitBalance($employee, $type, $currentYear);
                }
            }

            AuditLogService::log(
                action: $isCreated ? 'INTEGRATION_CREATE_EMPLOYEE' : 'INTEGRATION_UPDATE_EMPLOYEE',
                entityType: 'employee',
                entityId: $employee->id,
                newValue: $employee->toArray()
            );

            return response()->json([
                'created' => $isCreated,
                'employee_id' => $employee->id,
                'message' => $isCreated ? 'Employee created successfully via integration' : 'Employee updated successfully via integration'
            ], $isCreated ? 201 : 200);
        });
    }
}
