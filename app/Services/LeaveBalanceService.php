<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;

class LeaveBalanceService
{
    /**
     * Ensure leave balance record exists for an employee and leave type for a given year.
     */
    public function getOrInitBalance(Employee $employee, LeaveType $leaveType, int $year): LeaveBalance
    {
        $balance = LeaveBalance::where('employee_id', $employee->id)
                        ->where('leave_type_id', $leaveType->id)
                        ->where('year', $year)
                        ->first();

        if (!$balance) {
            $balance = LeaveBalance::create([
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'year' => $year,
                'allocated_days' => $leaveType->annual_quota,
                'used_days' => 0,
                'remaining_days' => $leaveType->annual_quota,
                'carry_forward_days' => 0,
            ]);
        }

        return $balance;
    }

    /**
     * Deduct leave balance for an approved leave request using pessimistic locking.
     */
    public function deductBalance(LeaveRequest $request): LeaveBalance
    {
        return DB::transaction(function () use ($request) {
            $year = Carbon::parse($request->from_date)->year;

            // Lock the balance row for update to prevent race conditions
            $balance = LeaveBalance::where('employee_id', $request->employee_id)
                        ->where('leave_type_id', $request->leave_type_id)
                        ->where('year', $year)
                        ->lockForUpdate()
                        ->first();

            if (!$balance) {
                // Initialize if missing
                $leaveType = LeaveType::findOrFail($request->leave_type_id);
                $employee = Employee::findOrFail($request->employee_id);
                $balance = $this->getOrInitBalance($employee, $leaveType, $year);

                // Lock again
                $balance = LeaveBalance::where('id', $balance->id)->lockForUpdate()->first();
            }

            if ($balance->remaining_days < $request->actual_leave_days) {
                throw new Exception("Insufficient leave balance. Required: {$request->actual_leave_days}, Remaining: {$balance->remaining_days}");
            }

            $oldValue = $balance->toArray();

            $balance->remaining_days -= $request->actual_leave_days;
            $balance->used_days += $request->actual_leave_days;
            $balance->save();

            AuditLogService::log(
                action: 'DEDUCT_LEAVE_BALANCE',
                entityType: 'leave_balance',
                entityId: $balance->id,
                oldValue: $oldValue,
                newValue: $balance->toArray()
            );

            return $balance;
        });
    }

    /**
     * Restore leave balance for a cancelled leave request using pessimistic locking.
     */
    public function restoreBalance(LeaveRequest $request): LeaveBalance
    {
        return DB::transaction(function () use ($request) {
            $year = Carbon::parse($request->from_date)->year;

            $balance = LeaveBalance::where('employee_id', $request->employee_id)
                ->where('leave_type_id', $request->leave_type_id)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (!$balance) {
                throw new Exception("Leave balance record not found for restoration.");
            }

            $oldValue = $balance->toArray();

            $balance->remaining_days += $request->actual_leave_days;
            $balance->used_days = max(0, $balance->used_days - $request->actual_leave_days);
            $balance->save();

            AuditLogService::log(
                action: 'RESTORE_LEAVE_BALANCE',
                entityType: 'leave_balance',
                entityId: $balance->id,
                oldValue: $oldValue,
                newValue: $balance->toArray()
            );

            return $balance;
        });
    }
}
