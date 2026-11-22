<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PrivateLessonRequest extends Model
{
    protected $fillable = ['learner_id', 'driving_school_id', 'instructor_id', 'lesson_vehicle_id', 'driving_lesson_id', 'starts_at', 'duration_minutes', 'meeting_point', 'note', 'status', 'decline_reason'];
    protected $casts = ['starts_at' => 'datetime', 'duration_minutes' => 'integer'];
    public function learner() { return $this->belongsTo(User::class, 'learner_id'); }
    public function instructor() { return $this->belongsTo(User::class, 'instructor_id'); }
    public function school() { return $this->belongsTo(DrivingSchool::class, 'driving_school_id'); }
    public function vehicle() { return $this->belongsTo(LessonVehicle::class, 'lesson_vehicle_id'); }
    public function lesson() { return $this->belongsTo(DrivingLesson::class, 'driving_lesson_id'); }
    public function endsAt() { return $this->starts_at->copy()->addMinutes((int) $this->duration_minutes); }
}
