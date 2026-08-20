@extends('layouts.app')

@section('page-title', 'Holiday Calendar')

@section('content')
<div class="container-fluid">

    <div class="card-custom p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <div>
                <h5 class="fw-bold m-0"><i class="fa-solid fa-umbrella-beach text-indigo me-2"></i>Company Holiday Calendar</h5>
                <p class="text-muted mb-0 fs-6">Configured holidays are automatically excluded from employee leave day deductions.</p>
            </div>
            <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="openCreateModal()">
                <i class="fa-solid fa-plus me-1"></i> Add Holiday
            </button>
        </div>

        <form method="GET" action="{{ route('holidays.index') }}" class="row g-3">
            <div class="col-md-4">
                <select name="year" class="form-select bg-light">
                    <option value="">All Years</option>
                    @for($y = 2024; $y <= 2030; $y++)
                        <option value="{{ $y }}" {{ request('year', 2026) == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-4">
                <select name="month" class="form-select bg-light">
                    <option value="">All Months</option>
                    @foreach(range(1, 12) as $m)
                        <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 10)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-secondary w-100"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                <a href="{{ route('holidays.index') }}" class="btn btn-outline-secondary"><i class="fa-solid fa-rotate-left"></i></a>
            </div>
        </form>
    </div>

    <div class="card-custom p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Holiday Date</th>
                        <th>Day of Week</th>
                        <th>Holiday Name</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($holidays as $h)
                    @php $carbonDate = \Carbon\Carbon::parse($h->holiday_date); @endphp
                    <tr>
                        <td>
                            <span class="badge bg-indigo-100 text-indigo-800 fs-6 py-2 px-3" style="background:#e0e7ff; color:#3730a3;">
                                <i class="fa-regular fa-calendar me-2"></i>{{ $carbonDate->format('F d, Y') }}
                            </span>
                        </td>
                        <td><span class="fw-semibold text-secondary">{{ $carbonDate->format('l') }}</span></td>
                        <td class="fw-bold text-dark fs-6">{{ $h->holiday_name }}</td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick='openEditModal(@json($h))'>
                                <i class="fa-solid fa-pen-to-square"></i> Edit
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDelete({{ $h->id }}, '{{ $h->holiday_name }}')">
                                <i class="fa-solid fa-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">No holidays found for selected criteria.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Holiday Modal -->
<div class="modal fade" id="holidayModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-4">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="modalTitle">Add Holiday</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="holidayForm">
                @csrf
                <input type="hidden" id="holidayId">
                <input type="hidden" id="formMethod" value="POST">

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Holiday Date <span class="text-danger">*</span></label>
                        <input type="date" id="holidayDate" name="holiday_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Holiday Name <span class="text-danger">*</span></label>
                        <input type="text" id="holidayName" name="holiday_name" class="form-control" required placeholder="e.g., Independence Day">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Save Holiday</button>
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
                <p class="m-0">Are you sure you want to delete holiday <strong id="deleteHolidayName"></strong>?</p>
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
    let holidayModal, deleteModal, targetDeleteId;

    $(document).ready(function() {
        holidayModal = new bootstrap.Modal(document.getElementById('holidayModal'));
        deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

        $('#holidayForm').on('submit', function(e) {
            e.preventDefault();

            const isEdit = $('#formMethod').val() === 'PUT';
            const hId = $('#holidayId').val();
            const url = isEdit ? `/holidays/${hId}` : '/holidays';
            const method = isEdit ? 'PUT' : 'POST';

            const payload = {
                holiday_date: $('#holidayDate').val(),
                holiday_name: $('#holidayName').val(),
            };

            $.ajax({
                url: url,
                type: method,
                data: payload,
                success: function(res) {
                    holidayModal.hide();
                    showToast(res.message || 'Holiday saved successfully');
                    setTimeout(() => location.reload(), 800);
                },
                error: function(err) {
                    const msg = err.responseJSON?.message || 'Error saving holiday.';
                    showToast(msg, false);
                }
            });
        });

        $('#confirmDeleteBtn').on('click', function() {
            if (!targetDeleteId) return;

            $.ajax({
                url: `/holidays/${targetDeleteId}`,
                type: 'DELETE',
                success: function(res) {
                    deleteModal.hide();
                    showToast('Holiday deleted successfully');
                    setTimeout(() => location.reload(), 800);
                },
                error: function(err) {
                    showToast('Failed to delete holiday.', false);
                }
            });
        });
    });

    function openCreateModal() {
        $('#modalTitle').text('Add Holiday');
        $('#formMethod').val('POST');
        $('#holidayId').val('');
        $('#holidayForm')[0].reset();
        holidayModal.show();
    }

    function openEditModal(h) {
        $('#modalTitle').text('Edit Holiday');
        $('#formMethod').val('PUT');
        $('#holidayId').val(h.id);

        $('#holidayDate').val(h.holiday_date.substring(0, 10));
        $('#holidayName').val(h.holiday_name);

        holidayModal.show();
    }

    function confirmDelete(id, name) {
        targetDeleteId = id;
        $('#deleteHolidayName').text(name);
        deleteModal.show();
    }
</script>
@endsection
