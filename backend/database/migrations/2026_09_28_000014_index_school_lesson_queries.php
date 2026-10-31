<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const INDEXES = [
        ['driving_schools', ['status', 'name'], 'schools_status_name_idx'],
        ['school_memberships', ['user_id', 'role', 'driving_school_id'], 'memberships_staff_lookup_idx'],
        ['lesson_vehicles', ['instructor_id', 'driving_school_id'], 'vehicles_instructor_school_idx'],
        ['driving_lessons', ['lesson_vehicle_id', 'cancelled', 'starts_at', 'ends_at'], 'lessons_vehicle_schedule_idx'],
        ['driving_lessons', ['cancelled', 'starts_at'], 'lessons_availability_idx'],
        ['lesson_bookings', ['driving_lesson_id', 'status'], 'bookings_lesson_status_idx'],
        ['lesson_bookings', ['learner_id', 'status', 'id'], 'bookings_learner_status_idx'],
    ];
    public function up(): void
    {
        foreach (self::INDEXES as [$table, $columns, $name]) {
            Schema::table($table, fn (Blueprint $t) => $t->index($columns, $name));
        }
    }
    public function down(): void
    {
        foreach (array_reverse(self::INDEXES) as [$table, $columns, $name]) {
            Schema::table($table, fn (Blueprint $t) => $t->dropIndex($name));
        }
    }
};
