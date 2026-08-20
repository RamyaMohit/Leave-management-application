@extends('layouts.app')

@section('page-title', 'Employee Management')

@section('content')
<div class="container-fluid">

    <!-- Header Actions & Filters -->
    <div class="card-custom p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <h5 class="fw-bold m-0"><i class="fa-solid fa-users text-indigo me-2"></i>Employees</h5>
            <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="openCreateModal()">
                <i class="fa-solid fa-user-plus me-1"></i> Add Employee
            </button>
        </div>

        <form method="GET" action="{{ route('employees.index') }}" class="row g-3">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="text" name="search" class="form-control bg-light border-start-0" placeholder="Search by name, code, or email..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="department" class="form-select bg-light">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept }}" {{ request('department') == $dept ? 'selected' : '' }}>{{ $dept }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select bg-light">
                    <option value="">All Statuses</option>
                    <option value="ACTIVE" {{ request('status') == 'ACTIVE' ? 'selected' : '' }}>Active</option>
                    <option value="INACTIVE" {{ request('status') == 'INACTIVE' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-secondary w-100"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary"><i class="fa-solid fa-rotate-left"></i></a>
            </div>
        </form>
    </div>

    <!-- Employee Table -->
    <div class="card-custom p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Department</th>
                        <th>Designation</th>
                        <th>Manager</th>
                        <th>Joining Date</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $emp)
                    <tr>
                        <td><span class="badge bg-light text-dark fw-bold">{{ $emp->employee_code }}</span></td>
                        <td class="fw-semibold">{{ $emp->name }}</td>
                        <td class="text-muted fs-6">{{ $emp->email }}</td>
                        <td><span class="badge bg-indigo-100 text-indigo-800" style="background:#e0e7ff; color:#3730a3;">{{ $emp->user->role ?? 'EMPLOYEE' }}</span></td>
                        <td>{{ $emp->department }}</td>
                        <td>{{ $emp->designation }}</td>
                        <td>
                            @if($emp->manager)
                                <span class="badge bg-light text-dark">{{ $emp->manager->name }}</span>
                            @else
                                <span class="text-muted fs-6">N/A</span>
                            @endif
                        </td>
                        <td class="fs-6">{{ $emp->joining_date->format('Y-m-d') }}</td>
                        <td>
                            <span class="badge rounded-pill {{ $emp->status === 'ACTIVE' ? 'bg-success' : 'bg-secondary' }}">
                                {{ $emp->status }}
                            </span>
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick='openEditModal(@json($emp))'>
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDelete({{ $emp->id }}, '{{ $emp->name }}')">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-4 text-muted">No employee records found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $employees->withQueryString()->links() }}
        </div>
    </div>

</div>

<!-- Employee Modal -->
<div class="modal fade" id="employeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="modalTitle">Add Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="employeeForm">
                @csrf
                <input type="hidden" id="employeeId" name="employee_id_val">
                <input type="hidden" id="formMethod" value="POST">

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Employee Code <span class="text-danger">*</span></label>
                            <input type="text" id="empCode" name="employee_code" class="form-control" required placeholder="EMP001">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" id="empName" name="name" class="form-control" required placeholder="John Doe">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" id="empEmail" name="email" class="form-control" required placeholder="john@company.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">System Role <span class="text-danger">*</span></label>
                            <select id="empRole" name="role" class="form-select" required>
                                <option value="EMPLOYEE">EMPLOYEE</option>
                                <option value="MANAGER">MANAGER</option>
                                <option value="HR">HR</option>
                                <option value="ADMIN">ADMIN</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Department <span class="text-danger">*</span></label>
                            <input type="text" id="empDept" name="department" class="form-control" required placeholder="IT Engineering">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Designation <span class="text-danger">*</span></label>
                            <input type="text" id="empDesig" name="designation" class="form-control" required placeholder="Software Engineer">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Manager</label>
                            <select id="empManager" name="manager_id" class="form-select">
                                <option value="">No Manager (Top Level)</option>
                                @foreach($managers as $m)
                                    <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->employee_code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Joining Date <span class="text-danger">*</span></label>
                            <input type="date" id="empJoining" name="joining_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                            <select id="empStatus" name="status" class="form-select" required>
                                <option value="ACTIVE">ACTIVE</option>
                                <option value="INACTIVE">INACTIVE</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4" id="saveEmpBtn">Save Employee</button>
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
                <p class="m-0">Are you sure you want to delete employee <strong id="deleteEmpName"></strong>? This action cannot be undone.</p>
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
    let empModal, deleteModal, targetDeleteId;

    $(document).ready(function() {
        empModal = new bootstrap.Modal(document.getElementById('employeeModal'));
        deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

        $('#employeeForm').on('submit', function(e) {
            e.preventDefault();

            const isEdit = $('#formMethod').val() === 'PUT';
            const empId = $('#employeeId').val();
            const url = isEdit ? `/employees/${empId}` : '/employees';
            const method = isEdit ? 'PUT' : 'POST';

            const payload = {
                employee_code: $('#empCode').val(),
                name: $('#empName').val(),
                email: $('#empEmail').val(),
                role: $('#empRole').val(),
                department: $('#empDept').val(),
                designation: $('#empDesig').val(),
                manager_id: $('#empManager').val() || null,
                joining_date: $('#empJoining').val(),
                status: $('#empStatus').val(),
            };

            // Self manager check
            if (isEdit && payload.manager_id == empId) {
                showToast('An employee cannot be their own manager.', false);
                return;
            }

            $.ajax({
                url: url,
                type: method,
                data: payload,
                success: function(res) {
                    empModal.hide();
                    showToast(res.message || 'Employee saved successfully');
                    setTimeout(() => location.reload(), 800);
                },
                error: function(err) {
                    const msg = err.responseJSON?.message || err.responseJSON?.errors ? Object.values(err.responseJSON.errors)[0][0] : 'Error saving employee.';
                    showToast(msg, false);
                }
            });
        });

        $('#confirmDeleteBtn').on('click', function() {
            if (!targetDeleteId) return;

            $.ajax({
                url: `/employees/${targetDeleteId}`,
                type: 'DELETE',
                success: function(res) {
                    deleteModal.hide();
                    showToast('Employee deleted successfully');
                    setTimeout(() => location.reload(), 800);
                },
                error: function(err) {
                    showToast('Failed to delete employee.', false);
                }
            });
        });
    });

    function openCreateModal() {
        $('#modalTitle').text('Add Employee');
        $('#formMethod').val('POST');
        $('#employeeId').val('');
        $('#employeeForm')[0].reset();
        empModal.show();
    }

    function openEditModal(emp) {
        $('#modalTitle').text('Edit Employee');
        $('#formMethod').val('PUT');
        $('#employeeId').val(emp.id);

        $('#empCode').val(emp.employee_code);
        $('#empName').val(emp.name);
        $('#empEmail').val(emp.email);
        $('#empRole').val(emp.user?.role || 'EMPLOYEE');
        $('#empDept').val(emp.department);
        $('#empDesig').val(emp.designation);
        $('#empManager').val(emp.manager_id || '');
        $('#empJoining').val(emp.joining_date.substring(0, 10));
        $('#empStatus').val(emp.status);

        empModal.show();
    }

    function confirmDelete(id, name) {
        targetDeleteId = id;
        $('#deleteEmpName').text(name);
        deleteModal.show();
    }
</script>
@endsection
