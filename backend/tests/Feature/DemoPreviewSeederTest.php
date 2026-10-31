<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB,Storage};
use Database\Seeders\DemoPreviewSeeder;
use App\Models\{User,DrivingSchool};
class DemoPreviewSeederTest extends TestCase {
    use RefreshDatabase;
    public function test_preview_is_repeatable_preserves_existing_data_and_renders():void {
        Storage::fake('public'); Storage::fake('protected-media');
        $existing=User::factory()->create(['name'=>'Existing user','is_active'=>true]);
        $this->seed(DemoPreviewSeeder::class);
        $counts=[];
        foreach(['users','driving_schools','driving_lessons','lesson_bookings','school_student_exams','school_student_licences','school_class_sessions','media'] as $table) $counts[$table]=DB::table($table)->count();
        $this->seed(DemoPreviewSeeder::class);
        foreach($counts as $table=>$count) $this->assertDatabaseCount($table,$count);
        $this->assertSame('Existing user',$existing->fresh()->name);
        $student=User::where('email','demo.student@example.test')->firstOrFail();
        $school=DrivingSchool::where('status','Approved')->firstOrFail();
        $this->actingAs($student)->get(route('learn.school.students.show',[$school->id,$student->id]))->assertOk()->assertSee('Scheduled');
        $this->get(route('learn.lessons.bookings'))->assertOk()->assertSee('Lesson receipt');
        $this->get(route('learn.lessons'))->assertOk()->assertSee('[DEMO]');
        $this->assertDatabaseHas('school_lesson_payments',['gateway'=>'demo','live'=>false]);
    }
    public function test_production_is_rejected():void {
        $this->app->instance('env','production');
        $this->expectException(\RuntimeException::class);
        (new DemoPreviewSeeder)->run();
    }
}
