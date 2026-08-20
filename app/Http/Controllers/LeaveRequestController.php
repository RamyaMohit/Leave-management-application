<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\AuditLogService;
use App\Services\HolidayCalculatorService;
use App\Services\LeaveBalanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class LeaveRequestController extends Controller
{
    protected HolidayCalculatorService $holidayCalculator;
    protected LeaveBalanceService $balanceService;

    public function __construct(
        HolidayCalculatorService $holidayCalculator,
        LeaveBalanceService $balanceService
    ) {
        $this->holidayCalculator = $holidayCalculator;
        $this->balanceService = $balanceService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $query = LeaveRequest::with(['employee.manager', 'leaveType']);

        // Scope queries by role
        if ($user->hasRole('EMPLOYEE')) {
            $employeeId = $user->employee->id ?? 0;
            $query->where('employee_id', $employeeId);
        } elseif ($user->hasRole('MANAGER')) {
            $managerId = $user->employee->id ?? 0;
            $query->where(function ($q) use ($managerId) {
                $q->whereHas('employee', function ($eq) use ($managerId) {
                    $eq->where('manager_id', $managerId);
                })->orWhere('employee_id', $managerId);
            });
        }
        // HR and ADMIN see all

        // Filters
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('department')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('department', $request->department);
            });
        }

        if ($request->filled('leave_type_id')) {
            $query->where('leave_type_id', $request->leave_type_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from_date')) {
            $query->where('from_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->where('to_date', '<=', $request->to_date);
        }

        $perPage = $request->input('per_page', 10);
        $leaveRequests = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $leaveTypes = LeaveType::all();
        $employees = Employee::where('status', 'ACTIVE')->get();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json($leaveRequests);
        }

        return view('leave_requests.index', compact('leaveRequests', 'leaveTypes', 'employees'));
    }

    public function calculateDays(Request $request)
    {
        $request->validate([
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
        ]);

        $workingDays = $this->holidayCalculator->calculateWorkingDays(
            $request->from_date,
            $request->to_date
        );


        return response()->json([
            'from_date' => $request->from_date,
            'to_date' => $request->to_date,
            'actual_leave_days' => $workingDays,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        // Determine employee applying for leave
        $employeeId = $request->input('employee_id');
        if (!$employeeId || !$user->hasRole('HR', 'ADMIN')) {
            $employee = $user->employee;
        } else {
            $employee = Employee::find($employeeId);
        }

        if (!$employee) {
            return response()->json(['message' => 'Employee record not found.'], 404);
        }

        // Rule 3: Employee must exist and be ACTIVE
        if ($employee->status !== 'ACTIVE') {
            return response()->json(['message' => 'Inactive employees cannot apply for leave.'], 422);
        }

        $validated = $request->validate([
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $fromDate = Carbon::parse($validated['from_date'])->startOfDay();
        $toDate = Carbon::parse($validated['to_date'])->startOfDay();
        $today = Carbon::today();

        // Rule 1 & 2: from_date must not be after to_date, dates not in past
        if ($fromDate->lt($today)) {
            return response()->json(['message' => 'Leave start date cannot be in the past.'], 422);
        }

        // Rule 4: Employee's joining_date must not be after the leave dates
        $joiningDate = Carbon::parse($employee->joining_date)->startOfDay();
        if ($joiningDate->gt($fromDate)) {
            return response()->json(['message' => "Leave date cannot be before employee's joining date ({$employee->joining_date->format('Y-m-d')})."], 422);
        }

        $leaveType = LeaveType::findOrFail($validated['leave_type_id']);

        // Rule 7: Notice period check
        if ($leaveType->notice_period_days > 0) {
            $minNoticeDate = $today->copy()->addDays($leaveType->notice_period_days);
            if ($fromDate->lt($minNoticeDate)) {
                return response()->json([
                    'message' => "This leave type requires a notice period of {$leaveType->notice_period_days} day(s). Earliest allowable start date is {$minNoticeDate->format('Y-m-d')}."
                ], 422);
            }
        }

        // Rule 10: Backend day calculation excluding weekends & holidays
        $actualLeaveDays = $this->holidayCalculator->calculateWorkingDays(
            $fromDate->format('Y-m-d'),
            $toDate->format('Y-m-d')
        );

        if ($actualLeaveDays <= 0) {
            return response()->json(['message' => 'The selected date range contains no working days (all weekends/holidays).'], 422);
        }

        // Rule 6: Max consecutive days check (using total span or working days)
        $totalCalendarDays = $fromDate->diffInDays($toDate) + 1;
        if ($leaveType->max_consecutive_days && $totalCalendarDays > $leaveType->max_consecutive_days) {
            return response()->json([
                'message' => "Requested leave period ({$totalCalendarDays} days) exceeds maximum allowable consecutive days ({$leaveType->max_consecutive_days}) for {$leaveType->name}."
            ], 422);
        }

        // Rule 5: Requested leave days must not exceed available remaining balance
        $year = $fromDate->year;
        $balance = $this->balanceService->getOrInitBalance($employee, $leaveType, $year);

        if ($actualLeaveDays > $balance->remaining_days) {
            return response()->json([
                'message' => "Insufficient leave balance. Requested: {$actualLeaveDays} day(s), Available: {$balance->remaining_days} day(s)."
            ], 422);
        }

        // Rule 8: No overlapping APPROVED or PENDING leaves
        $hasOverlap = LeaveRequest::where('employee_id', $employee->id)
                    ->whereIn('status', ['PENDING', 'MANAGER_APPROVED', 'HR_APPROVED', 'APPROVED'])
                    ->where(function ($q) use ($fromDate, $toDate) {
                        $q->where('from_date', '<=', $toDate->format('Y-m-d'))
                        ->where('to_date', '>=', $fromDate->format('Y-m-d'));
                    })->exists();

        if ($hasOverlap) {
            return response()->json(['message' => 'You already have an active or pending leave request overlapping with the selected dates.'], 422);
        }

        // Rule 9: If requires_approval = false, status becomes APPROVED; else PENDING
        $initialStatus = $leaveType->requires_approval ? 'PENDING' : 'APPROVED';

        return DB::transaction(function () use ($employee, $leaveType, $fromDate, $toDate, $actualLeaveDays, $validated, $initialStatus, $request) {
            $leaveRequest = LeaveRequest::create([
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'from_date' => $fromDate->format('Y-m-d'),
                'to_date' => $toDate->format('Y-m-d'),
                'actual_leave_days' => $actualLeaveDays,
                'reason' => $validated['reason'],
                'status' => $initialStatus,
            ]);

            if ($initialStatus === 'APPROVED') {
                $this->balanceService->deductBalance($leaveRequest);
            }

            AuditLogService::log(
                action: 'CREATE_LEAVE_REQUEST',
                entityType: 'leave_request',
                entityId: $leaveRequest->id,
                newValue: $leaveRequest->toArray()
            );

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Leave application submitted successfully',
                    'leave_request' => $leaveRequest->load('employee', 'leaveType')
                ], 201);
            }

            return redirect()->route('leave-requests.index')->with('success', 'Leave request submitted successfully.');
        });
    }

    public function show(Request $request, $id)
    {
        $leaveRequest = LeaveRequest::with(['employee.manager', 'leaveType'])->findOrFail($id);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json($leaveRequest);
        }

        return response()->json($leaveRequest);
    }

    public function managerApprove(Request $request, $id)
    {
        $leaveRequest = LeaveRequest::with('employee')->findOrFail($id);
        $user = Auth::user();

        // Check if current user is manager or admin
        $isManager = ($user->employee && $user->employee->id === $leaveRequest->employee->manager_id);
        $isAdmin = $user->hasRole('ADMIN');

        if (!$isManager && !$isAdmin) {
            return response()->json(['message' => 'Unauthorized. Only the designated manager can approve at this stage.'], 403);
        }

        if ($leaveRequest->status !== 'PENDING') {
            return response()->json(['message' => 'Leave request is not in PENDING status.'], 422);
        }

        $oldValue = $leaveRequest->toArray();
        $leaveRequest->status = 'MANAGER_APPROVED';
        $leaveRequest->save();

        AuditLogService::log(
            action: 'MANAGER_APPROVE_LEAVE',
            entityType: 'leave_request',
            entityId: $leaveRequest->id,
            oldValue: $oldValue,
            newValue: $leaveRequest->toArray()
        );

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Leave request approved by manager.',
                'leave_request' => $leaveRequest->load('employee', 'leaveType')
            ]);
        }

        return redirect()->route('leave-requests.index')->with('success', 'Leave request manager approved.');
    }

    public function hrApprove(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->hasRole('HR', 'ADMIN')) {
            return response()->json(['message' => 'Unauthorized. Only HR/Admin can approve at this stage.'], 403);
        }

        $leaveRequest = LeaveRequest::with('employee')->findOrFail($id);

        // Can approve if MANAGER_APPROVED, or if PENDING and employee has no manager
        $canApproveDirect = ($leaveRequest->status === 'PENDING' && !$leaveRequest->employee->manager_id);
        if ($leaveRequest->status !== 'MANAGER_APPROVED' && !$canApproveDirect) {
            return response()->json(['message' => 'Leave request must be MANAGER_APPROVED before HR approval.'], 422);
        }

        return DB::transaction(function () use ($leaveRequest, $request) {
            $oldValue = $leaveRequest->toArray();

            // Deduct balance with lockForUpdate inside transaction
            $this->balanceService->deductBalance($leaveRequest);

            $leaveRequest->status = 'APPROVED';
            $leaveRequest->save();

            AuditLogService::log(
                action: 'HR_APPROVE_LEAVE',
                entityType: 'leave_request',
                entityId: $leaveRequest->id,
                oldValue: $oldValue,
                newValue: $leaveRequest->toArray()
            );

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Leave request approved by HR and balance deducted.',
                    'leave_request' => $leaveRequest->load('employee', 'leaveType')
                ]);
            }

            return redirect()->route('leave-requests.index')->with('success', 'Leave request approved by HR.');
        });
    }

    public function reject(Request $request, $id)
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $leaveRequest = LeaveRequest::with('employee')->findOrFail($id);
        $user = Auth::user();

        $isManager = ($user->employee && $user->employee->id === $leaveRequest->employee->manager_id);
        $isHrOrAdmin = $user->hasRole('HR', 'ADMIN');

        if (!$isManager && !$isHrOrAdmin) {
            return response()->json(['message' => 'Unauthorized to reject this leave request.'], 403);
        }

        if (!in_array($leaveRequest->status, ['PENDING', 'MANAGER_APPROVED'])) {
            return response()->json(['message' => 'Cannot reject leave request in current status.'], 422);
        }

        $oldValue = $leaveRequest->toArray();

        $leaveRequest->status = 'REJECTED';
        $leaveRequest->rejection_reason = $validated['rejection_reason'];
        $leaveRequest->save();

        AuditLogService::log(
            action: 'REJECT_LEAVE',
            entityType: 'leave_request',
            entityId: $leaveRequest->id,
            oldValue: $oldValue,
            newValue: $leaveRequest->toArray()
        );

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Leave request rejected.',
                'leave_request' => $leaveRequest->load('employee', 'leaveType')
            ]);
        }

        return redirect()->route('leave-requests.index')->with('success', 'Leave request rejected.');
    }

    public function cancel(Request $request, $id)
    {
        $leaveRequest = LeaveRequest::with('employee')->findOrFail($id);
        $user = Auth::user();

        $isOwner = ($user->employee && $user->employee->id === $leaveRequest->employee_id);
        $isManager = ($user->employee && $user->employee->id === $leaveRequest->employee->manager_id);
        $isHrOrAdmin = $user->hasRole('HR', 'ADMIN');

        if (!$isOwner && !$isManager && !$isHrOrAdmin) {
            return response()->json(['message' => 'Unauthorized to cancel this leave request.'], 403);
        }

        if (in_array($leaveRequest->status, ['REJECTED', 'CANCELLED'])) {
            return response()->json(['message' => 'Leave request is already rejected or cancelled.'], 422);
        }

        return DB::transaction(function () use ($leaveRequest, $request) {
            $oldValue = $leaveRequest->toArray();

            // If leave was APPROVED, restore consumed balance
            if ($leaveRequest->status === 'APPROVED') {
                $this->balanceService->restoreBalance($leaveRequest);
            }

            $leaveRequest->status = 'CANCELLED';
            $leaveRequest->save();

            AuditLogService::log(
                action: 'CANCEL_LEAVE',
                entityType: 'leave_request',
                entityId: $leaveRequest->id,
                oldValue: $oldValue,
                newValue: $leaveRequest->toArray()
            );

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Leave request cancelled successfully.',
                    'leave_request' => $leaveRequest->load('employee', 'leaveType')
                ]);
            }

            return redirect()->route('leave-requests.index')->with('success', 'Leave request cancelled.');
        });
    }
}
