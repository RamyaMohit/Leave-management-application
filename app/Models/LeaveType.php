<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'annual_quota',
        'requires_approval',
        'can_carry_forward',
        'max_consecutive_days',
        'notice_period_days',
    ];

    protected $casts = [
        'annual_quota' => 'decimal:2',
        'requires_approval' => 'boolean',
        'can_carry_forward' => 'boolean',
        'max_consecutive_days' => 'integer',
        'notice_period_days' => 'integer',
    ];

    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class, 'leave_type_id');
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class, 'leave_type_id');
    }
}
