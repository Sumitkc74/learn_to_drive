<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolJoinRequest extends Model
{
    use HasFactory;

    protected $fillable = ['driving_school_id', 'user_id', 'status'];

    public function school()
    {
        return $this->belongsTo(DrivingSchool::class, 'driving_school_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
