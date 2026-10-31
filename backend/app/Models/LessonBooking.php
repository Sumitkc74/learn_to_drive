<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class LessonBooking extends Model
{
    protected $fillable = ['driving_lesson_id', 'learner_id', 'active_lesson_id', 'status'];
    public function lesson() { return $this->belongsTo(DrivingLesson::class, 'driving_lesson_id'); }
    public function learner() { return $this->belongsTo(User::class, 'learner_id'); }
    public function payment() { return $this->hasOne(SchoolLessonPayment::class); }
}
