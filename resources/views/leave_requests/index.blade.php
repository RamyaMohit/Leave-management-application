@extends('layouts.app')

@section('page-title', 'Leave Applications & Approvals')

@section('content')
<div class="container-fluid">

    <!-- Filters & Create Action -->
    <div class="card-custom p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <div>
                <h5 class="fw-bold m-0"><i class="fa-solid fa-calendar-check text-indigo me-2"></i>Leave Applications</h5>
                <p class="text-muted mb-0 fs-6">Manage, approve, reject, or cancel leave requests across stages.</p>
            </div>
            <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="openApplyModal()">
                <i class="fa-solid fa-paper-plane me-1"></i> Apply for Leave
            </button>
        </div>

        <form method="GET" action="{{ route('leave-requests.index') }}" class="row g-3">
            @if(auth()->user()->hasRole('HR', 'ADMIN'))
            <div class="col-md-3">
                <select name="employee_id" class="form-select bg-light">
                    <option value="">All Employees</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>{{ $emp->name }} ({{ $emp->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="col-md-2">
                <select name="leave_type_id" class="form-select bg-light">
                    <option value="">All Leave Types</option>
                    @foreach($leaveTypes as $type)
                        <option value="{{ $type->id }}" {{ request('leave_type_id') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <select name="status" class="form-select bg-light">
                    <option value="">All Statuses</option>
                    <option value="PENDING" {{ request('status') == 'PENDING' ? 'selected' : '' }}>Pending</option>
                    <option value="MANAGER_APPROVED" {{ request('status') == 'MANAGER_APPROVED' ? 'selected' : '' }}>Manager Approved</option>
                    <option value="HR_APPROVED" {{ request('status') == 'HR_APPROVED' ? 'selected' : '' }}>HR Approved</option>
                    <option value="APPROVED" {{ request('status') == 'APPROVED' ? 'selected' : '' }}>Approved</option>
                    <option value="REJECTED" {{ request('status') == 'REJECTED' ? 'selected' : '' }}>Rejected</option>
                    <option value="CANCELLED" {{ request('status') == 'CANCELLED' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>

            <div class="col-md-2">
                <input type="date" name="from_date" class="form-control bg-light" value="{{ request('from_date') }}" placeholder="From Date">
            </div>

            <div class="col-md-2">
                <input type="date" name="to_date" class="form-control bg-light" value="{{ request('to_date') }}" placeholder="To Date">
            </div>

            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-secondary w-100"><i class="fa-solid fa-filter"></i></button>
                <a href="{{ route('leave-requests.index') }}" class="btn btn-outline-secondary"><i class="fa-solid fa-rotate-left"></i></a>
            </div>
        </form>
    </div>

    <!-- Leave Requests Table -->
    <div class="card-custom p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Leave Type</th>
                        <th>Date Range</th>
                        <th>Working Days</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leaveRequests as $req)
                    @php
                        $user = auth()->user();
                        $isManagerOfEmp = ($user->employee && $user->employee->id === $req->employee->manager_id);
                        $isHrOrAdmin = $user->hasRole('HR', 'ADMIN');
                        $isOwner = ($user->employee && $user->employee->id === $req->employee_id);
                    @endphp
                    <tr>
                        <td><span class="text-muted fw-bold">#{{ $req->id }}</span></td>
                        <td>
                            <div class="fw-bold text-dark">{{ $req->employee->name }}</div>
                            <span class="text-muted fs-6">{{ $req->employee->employee_code }}</span>
                        </td>
                        <td><span class="badge bg-light text-dark">{{ $req->employee->department }}</span></td>
                        <td><span class="badge bg-indigo-100 text-indigo-800" style="background:#e0e7ff; color:#3730a3;">{{ $req->leaveType->name }}</span></td>
                        <td class="fs-6">
                            <i class="fa-regular fa-calendar text-muted me-1"></i>
                            {{ $req->from_date->format('M d, Y') }} &rarr; {{ $req->to_date->format('M d, Y') }}
                        </td>
                        <td>
                            <span class="badge bg-dark rounded-pill px-3 fs-6">{{ $req->actual_leave_days }} Days</span>
                        </td>
                        <td style="max-width: 200px;" class="text-truncate">{{ $req->reason }}</td>
                        <td>
                            <span class="badge badge-status badge-{{ $req->status }}">{{ $req->status }}</span>
                        </td>
                        <td class="text-end">
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick='viewDetails(@json($req))' title="Details">
                                    <i class="fa-solid fa-eye"></i>
                                </button>

                                <!-- Manager Approval Action -->
                                @if(($isManagerOfEmp || $user->hasRole('ADMIN')) && $req->status === 'PENDING')
                                    <button type="button" class="btn btn-sm btn-primary ms-1" onclick="managerApprove({{ $req->id }})" title="Manager Approve">
                                        <i class="fa-solid fa-user-check me-1"></i> Mgr Approve
                                    </button>
                                @endif

                                <!-- HR Approval Action -->
                                @if($isHrOrAdmin && ($req->status === 'MANAGER_APPROVED' || ($req->status === 'PENDING' && !$req->employee->manager_id)))
                                    <button type="button" class="btn btn-sm btn-success ms-1" onclick="hrApprove({{ $req->id }})" title="HR Approve & Deduct Balance">
                                        <i class="fa-solid fa-check-double me-1"></i> HR Approve
                                    </button>
                                @endif

                                <!-- Reject Action -->
                                @if(($isManagerOfEmp || $isHrOrAdmin) && in_array($req->status, ['PENDING', 'MANAGER_APPROVED']))
                                    <button type="button" class="btn btn-sm btn-outline-danger ms-1" onclick="openRejectModal({{ $req->id }})" title="Reject">
                                        <i class="fa-solid fa-xmark"></i> Reject
                                    </button>
                                @endif

                                <!-- Cancel Action -->
                                @if(($isOwner || $isManagerOfEmp || $isHrOrAdmin) && !in_array($req->status, ['REJECTED', 'CANCELLED']))
                                    <button type="button" class="btn btn-sm btn-outline-secondary ms-1" onclick="confirmCancel({{ $req->id }})" title="Cancel Leave">
                                        <i class="fa-solid fa-ban"></i> Cancel
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">No leave requests found matching your filters.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $leaveRequests->withQueryString()->links() }}
        </div>
    </div>

</div>

<!-- Apply Leave Modal -->
<div class="modal fade" id="applyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-paper-plane text-indigo me-2"></i>Apply for Leave</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="applyLeaveForm">
                @csrf
                <div class="modal-body p-4">

                    @if(auth()->user()->hasRole('HR', 'ADMIN'))
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Apply On Behalf Of Employee</label>
                        <select name="employee_id" class="form-select" id="applyEmpId">
                            <option value="">Myself ({{ auth()->user()->name }})</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->employee_code }}) - {{ $emp->department }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Leave Type <span class="text-danger">*</span></label>
                            <select name="leave_type_id" id="applyLeaveType" class="form-select" required>
                                <option value="">Select Leave Type</option>
                                @foreach($leaveTypes as $t)
                                    <option value="{{ $t->id }}" data-notice="{{ $t->notice_period_days }}" data-max="{{ $t->max_consecutive_days ?? 'None' }}">
                                        {{ $t->name }} (Quota: {{ $t->annual_quota }} Days)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">From Date <span class="text-danger">*</span></label>
                            <input type="date" name="from_date" id="applyFromDate" class="form-control" required min="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">To Date <span class="text-danger">*</span></label>
                            <input type="date" name="to_date" id="applyToDate" class="form-control" required min="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Calculated Working Days</label>
                            <div class="input-group">
                                <input type="text" id="calculatedDaysInput" class="form-control bg-light fw-bold text-indigo" readonly value="0 Days">
                                <span class="input-group-text bg-light text-muted" id="calcSpinner" style="display:none;"><i class="fa-solid fa-spinner fa-spin"></i></span>
                            </div>
                            <small class="text-muted fs-6" id="calcHelp">Excludes weekends & company holidays.</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Reason for Leave <span class="text-danger">*</span></label>
                            <textarea name="reason" id="applyReason" class="form-control" rows="3" required placeholder="Please provide detailed reason for leave request..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4" id="submitApplyBtn">Submit Application</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-4">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold">Reject Leave Request</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectForm">
                @csrf
                <input type="hidden" id="rejectReqId">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea id="rejectionReason" class="form-control" rows="3" required placeholder="State reason for rejection..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4">Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-circle-info text-indigo me-2"></i>Leave Details & Workflow Timeline</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="detailsContent">
                <!-- Dynamically filled -->
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let applyModal, rejectModal, detailsModal;

    $(document).ready(function() {
        applyModal = new bootstrap.Modal(document.getElementById('applyModal'));
        rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));
        detailsModal = new bootstrap.Modal(document.getElementById('detailsModal'));

        // Real-time backend working day calculator
        $('#applyFromDate, #applyToDate').on('change', function() {
            const from = $('#applyFromDate').val();
            const to = $('#applyToDate').val();

            if (from && to && from <= to) {
                $('#calcSpinner').show();
                $.ajax({
                    url: '/leave-requests/calculate-days',
                    type: 'POST',
                    data: { from_date: from, to_date: to },
                    success: function(res) {
                        $('#calcSpinner').hide();
                        $('#calculatedDaysInput').val(res.actual_leave_days + ' Days');
                    },
                    error: function() {
                        $('#calcSpinner').hide();
                        $('#calculatedDaysInput').val('0 Days');
                    }
                });
            }
        });

        // Submit Apply Leave
        $('#applyLeaveForm').on('submit', function(e) {
            e.preventDefault();

            const payload = {
                employee_id: $('#applyEmpId').val() || null,
                leave_type_id: $('#applyLeaveType').val(),
                from_date: $('#applyFromDate').val(),
                to_date: $('#applyToDate').val(),
                reason: $('#applyReason').val(),
            };

            $('#submitApplyBtn').prop('disabled', true).text('Submitting...');

            $.ajax({
                url: '/leave-requests',
                type: 'POST',
                data: payload,
                success: function(res) {
                    applyModal.hide();
                    showToast(res.message || 'Leave request submitted!');
                    setTimeout(() => location.reload(), 800);
                },
                error: function(err) {
                    $('#submitApplyBtn').prop('disabled', false).text('Submit Application');
                    const msg = err.responseJSON?.message || 'Failed to submit leave request.';
                    showToast(msg, false);
                }
            });
        });

        // Submit Rejection
        $('#rejectForm').on('submit', function(e) {
            e.preventDefault();
            const reqId = $('#rejectReqId').val();
            const reason = $('#rejectionReason').val();

            $.ajax({
                url: `/leave-requests/${reqId}/reject`,
                type: 'PUT',
                data: { rejection_reason: reason },
                success: function(res) {
                    rejectModal.hide();
                    showToast(res.message || 'Leave request rejected');
                    setTimeout(() => location.reload(), 800);
                },
                error: function(err) {
                    const msg = err.responseJSON?.message || 'Error rejecting leave.';
                    showToast(msg, false);
                }
            });
        });
    });

    function openApplyModal() {
        $('#applyLeaveForm')[0].reset();
        $('#calculatedDaysInput').val('0 Days');
        applyModal.show();
    }

    function managerApprove(id) {
        if (!confirm('Approve this leave request as Manager?')) return;
        $.ajax({
            url: `/leave-requests/${id}/manager-approve`,
            type: 'PUT',
            success: function(res) {
                showToast(res.message || 'Manager approved!');
                setTimeout(() => location.reload(), 800);
            },
            error: function(err) {
                showToast(err.responseJSON?.message || 'Error approving leave.', false);
            }
        });
    }

    function hrApprove(id) {
        if (!confirm('Approve this leave request as HR and deduct balance?')) return;
        $.ajax({
            url: `/leave-requests/${id}/hr-approve`,
            type: 'PUT',
            success: function(res) {
                showToast(res.message || 'HR approved and balance deducted!');
                setTimeout(() => location.reload(), 800);
            },
            error: function(err) {
                showToast(err.responseJSON?.message || 'Error approving leave.', false);
            }
        });
    }

    function openRejectModal(id) {
        $('#rejectReqId').val(id);
        $('#rejectionReason').val('');
        rejectModal.show();
    }

    function confirmCancel(id) {
        if (!confirm('Are you sure you want to cancel this leave request? If approved, balance will be restored.')) return;

        $.ajax({
            url: `/leave-requests/${id}/cancel`,
            type: 'POST',
            success: function(res) {
                showToast(res.message || 'Leave request cancelled.');
                setTimeout(() => location.reload(), 800);
            },
            error: function(err) {
                showToast(err.responseJSON?.message || 'Error cancelling leave.', false);
            }
        });
    }

    function viewDetails(req) {
        const html = `
            <div class="row g-3">
                <div class="col-md-6"><strong>Employee:</strong> ${req.employee?.name} (${req.employee?.employee_code})</div>
                <div class="col-md-6"><strong>Department:</strong> ${req.employee?.department}</div>
                <div class="col-md-6"><strong>Leave Type:</strong> ${req.leave_type?.name}</div>
                <div class="col-md-6"><strong>Calculated Days:</strong> ${req.actual_leave_days} Working Day(s)</div>
                <div class="col-md-6"><strong>From Date:</strong> ${req.from_date}</div>
                <div class="col-md-6"><strong>To Date:</strong> ${req.to_date}</div>
                <div class="col-12"><strong>Status:</strong> <span class="badge badge-status badge-${req.status}">${req.status}</span></div>
                <div class="col-12"><strong>Reason:</strong> <p class="text-muted bg-light p-2 rounded">${req.reason}</p></div>
                ${req.rejection_reason ? `<div class="col-12 text-danger"><strong>Rejection Reason:</strong> <p class="bg-light p-2 rounded">${req.rejection_reason}</p></div>` : ''}
            </div>
            <hr>
            <h6>Workflow History Timeline:</h6>
            <ul class="timeline list-unstyled ps-3">
                <li class="mb-2"><i class="fa-solid fa-circle-check text-success me-2"></i><strong>Submitted:</strong> Status PENDING</li>
                ${req.status === 'MANAGER_APPROVED' || req.status === 'HR_APPROVED' || req.status === 'APPROVED' ? `<li class="mb-2"><i class="fa-solid fa-circle-check text-info me-2"></i><strong>Manager Reviewed:</strong> Status MANAGER_APPROVED</li>` : ''}
                ${req.status === 'APPROVED' ? `<li class="mb-2"><i class="fa-solid fa-circle-check text-primary me-2"></i><strong>HR Reviewed:</strong> Status APPROVED & Balance Deducted</li>` : ''}
                ${req.status === 'REJECTED' ? `<li class="mb-2"><i class="fa-solid fa-circle-xmark text-danger me-2"></i><strong>Rejected:</strong> Request Rejected</li>` : ''}
                ${req.status === 'CANCELLED' ? `<li class="mb-2"><i class="fa-solid fa-ban text-secondary me-2"></i><strong>Cancelled:</strong> Request Cancelled & Balance Restored</li>` : ''}
            </ul>
        `;

        $('#detailsContent').html(html);
        detailsModal.show();
    }
</script>
@endsection
