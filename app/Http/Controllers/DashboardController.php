<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // High-level statistics
        $employeeCount = Employee::where('status', 'ACTIVE')->count();

        $leaveStatsRaw = LeaveRequest::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $leaveRequestStats = [
            'pending' => $leaveStatsRaw['PENDING'] ?? 0,
            'manager_approved' => $leaveStatsRaw['MANAGER_APPROVED'] ?? 0,
            'hr_approved' => $leaveStatsRaw['HR_APPROVED'] ?? 0,
            'approved' => $leaveStatsRaw['APPROVED'] ?? 0,
            'rejected' => $leaveStatsRaw['REJECTED'] ?? 0,
            'cancelled' => $leaveStatsRaw['CANCELLED'] ?? 0,
        ];

        $leaveByDeptRaw = LeaveRequest::join('employees', 'leave_requests.employee_id', '=', 'employees.id')
            ->selectRaw('employees.department, count(*) as count')
            ->groupBy('employees.department')
            ->pluck('count', 'department')
            ->toArray();

        $data = [
            'employee_count' => $employeeCount,
            'leave_request_stats' => $leaveRequestStats,
            'leave_by_department' => $leaveByDeptRaw,
        ];

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json($data);
        }

        // Additional data for Web Dashboard view based on role
        $myEmployee = $user->employee;
        $myBalances = collect();
        $myRecentLeaves = collect();
        $pendingTeamApprovals = collect();
        $pendingHrApprovals = collect();

        $currentYear = Carbon::now()->year;

        if ($myEmployee) {
            $myBalances = LeaveBalance::with('leaveType')
                ->where('employee_id', $myEmployee->id)
                ->where('year', $currentYear)
                ->get();

            $myRecentLeaves = LeaveRequest::with('leaveType')
                ->where('employee_id', $myEmployee->id)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            if ($user->hasRole('MANAGER', 'ADMIN')) {
                $pendingTeamApprovals = LeaveRequest::with(['employee', 'leaveType'])
                    ->whereHas('employee', function ($q) use ($myEmployee) {
                        $q->where('manager_id', $myEmployee->id);
                    })
                    ->where('status', 'PENDING')
                    ->get();
            }
        }

        if ($user->hasRole('HR', 'ADMIN')) {
            $pendingHrApprovals = LeaveRequest::with(['employee', 'leaveType'])
                ->where(function ($q) {
                    $q->where('status', 'MANAGER_APPROVED')
                      ->orWhere(function ($q2) {
                          $q2->where('status', 'PENDING')
                             ->whereHas('employee', function ($eq) {
                                 $eq->whereNull('manager_id');
                             });
                      });
                })->get();
        }

        return view('dashboard', array_merge($data, compact(
            'myBalances',
            'myRecentLeaves',
            'pendingTeamApprovals',
            'pendingHrApprovals'
        )));
    }
}
