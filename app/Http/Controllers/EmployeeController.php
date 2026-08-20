<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\LeaveBalanceService;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    protected LeaveBalanceService $balanceService;

    public function __construct(LeaveBalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    public function index(Request $request)
    {
        $query = Employee::with(['manager', 'user']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('employee_code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $employees = $query->orderBy('created_at', 'desc')->paginate(10);
        $managers = Employee::where('status', 'ACTIVE')->get();
        $departments = Employee::distinct()->pluck('department')->filter()->values();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json($employees);
        }

        return view('employees.index', compact('employees', 'managers', 'departments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_code' => ['required', 'string', 'max:50', 'unique:employees,employee_code'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:employees,email', 'unique:users,email'],
            'department' => ['required', 'string', 'max:100'],
            'designation' => ['required', 'string', 'max:100'],
            'manager_id' => ['nullable', 'exists:employees,id'],
            'joining_date' => ['required', 'date'],
            'status' => ['required', 'in:ACTIVE,INACTIVE'],
            'role' => ['nullable', 'in:EMPLOYEE,MANAGER,HR,ADMIN'],
        ]);

        // Create linked User if role provided or default to EMPLOYEE
        $role = $validated['role'] ?? 'EMPLOYEE';
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt('password'),
            'role' => $role,
        ]);

        $employee = Employee::create([
            'user_id' => $user->id,
            'employee_id' => $validated['employee_code'],
            'employee_code' => $validated['employee_code'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'department' => $validated['department'],
            'designation' => $validated['designation'],
            'manager_id' => $validated['manager_id'] ?? null,
            'joining_date' => $validated['joining_date'],
            'status' => $validated['status'],
        ]);

        // Initialize balances for current year
        $currentYear = Carbon::now()->year;
        $leaveTypes = LeaveType::all();
        foreach ($leaveTypes as $type) {
            $this->balanceService->getOrInitBalance($employee, $type, $currentYear);
        }

        AuditLogService::log(
            action: 'CREATE_EMPLOYEE',
            entityType: 'employee',
            entityId: $employee->id,
            newValue: $employee->toArray()
        );

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Employee created successfully',
                'employee' => $employee->load('user', 'manager')
            ], 201);
        }

        return redirect()->route('employees.index')->with('success', 'Employee created successfully.');
    }

    public function show(Request $request, $id)
    {
        $employee = Employee::with(['user', 'manager', 'subordinates', 'leaveBalances.leaveType'])->findOrFail($id);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json($employee);
        }

        return response()->json($employee);
    }

    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);

        $validated = $request->validate([
            'employee_code' => ['required', 'string', 'max:50', Rule::unique('employees')->ignore($employee->id)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('employees')->ignore($employee->id)],
            'department' => ['required', 'string', 'max:100'],
            'designation' => ['required', 'string', 'max:100'],
            'manager_id' => ['nullable', 'exists:employees,id', "different:{$employee->id}"],
            'joining_date' => ['required', 'date'],
            'status' => ['required', 'in:ACTIVE,INACTIVE'],
            'role' => ['nullable', 'in:EMPLOYEE,MANAGER,HR,ADMIN'],
        ]);

        if (isset($validated['manager_id']) && $validated['manager_id'] == $employee->id) {
            return response()->json(['message' => 'An employee cannot be their own manager.'], 422);
        }

        $oldValue = $employee->toArray();

        $employee->update([
            'employee_code' => $validated['employee_code'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'department' => $validated['department'],
            'designation' => $validated['designation'],
            'manager_id' => $validated['manager_id'] ?? null,
            'joining_date' => $validated['joining_date'],
            'status' => $validated['status'],
        ]);

        if ($employee->user) {
            $employee->user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => $validated['role'] ?? $employee->user->role,
            ]);
        }

        AuditLogService::log(
            action: 'UPDATE_EMPLOYEE',
            entityType: 'employee',
            entityId: $employee->id,
            oldValue: $oldValue,
            newValue: $employee->toArray()
        );

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Employee updated successfully',
                'employee' => $employee->load('user', 'manager')
            ]);
        }

        return redirect()->route('employees.index')->with('success', 'Employee updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);
        $oldValue = $employee->toArray();

        $employee->delete();

        AuditLogService::log(
            action: 'DELETE_EMPLOYEE',
            entityType: 'employee',
            entityId: $id,
            oldValue: $oldValue
        );

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'Employee deleted successfully']);
        }

        return redirect()->route('employees.index')->with('success', 'Employee deleted successfully.');
    }
}
