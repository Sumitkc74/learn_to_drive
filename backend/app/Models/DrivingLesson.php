<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class DrivingLesson extends Model
{
    protected $fillable = ['lesson_vehicle_id', 'starts_at', 'ends_at', 'price_npr', 'meeting_point', 'cancelled'];
    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'cancelled' => 'boolean'];
    public function vehicle() { return $this->belongsTo(LessonVehicle::class, 'lesson_vehicle_id'); }
    public function bookings() { return $this->hasMany(LessonBooking::class); }
    public function scopeOverlapping($query, $start, $end)
    {
        return $query->where('starts_at', '<', $end)->where('ends_at', '>', $start);
    }
    public function scopeAvailableProvider($q)
    {
        return $q->whereHas('vehicle', fn ($v) => $v
            ->whereHas('school', fn ($s) => $s->where('status', 'Approved'))
            ->whereHas('instructor', fn ($u) => $u->where('is_active', true)
                ->whereHas('schoolMemberships', fn ($m) => $m->where('role', 'Instructor')
                    ->whereColumn('school_memberships.driving_school_id', 'lesson_vehicles.driving_school_id'))));
    }
}
