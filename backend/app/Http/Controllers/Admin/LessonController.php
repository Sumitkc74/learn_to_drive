<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DrivingLesson;
use App\Models\DrivingSchool;
use App\Models\LessonBooking;
use App\Support\AdminTable;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function lessons(Request $request)
    {
        $schools = DrivingSchool::orderBy('name')->pluck('name', 'id');
        $lessons = AdminTable::paginate(
            DrivingLesson::with(['vehicle.school', 'vehicle.instructor', 'bookings' => fn ($q) => $q->where('status', 'Booked')->with('learner:id,name,email')])->withCount('bookings'),
            $request,
            ['meeting_point'],
            ['id', 'starts_at', 'ends_at', 'price_npr', 'created_at'],
            [
                'school' => [
                    'allowed' => $schools->keys()->map(fn ($id) => (string) $id)->all(),
                    'apply' => fn ($query, $value) => $query->whereHas('vehicle', fn ($v) => $v->where('driving_school_id', (int) $value)),
                ],
                'status' => [
                    'allowed' => ['scheduled', 'cancelled'],
                    'apply' => fn ($query, $value) => $query->where('cancelled', $value === 'cancelled'),
                ],
            ]
        );

        return view('admin.lessons.index', compact('lessons', 'schools'));
    }

    public function bookings(Request $request)
    {
        $schools = DrivingSchool::orderBy('name')->pluck('name', 'id');
        $bookings = AdminTable::paginate(
            LessonBooking::with(['lesson.vehicle.school', 'lesson.vehicle.instructor', 'learner:id,name,email', 'payment']),
            $request,
            ['status'],
            ['id', 'created_at'],
            [
                'school' => [
                    'allowed' => $schools->keys()->map(fn ($id) => (string) $id)->all(),
                    'apply' => fn ($query, $value) => $query->whereHas('lesson.vehicle', fn ($v) => $v->where('driving_school_id', (int) $value)),
                ],
                'status' => ['allowed' => ['Booked', 'Cancelled']],
            ]
        );

        return view('admin.lessons.bookings', compact('bookings', 'schools'));
    }
}
