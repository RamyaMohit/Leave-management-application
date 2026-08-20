@extends('layouts.app')

@section('page-title', 'System Audit Logs')

@section('content')
<div class="container-fluid">

    <div class="card-custom p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <div>
                <h5 class="fw-bold m-0"><i class="fa-solid fa-clock-rotate-left text-indigo me-2"></i>Audit Trail & Compliance Logs</h5>
                <p class="text-muted mb-0 fs-6">Comprehensive audit tracking for all employee, leave balance, approval, and configuration operations.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('audit-logs.index') }}" class="row g-3">
            <div class="col-md-5">
                <input type="text" name="action" class="form-control bg-light" placeholder="Search by Action (e.g. DEDUCT_LEAVE_BALANCE, HR_APPROVE)..." value="{{ request('action') }}">
            </div>
            <div class="col-md-5">
                <select name="entity_type" class="form-select bg-light">
                    <option value="">All Entities</option>
                    <option value="leave_request" {{ request('entity_type') == 'leave_request' ? 'selected' : '' }}>leave_request</option>
                    <option value="employee" {{ request('entity_type') == 'employee' ? 'selected' : '' }}>employee</option>
                    <option value="leave_balance" {{ request('entity_type') == 'leave_balance' ? 'selected' : '' }}>leave_balance</option>
                    <option value="leave_type" {{ request('entity_type') == 'leave_type' ? 'selected' : '' }}>leave_type</option>
                    <option value="holiday" {{ request('entity_type') == 'holiday' ? 'selected' : '' }}>holiday</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-secondary w-100"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                <a href="{{ route('audit-logs.index') }}" class="btn btn-outline-secondary"><i class="fa-solid fa-rotate-left"></i></a>
            </div>
        </form>
    </div>

    <div class="card-custom p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Entity Type</th>
                        <th>Entity ID</th>
                        <th class="text-end">Payload Inspector</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($auditLogs as $log)
                    <tr>
                        <td><span class="text-muted fw-bold">#{{ $log->id }}</span></td>
                        <td class="fs-6 text-muted">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                        <td>
                            @if($log->user)
                                <span class="fw-semibold text-dark">{{ $log->user->name }}</span>
                            @else
                                <span class="badge bg-light text-muted">System / Guest</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-indigo-100 text-indigo-800 fw-bold" style="background:#e0e7ff; color:#3730a3;">{{ $log->action }}</span>
                        </td>
                        <td><span class="badge bg-light text-dark">{{ $log->entity_type }}</span></td>
                        <td><span class="fw-bold text-secondary">#{{ $log->entity_id ?? 'N/A' }}</span></td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick='inspectJson(@json($log))'>
                                <i class="fa-solid fa-code me-1"></i> View Values
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No audit log records found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $auditLogs->withQueryString()->links() }}
        </div>
    </div>

</div>

<!-- JSON Inspector Modal -->
<div class="modal fade" id="jsonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-code me-2"></i>Audit Payload Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <h6 class="fw-bold text-danger"><i class="fa-solid fa-clock-rotate-left me-1"></i> Old Values (Before)</h6>
                        <pre id="oldJsonView" class="bg-light p-3 rounded border" style="max-height: 350px; overflow: auto; font-size: 0.85rem;"></pre>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold text-success"><i class="fa-solid fa-circle-check me-1"></i> New Values (After)</h6>
                        <pre id="newJsonView" class="bg-light p-3 rounded border" style="max-height: 350px; overflow: auto; font-size: 0.85rem;"></pre>
                    </div>
                </div>
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
    let jsonModal;

    $(document).ready(function() {
        jsonModal = new bootstrap.Modal(document.getElementById('jsonModal'));
    });

    function inspectJson(log) {
        $('#oldJsonView').text(log.old_value ? JSON.stringify(log.old_value, null, 2) : 'No previous values recorded.');
        $('#newJsonView').text(log.new_value ? JSON.stringify(log.new_value, null, 2) : 'No new values recorded.');
        jsonModal.show();
    }
</script>
@endsection
