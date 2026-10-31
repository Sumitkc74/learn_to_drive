<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DrivingSchool extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'address', 'phone', 'email', 'status', 'latitude', 'longitude'];

    protected $casts = ['latitude' => 'float', 'longitude' => 'float'];

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
