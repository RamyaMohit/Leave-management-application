@extends('layouts.app')

@section('page-title', 'Dashboard')

@section('content')
<div class="container-fluid">

    <!-- Welcome Banner -->
    <div class="card-custom p-4 mb-4" style="background: linear-gradient(135deg, #312e81, #4f46e5); color: #fff;">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h3 class="fw-bold m-0">Welcome back, {{ auth()->user()->name }} 👋</h3>
                <p class="mb-0 text-white-50 mt-1">Role: <span class="badge bg-light text-dark fw-bold">{{ auth()->user()->role }}</span> | Manage your leave requests and team approvals seamlessly.</p>
            </div>
            <div>
                <a href="{{ route('leave-requests.index') }}" class="btn btn-light fw-bold text-indigo rounded-pill px-4 shadow-sm">
                    <i class="fa-solid fa-plus me-1"></i> Apply Leave
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Cards Grid -->
    <div class="row g-3 mb-4">
        <div class="col-md-4 col-xl-2">
            <div class="card-custom p-3 text-center border-start border-4 border-indigo">
                <div class="text-muted fs-6 fw-semibold text-uppercase">Employees</div>
                <div class="fs-2 fw-bold text-indigo mt-1">{{ $employee_count }}</div>
            </div>
        </div>
        <div class="col-md-4 col-xl-2">
            <div class="card-custom p-3 text-center border-start border-4 border-warning">
                <div class="text-muted fs-6 fw-semibold text-uppercase">Pending</div>
                <div class="fs-2 fw-bold text-warning mt-1">{{ $leave_request_stats['pending'] }}</div>
            </div>
        </div>
        <div class="col-md-4 col-xl-2">
            <div class="card-custom p-3 text-center border-start border-4 border-info">
                <div class="text-muted fs-6 fw-semibold text-uppercase">Mgr Approved</div>
                <div class="fs-2 fw-bold text-info mt-1">{{ $leave_request_stats['manager_approved'] }}</div>
            </div>
        </div>
        <div class="col-md-4 col-xl-2">
            <div class="card-custom p-3 text-center border-start border-4 border-primary">
                <div class="text-muted fs-6 fw-semibold text-uppercase">HR Approved</div>
                <div class="fs-2 fw-bold text-primary mt-1">{{ $leave_request_stats['hr_approved'] }}</div>
            </div>
        </div>
        <div class="col-md-4 col-xl-2">
            <div class="card-custom p-3 text-center border-start border-4 border-success">
                <div class="text-muted fs-6 fw-semibold text-uppercase">Approved</div>
                <div class="fs-2 fw-bold text-success mt-1">{{ $leave_request_stats['approved'] }}</div>
            </div>
        </div>
        <div class="col-md-4 col-xl-2">
            <div class="card-custom p-3 text-center border-start border-4 border-danger">
                <div class="text-muted fs-6 fw-semibold text-uppercase">Rejected</div>
                <div class="fs-2 fw-bold text-danger mt-1">{{ $leave_request_stats['rejected'] }}</div>
            </div>
        </div>
    </div>

    <!-- My Leave Balances (Current Year) -->
    @if($myBalances->count() > 0)
    <h5 class="fw-bold mb-3"><i class="fa-solid fa-wallet text-indigo me-2"></i>My Leave Balances ({{ \Carbon\Carbon::now()->year }})</h5>
    <div class="row g-3 mb-4">
        @foreach($myBalances as $balance)
        <div class="col-md-4">
            <div class="card-custom p-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold text-uppercase m-0 text-secondary">{{ $balance->leaveType->name }}</h6>
                    <span class="badge bg-primary rounded-pill px-3">{{ $balance->remaining_days }} Days Left</span>
                </div>
                <div class="progress mb-3" style="height: 8px;">
                    @php
                        $percentage = $balance->allocated_days > 0 ? min(100, ($balance->used_days / $balance->allocated_days) * 100) : 0;
                    @endphp
                    <div class="progress-bar bg-indigo" role="progressbar" style="width: {{ $percentage }}%"></div>
                </div>
                <div class="d-flex justify-content-between fs-6 text-muted">
                    <span>Quota: <strong>{{ $balance->allocated_days }}</strong></span>
                    <span>Used: <strong>{{ $balance->used_days }}</strong></span>
                    <span>Carry Fwd: <strong>{{ $balance->carry_forward_days }}</strong></span>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <!-- Role Action Tables -->
    <div class="row g-4 mb-4">
        <!-- Pending Approvals for Manager -->
        @if(auth()->user()->hasRole('MANAGER', 'ADMIN') && isset($pendingTeamApprovals) && $pendingTeamApprovals->count() > 0)
        <div class="col-lg-6">
            <div class="card-custom p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold m-0 text-dark"><i class="fa-solid fa-clock text-warning me-2"></i>Pending Manager Approvals</h5>
                    <span class="badge bg-warning text-dark">{{ $pendingTeamApprovals->count() }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Employee</th>
                                <th>Leave Type</th>
                                <th>Dates</th>
                                <th>Days</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingTeamApprovals as $req)
                            <tr>
                                <td class="fw-semibold">{{ $req->employee->name }}</td>
                                <td><span class="badge bg-light text-dark">{{ $req->leaveType->name }}</span></td>
                                <td class="fs-6">{{ $req->from_date->format('M d') }} - {{ $req->to_date->format('M d') }}</td>
                                <td class="fw-bold">{{ $req->actual_leave_days }}</td>
                                <td>
                                    <a href="{{ route('leave-requests.index') }}" class="btn btn-sm btn-primary rounded-pill px-3">Review</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        <!-- Pending Approvals for HR -->
        @if(auth()->user()->hasRole('HR', 'ADMIN') && isset($pendingHrApprovals) && $pendingHrApprovals->count() > 0)
        <div class="col-lg-6">
            <div class="card-custom p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold m-0 text-dark"><i class="fa-solid fa-user-check text-primary me-2"></i>Pending HR Approvals</h5>
                    <span class="badge bg-primary">{{ $pendingHrApprovals->count() }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Employee</th>
                                <th>Leave Type</th>
                                <th>Dates</th>
                                <th>Days</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingHrApprovals as $req)
                            <tr>
                                <td class="fw-semibold">{{ $req->employee->name }}</td>
                                <td><span class="badge bg-light text-dark">{{ $req->leaveType->name }}</span></td>
                                <td class="fs-6">{{ $req->from_date->format('M d') }} - {{ $req->to_date->format('M d') }}</td>
                                <td class="fw-bold">{{ $req->actual_leave_days }}</td>
                                <td>
                                    <a href="{{ route('leave-requests.index') }}" class="btn btn-sm btn-success rounded-pill px-3">Approve</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Department Distribution & Recent Applications -->
    <div class="row g-4">
        <!-- Leave by Department -->
        <div class="col-lg-5">
            <div class="card-custom p-4">
                <h5 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-sitemap text-info me-2"></i>Leave by Department</h5>
                @if(count($leave_by_department) > 0)
                    @foreach($leave_by_department as $dept => $cnt)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between fs-6 mb-1">
                            <span class="fw-semibold text-secondary">{{ $dept }}</span>
                            <span class="fw-bold text-dark">{{ $cnt }} Request(s)</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-info" style="width: {{ min(100, $cnt * 10) }}%"></div>
                        </div>
                    </div>
                    @endforeach
                @else
                    <p class="text-muted my-3">No leave requests recorded yet.</p>
                @endif
            </div>
        </div>

        <!-- My Recent Applications -->
        <div class="col-lg-7">
            <div class="card-custom p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold m-0 text-dark"><i class="fa-solid fa-history text-secondary me-2"></i>My Recent Applications</h5>
                    <a href="{{ route('leave-requests.index') }}" class="text-decoration-none fw-semibold fs-6">View All</a>
                </div>
                @if($myRecentLeaves->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Type</th>
                                <th>From - To</th>
                                <th>Working Days</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($myRecentLeaves as $req)
                            <tr>
                                <td><span class="fw-semibold">{{ $req->leaveType->name }}</span></td>
                                <td class="fs-6">{{ $req->from_date->format('M d, Y') }} to {{ $req->to_date->format('M d, Y') }}</td>
                                <td class="fw-bold text-center">{{ $req->actual_leave_days }}</td>
                                <td><span class="badge badge-status badge-{{ $req->status }}">{{ $req->status }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-muted my-3">You haven't submitted any leave applications yet.</p>
                @endif
            </div>
        </div>
    </div>

</div>
@endsection
