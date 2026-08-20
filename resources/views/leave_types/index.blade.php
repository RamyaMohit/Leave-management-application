@extends('layouts.app')

@section('page-title', 'Leave Type Management')

@section('content')
<div class="container-fluid">

    <div class="card-custom p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h5 class="fw-bold m-0"><i class="fa-solid fa-sliders text-indigo me-2"></i>Configurable Leave Types</h5>
                <p class="text-muted mb-0 fs-6">Configure quotas, approval rules, carry forward rules, and notice periods.</p>
            </div>
            <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="openCreateModal()">
                <i class="fa-solid fa-plus me-1"></i> Add Leave Type
            </button>
        </div>
    </div>

    <div class="card-custom p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Annual Quota</th>
                        <th>Requires Approval</th>
                        <th>Can Carry Forward</th>
                        <th>Max Consecutive Days</th>
                        <th>Notice Period</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leaveTypes as $type)
                    <tr>
                        <td><span class="text-muted fw-bold">#{{ $type->id }}</span></td>
                        <td><span class="fw-bold text-indigo fs-6">{{ $type->name }}</span></td>
                        <td><span class="badge bg-primary rounded-pill px-3 fs-6">{{ $type->annual_quota }} Days</span></td>
                        <td>
                            @if($type->requires_approval)
                                <span class="badge bg-warning text-dark"><i class="fa-solid fa-shield-halved me-1"></i> Yes</span>
                            @else
                                <span class="badge bg-success"><i class="fa-solid fa-bolt me-1"></i> Auto-Approve</span>
                            @endif
                        </td>
                        <td>
                            @if($type->can_carry_forward)
                                <span class="badge bg-info text-dark"><i class="fa-solid fa-check me-1"></i> Allowed</span>
                            @else
                                <span class="badge bg-light text-muted">No</span>
                            @endif
                        </td>
                        <td>
                            {{ $type->max_consecutive_days ? $type->max_consecutive_days . ' Days' : 'Unlimited' }}
                        </td>
                        <td>
                            {{ $type->notice_period_days > 0 ? $type->notice_period_days . ' Day(s)' : 'None' }}
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick='openEditModal(@json($type))'>
                                <i class="fa-solid fa-pen-to-square"></i> Edit
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDelete({{ $type->id }}, '{{ $type->name }}')">
                                <i class="fa-solid fa-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">No leave types configured yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Leave Type Modal -->
<div class="modal fade" id="leaveTypeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="modalTitle">Add Leave Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="leaveTypeForm">
                @csrf
                <input type="hidden" id="leaveTypeId">
                <input type="hidden" id="formMethod" value="POST">

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Leave Type Name <span class="text-danger">*</span></label>
                            <input type="text" id="typeName" name="name" class="form-control" required placeholder="e.g. CASUAL, SICK, MATERNITY">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Annual Quota (Days) <span class="text-danger">*</span></label>
                            <input type="number" step="0.5" id="typeQuota" name="annual_quota" class="form-control" required placeholder="12.0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Requires Approval <span class="text-danger">*</span></label>
                            <select id="typeRequiresApproval" name="requires_approval" class="form-select" required>
                                <option value="1">Yes (Pending Manager & HR Approval)</option>
                                <option value="0">No (Auto Approve Immediately)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Can Carry Forward <span class="text-danger">*</span></label>
                            <select id="typeCarryForward" name="can_carry_forward" class="form-select" required>
                                <option value="0">No</option>
                                <option value="1">Yes (Allow unused days roll over)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Max Consecutive Days</label>
                            <input type="number" id="typeMaxConsecutive" name="max_consecutive_days" class="form-control" placeholder="Optional (e.g., 5)">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Notice Period (Days) <span class="text-danger">*</span></label>
                            <input type="number" id="typeNoticePeriod" name="notice_period_days" class="form-control" value="0" required placeholder="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Save Leave Type</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-4">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold">Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="m-0">Are you sure you want to delete leave type <strong id="deleteTypeName"></strong>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger rounded-pill px-4" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let leaveTypeModal, deleteModal, targetDeleteId;

    $(document).ready(function() {
        leaveTypeModal = new bootstrap.Modal(document.getElementById('leaveTypeModal'));
        deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

        $('#leaveTypeForm').on('submit', function(e) {
            e.preventDefault();

            const isEdit = $('#formMethod').val() === 'PUT';
            const typeId = $('#leaveTypeId').val();
            const url = isEdit ? `/leave-types/${typeId}` : '/leave-types';
            const method = isEdit ? 'PUT' : 'POST';

            const payload = {
                name: $('#typeName').val(),
                annual_quota: $('#typeQuota').val(),
                requires_approval: $('#typeRequiresApproval').val(),
                can_carry_forward: $('#typeCarryForward').val(),
                max_consecutive_days: $('#typeMaxConsecutive').val() || null,
                notice_period_days: $('#typeNoticePeriod').val() || 0,
            };

            $.ajax({
                url: url,
                type: method,
                data: payload,
                success: function(res) {
                    leaveTypeModal.hide();
                    showToast(res.message || 'Leave type saved successfully');
                    setTimeout(() => location.reload(), 800);
                },
                error: function(err) {
                    const msg = err.responseJSON?.message || 'Error saving leave type.';
                    showToast(msg, false);
                }
            });
        });

        $('#confirmDeleteBtn').on('click', function() {
            if (!targetDeleteId) return;

            $.ajax({
                url: `/leave-types/${targetDeleteId}`,
                type: 'DELETE',
                success: function(res) {
                    deleteModal.hide();
                    showToast('Leave type deleted successfully');
                    setTimeout(() => location.reload(), 800);
                },
                error: function(err) {
                    showToast('Failed to delete leave type.', false);
                }
            });
        });
    });

    function openCreateModal() {
        $('#modalTitle').text('Add Leave Type');
        $('#formMethod').val('POST');
        $('#leaveTypeId').val('');
        $('#leaveTypeForm')[0].reset();
        leaveTypeModal.show();
    }

    function openEditModal(type) {
        $('#modalTitle').text('Edit Leave Type');
        $('#formMethod').val('PUT');
        $('#leaveTypeId').val(type.id);

        $('#typeName').val(type.name);
        $('#typeQuota').val(type.annual_quota);
        $('#typeRequiresApproval').val(type.requires_approval ? '1' : '0');
        $('#typeCarryForward').val(type.can_carry_forward ? '1' : '0');
        $('#typeMaxConsecutive').val(type.max_consecutive_days || '');
        $('#typeNoticePeriod').val(type.notice_period_days);

        leaveTypeModal.show();
    }

    function confirmDelete(id, name) {
        targetDeleteId = id;
        $('#deleteTypeName').text(name);
        deleteModal.show();
    }
</script>
@endsection
