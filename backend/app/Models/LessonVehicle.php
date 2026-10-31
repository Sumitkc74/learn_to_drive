<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class LessonVehicle extends Model
{
    protected $fillable = ['driving_school_id', 'instructor_id', 'name', 'registration', 'transmission'];
    public function school() { return $this->belongsTo(DrivingSchool::class, 'driving_school_id'); }
    public function instructor() { return $this->belongsTo(User::class, 'instructor_id'); }
}
