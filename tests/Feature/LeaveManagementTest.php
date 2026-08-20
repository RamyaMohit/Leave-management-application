<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\HolidayCalculatorService;
use App\Services\LeaveBalanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Exception;

class LeaveManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $hrUser;
    protected User $managerUser;
    protected User $employeeUser;

    protected Employee $adminEmp;
    protected Employee $hrEmp;
    protected Employee $managerEmp;
    protected Employee $employeeEmp;

    protected LeaveType $casualLeave;
    protected LeaveType $autoApproveLeave;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Leave Types
        $this->casualLeave = LeaveType::create([
            'name' => 'CASUAL',
            'annual_quota' => 12.00,
            'requires_approval' => true,
            'can_carry_forward' => false,
            'max_consecutive_days' => 10,
            'notice_period_days' => 0,
        ]);

        $this->autoApproveLeave = LeaveType::create([
            'name' => 'AUTO_LEAVE',
            'annual_quota' => 5.00,
            'requires_approval' => false,
            'can_carry_forward' => false,
            'max_consecutive_days' => 5,
            'notice_period_days' => 0,
        ]);

        // 2. Setup Users & Employees
        $this->adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@company.com',
            'password' => bcrypt('password'),
            'role' => 'ADMIN',
        ]);
        $this->adminEmp = Employee::create([
            'user_id' => $this->adminUser->id,
            'employee_code' => 'EMP001',
            'name' => 'Admin User',
            'email' => 'admin@company.com',
            'department' => 'Management',
            'designation' => 'Director',
            'joining_date' => '2020-01-01',
            'status' => 'ACTIVE',
        ]);

        $this->hrUser = User::create([
            'name' => 'HR User',
            'email' => 'hr@company.com',
            'password' => bcrypt('password'),
            'role' => 'HR',
        ]);
        $this->hrEmp = Employee::create([
            'user_id' => $this->hrUser->id,
            'employee_code' => 'EMP002',
            'name' => 'HR User',
            'email' => 'hr@company.com',
            'department' => 'HR',
            'designation' => 'HR Lead',
            'joining_date' => '2021-01-01',
            'status' => 'ACTIVE',
        ]);

        $this->managerUser = User::create([
            'name' => 'Manager User',
            'email' => 'manager@company.com',
            'password' => bcrypt('password'),
            'role' => 'MANAGER',
        ]);
        $this->managerEmp = Employee::create([
            'user_id' => $this->managerUser->id,
            'employee_code' => 'EMP003',
            'name' => 'Manager User',
            'email' => 'manager@company.com',
            'department' => 'IT',
            'designation' => 'Engineering Lead',
            'joining_date' => '2021-06-01',
            'status' => 'ACTIVE',
        ]);

        $this->employeeUser = User::create([
            'name' => 'Employee User',
            'email' => 'employee@company.com',
            'password' => bcrypt('password'),
            'role' => 'EMPLOYEE',
        ]);
        $this->employeeEmp = Employee::create([
            'user_id' => $this->employeeUser->id,
            'employee_code' => 'EMP004',
            'name' => 'Employee User',
            'email' => 'employee@company.com',
            'department' => 'IT',
            'designation' => 'Developer',
            'manager_id' => $this->managerEmp->id,
            'joining_date' => '2022-01-01',
            'status' => 'ACTIVE',
        ]);

        // Initialize Balances
        $balanceService = app(LeaveBalanceService::class);
        $currentYear = Carbon::now()->year;
        $balanceService->getOrInitBalance($this->employeeEmp, $this->casualLeave, $currentYear);
        $balanceService->getOrInitBalance($this->employeeEmp, $this->autoApproveLeave, $currentYear);
    }

    /** 1. Valid leave application creates PENDING request */
    public function test_valid_leave_application_creates_pending_request(): void
    {
        // Choose next Monday to Wednesday
        $fromDate = Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d');
        $toDate = Carbon::now()->next(Carbon::WEDNESDAY)->format('Y-m-d');

        $response = $this->actingAs($this->employeeUser)
            ->postJson('/api/leave-requests', [
                'leave_type_id' => $this->casualLeave->id,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'reason' => 'Family vacation',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('leave_request.status', 'PENDING');

        $this->assertDatabaseHas('leave_requests', [
            'employee_id' => $this->employeeEmp->id,
            'leave_type_id' => $this->casualLeave->id,
            'status' => 'PENDING',
        ]);
    }

    /** 2. Insufficient balance returns validation error */
    public function test_insufficient_balance_returns_validation_error(): void
    {
        // Reduce employee's balance to 1 day
        $currentYear = Carbon::now()->year;
        LeaveBalance::where('employee_id', $this->employeeEmp->id)
            ->where('leave_type_id', $this->casualLeave->id)
            ->where('year', $currentYear)
            ->update(['remaining_days' => 1.00]);

        // Request 3 working days (Monday to Wednesday)
        $fromDate = Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d');
        $toDate = Carbon::now()->next(Carbon::WEDNESDAY)->format('Y-m-d');

        $response = $this->actingAs($this->employeeUser)
            ->postJson('/api/leave-requests', [
                'leave_type_id' => $this->casualLeave->id,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'reason' => 'Personal work',
            ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Insufficient leave balance. Requested: 3 day(s), Available: 1.00 day(s).']);
    }

    /** 3. Overlapping leave dates return validation error */
    public function test_overlapping_leave_dates_return_validation_error(): void
    {
        $fromDate = Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d');
        $toDate = Carbon::now()->next(Carbon::WEDNESDAY)->format('Y-m-d');

        // Create initial pending request
        LeaveRequest::create([
            'employee_id' => $this->employeeEmp->id,
            'leave_type_id' => $this->casualLeave->id,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'actual_leave_days' => 3,
            'reason' => 'Existing leave',
            'status' => 'PENDING',
        ]);

        // Attempt to create overlapping leave
        $response = $this->actingAs($this->employeeUser)
            ->postJson('/api/leave-requests', [
                'leave_type_id' => $this->casualLeave->id,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'reason' => 'Overlapping leave',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'You already have an active or pending leave request overlapping with the selected dates.');
    }

    /** 4. Weekend and holiday calculation excludes non-working days */
    public function test_weekend_and_holiday_calculation_excludes_non_working_days(): void
    {
        // Monday (2026-08-17) to Monday (2026-08-24)
        // Span contains Saturday (Aug 22), Sunday (Aug 23) and Holiday (Aug 21)
        Holiday::create([
            'holiday_date' => '2026-08-21',
            'holiday_name' => 'Company Foundation Day',
        ]);

        $service = new HolidayCalculatorService();
        $workingDays = $service->calculateWorkingDays('2026-08-17', '2026-08-24');

        // Total 8 calendar days: 17(Mon), 18(Tue), 19(Wed), 20(Thu), 21(Fri Holiday), 22(Sat), 23(Sun), 24(Mon)
        // Excluded: 21 (Holiday), 22 (Sat), 23 (Sun) -> Remaining working days = 5
        $this->assertEquals(5.0, $workingDays);
    }

    /** 5. Unauthorized approval attempt is blocked (403) */
    public function test_unauthorized_approval_attempt_is_blocked(): void
    {
        $req = LeaveRequest::create([
            'employee_id' => $this->employeeEmp->id,
            'leave_type_id' => $this->casualLeave->id,
            'from_date' => '2026-09-01',
            'to_date' => '2026-09-02',
            'actual_leave_days' => 2,
            'reason' => 'Test',
            'status' => 'PENDING',
        ]);

        // Employee trying to approve own request
        $response = $this->actingAs($this->employeeUser)
            ->putJson("/api/leave-requests/{$req->id}/manager-approve");

        $response->assertStatus(403);
    }

    /** 6. Manager approval stage transition to MANAGER_APPROVED */
    public function test_manager_approval_changes_status_to_manager_approved(): void
    {
        $req = LeaveRequest::create([
            'employee_id' => $this->employeeEmp->id,
            'leave_type_id' => $this->casualLeave->id,
            'from_date' => '2026-09-01',
            'to_date' => '2026-09-02',
            'actual_leave_days' => 2,
            'reason' => 'Test',
            'status' => 'PENDING',
        ]);

        $response = $this->actingAs($this->managerUser)
            ->putJson("/api/leave-requests/{$req->id}/manager-approve");

        $response->assertStatus(200)
            ->assertJsonPath('leave_request.status', 'MANAGER_APPROVED');

        $this->assertDatabaseHas('leave_requests', [
            'id' => $req->id,
            'status' => 'MANAGER_APPROVED',
        ]);
    }

    /** 7. HR approval changes status to APPROVED and deducts leave balance */
    public function test_hr_approval_changes_status_to_approved_and_deducts_balance(): void
    {
        $req = LeaveRequest::create([
            'employee_id' => $this->employeeEmp->id,
            'leave_type_id' => $this->casualLeave->id,
            'from_date' => '2026-09-01',
            'to_date' => '2026-09-02',
            'actual_leave_days' => 2,
            'reason' => 'Test',
            'status' => 'MANAGER_APPROVED',
        ]);

        $response = $this->actingAs($this->hrUser)
            ->putJson("/api/leave-requests/{$req->id}/hr-approve");

        $response->assertStatus(200)
            ->assertJsonPath('leave_request.status', 'APPROVED');

        $this->assertDatabaseHas('leave_balances', [
            'employee_id' => $this->employeeEmp->id,
            'leave_type_id' => $this->casualLeave->id,
            'remaining_days' => 10.00, // 12 - 2
            'used_days' => 2.00,
        ]);
    }

    /** 8. Transaction rollback when balance deduction fails */
    public function test_transaction_rollback_when_balance_deduction_fails(): void
    {
        // Set balance to 1 day
        $currentYear = Carbon::now()->year;
        LeaveBalance::where('employee_id', $this->employeeEmp->id)
            ->where('leave_type_id', $this->casualLeave->id)
            ->where('year', $currentYear)
            ->update(['remaining_days' => 1.00]);

        // Request requiring 3 days
        $req = LeaveRequest::create([
            'employee_id' => $this->employeeEmp->id,
            'leave_type_id' => $this->casualLeave->id,
            'from_date' => '2026-09-01',
            'to_date' => '2026-09-03',
            'actual_leave_days' => 3,
            'reason' => 'Exceeding balance',
            'status' => 'MANAGER_APPROVED',
        ]);

        $this->expectException(Exception::class);

        $service = app(LeaveBalanceService::class);
        $service->deductBalance($req);

        // Verify status was not altered
        $this->assertDatabaseHas('leave_requests', [
            'id' => $req->id,
            'status' => 'MANAGER_APPROVED',
        ]);
    }

    /** 9. Integration API creates/updates employee idempotently without duplicates */
    public function test_integration_api_creates_and_updates_idempotently(): void
    {
        $payload = [
            'employee_code' => 'EMP999',
            'name' => 'Sync User',
            'email' => 'sync@company.com',
            'department' => 'Finance',
            'designation' => 'Analyst',
            'manager_code' => 'EMP003',
        ];

        // 1st request - Create
        $res1 = $this->postJson('/api/integration/employee', $payload);
        $res1->assertStatus(201)
            ->assertJsonPath('created', true);

        $this->assertDatabaseHas('employees', [
            'employee_code' => 'EMP999',
            'department' => 'Finance',
        ]);

        // 2nd request - Update department
        $payload['department'] = 'Finance Ops';
        $res2 = $this->postJson('/api/integration/employee', $payload);
        $res2->assertStatus(200)
            ->assertJsonPath('created', false);

        $this->assertDatabaseHas('employees', [
            'employee_code' => 'EMP999',
            'department' => 'Finance Ops',
        ]);

        // Ensure count is exactly 1 (no duplicate)
        $this->assertEquals(1, Employee::where('employee_code', 'EMP999')->count());
    }

    /** 10. Cancelling an approved leave restores consumed leave days */
    public function test_cancelling_approved_leave_restores_consumed_leave_days(): void
    {
        // Create an approved leave request for 2 days
        $req = LeaveRequest::create([
            'employee_id' => $this->employeeEmp->id,
            'leave_type_id' => $this->casualLeave->id,
            'from_date' => '2026-09-01',
            'to_date' => '2026-09-02',
            'actual_leave_days' => 2,
            'reason' => 'Approved leave to cancel',
            'status' => 'APPROVED',
        ]);

        // Deduct balance manually to simulate prior HR approval state
        $balanceService = app(LeaveBalanceService::class);
        $balanceService->deductBalance($req);

        // Verify balance after deduction (12 - 2 = 10)
        $this->assertDatabaseHas('leave_balances', [
            'employee_id' => $this->employeeEmp->id,
            'leave_type_id' => $this->casualLeave->id,
            'remaining_days' => 10.00,
        ]);

        // Cancel the leave
        $response = $this->actingAs($this->employeeUser)
            ->postJson("/api/leave-requests/{$req->id}/cancel");

        $response->assertStatus(200)
            ->assertJsonPath('leave_request.status', 'CANCELLED');

        // Verify balance restored back to 12.00
        $this->assertDatabaseHas('leave_balances', [
            'employee_id' => $this->employeeEmp->id,
            'leave_type_id' => $this->casualLeave->id,
            'remaining_days' => 12.00,
            'used_days' => 0.00,
        ]);
    }
}
