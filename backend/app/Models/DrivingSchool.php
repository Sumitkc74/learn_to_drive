<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DrivingSchool extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'address', 'phone', 'email', 'status', 'latitude', 'longitude',
        'commission_percent', 'enrollment_fee_paisa', 'proposed_commission_percent', 'proposed_enrollment_fee_paisa'];

    protected $casts = ['latitude' => 'float', 'longitude' => 'float',
        'commission_percent' => 'integer', 'enrollment_fee_paisa' => 'integer',
        'proposed_commission_percent' => 'integer', 'proposed_enrollment_fee_paisa' => 'integer'];

    // Agreed rates are per school. A null agreed value means the school
    // uses the platform default from config — the platform cannot change
    // a school's agreed rate unilaterally; a new rate only applies once
    // the school accepts the platform's proposal.
    public function effectiveCommissionPercent(): int
    {
        return $this->commission_percent ?? (int)config('schools.commission_percent');
    }

    public function effectiveEnrollmentFeePaisa(): int
    {
        return $this->enrollment_fee_paisa ?? (int)config('schools.enrollment_fee_paisa');
    }

    public function memberships()
    {
        return $this->hasMany(SchoolMembership::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'school_memberships')
            ->withPivot('role')
            ->withTimestamps();
    }
}
