<?php

namespace App\Http\Controllers;

use App\Models\LeaveType;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeaveTypeController extends Controller
{
    public function index(Request $request)
    {
        $leaveTypes = LeaveType::orderBy('created_at', 'desc')->get();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json($leaveTypes);
        }

        return view('leave_types.index', compact('leaveTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:leave_types,name'],
            'annual_quota' => ['required', 'numeric', 'min:0'],
            'requires_approval' => ['required', 'boolean'],
            'can_carry_forward' => ['required', 'boolean'],
            'max_consecutive_days' => ['nullable', 'integer', 'min:1'],
            'notice_period_days' => ['required', 'integer', 'min:0'],
        ]);

        $leaveType = LeaveType::create($validated);

        AuditLogService::log(
            action: 'CREATE_LEAVE_TYPE',
            entityType: 'leave_type',
            entityId: $leaveType->id,
            newValue: $leaveType->toArray()
        );

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Leave type created successfully',
                'leave_type' => $leaveType
            ], 201);
        }

        return redirect()->route('leave-types.index')->with('success', 'Leave type created successfully.');
    }

    public function show(Request $request, $id)
    {
        $leaveType = LeaveType::findOrFail($id);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json($leaveType);
        }

        return response()->json($leaveType);
    }

    public function update(Request $request, $id)
    {
        $leaveType = LeaveType::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('leave_types')->ignore($leaveType->id)],
            'annual_quota' => ['required', 'numeric', 'min:0'],
            'requires_approval' => ['required', 'boolean'],
            'can_carry_forward' => ['required', 'boolean'],
            'max_consecutive_days' => ['nullable', 'integer', 'min:1'],
            'notice_period_days' => ['required', 'integer', 'min:0'],
        ]);

        $oldValue = $leaveType->toArray();
        $leaveType->update($validated);

        AuditLogService::log(
            action: 'UPDATE_LEAVE_TYPE',
            entityType: 'leave_type',
            entityId: $leaveType->id,
            oldValue: $oldValue,
            newValue: $leaveType->toArray()
        );

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Leave type updated successfully',
                'leave_type' => $leaveType
            ]);
        }

        return redirect()->route('leave-types.index')->with('success', 'Leave type updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        $leaveType = LeaveType::findOrFail($id);
        $oldValue = $leaveType->toArray();

        $leaveType->delete();

        AuditLogService::log(
            action: 'DELETE_LEAVE_TYPE',
            entityType: 'leave_type',
            entityId: $id,
            oldValue: $oldValue
        );

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'Leave type deleted successfully']);
        }

        return redirect()->route('leave-types.index')->with('success', 'Leave type deleted successfully.');
    }
}
