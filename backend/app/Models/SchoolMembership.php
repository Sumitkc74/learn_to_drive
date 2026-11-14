<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolMembership extends Model
{
    use HasFactory;

    protected $fillable = ['driving_school_id', 'user_id', 'role', 'enrollment_source', 'enrollment_fee_paisa'];
    protected $casts = ['share_progress' => 'boolean', 'enrollment_fee_paisa' => 'integer'];

    public function school()
    {
        return $this->belongsTo(DrivingSchool::class, 'driving_school_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
