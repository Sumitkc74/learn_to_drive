<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class DrivingLesson extends Model
{
    protected $fillable = ['lesson_vehicle_id', 'starts_at', 'ends_at', 'price_npr', 'meeting_point', 'cancelled'];
    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'cancelled' => 'boolean'];
    public function vehicle() { return $this->belongsTo(LessonVehicle::class, 'lesson_vehicle_id'); }
    public function bookings() { return $this->hasMany(LessonBooking::class); }
    // Meeting points are often stored as "School name, area, city". Views
    // that already print the school name use this so the name is not shown
    // twice; a point that does not start with the school name is unchanged.
    public function displayMeetingPoint(): string
    {
        $point = (string) $this->meeting_point;
        $school = $this->vehicle?->school;
        if (! $school || $school->name === '' || $point === '') return $point;
        if ($point === $school->name) return '';
        foreach ([', ', ' - ', ' · '] as $separator) {
            $prefix = $school->name.$separator;
            if (str_starts_with($point, $prefix)) return mb_substr($point, mb_strlen($prefix));
        }
        return $point;
    }
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
