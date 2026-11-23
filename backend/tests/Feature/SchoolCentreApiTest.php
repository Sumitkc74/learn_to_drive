<?php
namespace Tests\Feature;

use App\Models\{DrivingSchool, User};
use App\Support\SchoolMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// Live reproduction (Nov 20): with a valid Sanctum token for a user who is
// Owner of one Approved school and Instructor at another, these four GETs
// returned HTTP 500 live, while other API endpoints with the same token
// returned proper JSON. Mirrors the live data: media on the school (logo +
// gallery), a notification row, cross memberships.
class SchoolCentreApiTest extends TestCase {
    use RefreshDatabase;

    private function liveSetup(): array {
        $owner = User::factory()->create(['is_active' => true, 'name' => 'Test']);
        $teacher = User::factory()->create(['is_active' => true, 'name' => 'Test']);
        $nepal = DrivingSchool::create(['name' => 'Nepal Driving Center', 'address' => 'Chabahil', 'phone' => '+97714586995', 'status' => 'Approved']);
        $bagmati = DrivingSchool::create(['name' => 'Bagmati Driving Center', 'address' => 'Dyabu Marg', 'phone' => '+97714022567', 'status' => 'Approved']);
        $nepal->memberships()->create(['user_id' => $owner->id, 'role' => 'Owner']);
        $bagmati->memberships()->create(['user_id' => $teacher->id, 'role' => 'Owner']);
        $nepal->memberships()->create(['user_id' => $teacher->id, 'role' => 'Instructor']);
        $bagmati->memberships()->create(['user_id' => $owner->id, 'role' => 'Instructor']);
        SchoolMedia::attachLogo($nepal, UploadedFile::fake()->image('logo.png'));
        SchoolMedia::attachGallery($nepal, [
            UploadedFile::fake()->image('gallery-1.png'),
            UploadedFile::fake()->image('gallery-2.png'),
            UploadedFile::fake()->image('gallery-3.png'),
        ], []);
        SchoolMedia::attachLogo($bagmati, UploadedFile::fake()->image('logo.png'));
        DB::table('school_notifications')->insert(['user_id' => $owner->id, 'message' => 'A school invitation is waiting in your school centre.', 'created_at' => now(), 'updated_at' => now()]);
        return [$nepal, $bagmati, $owner, $teacher];
    }

    private function asToken(User $user) {
        $token = $user->createToken('school-test')->plainTextToken;
        return $this->withHeader('Authorization', 'Bearer ' . $token);
    }

    public function test_centre_index_returns_json_200_with_token(): void {
        [$nepal, $bagmati, $owner, $teacher] = $this->liveSetup();
        $this->asToken($owner)->getJson('/api/school-centre')->assertOk()
            ->assertJsonCount(2, 'data.schools')
            ->assertJsonFragment(['name' => 'Nepal Driving Center'])
            ->assertJsonFragment(['name' => 'Bagmati Driving Center']);
    }

    public function test_centre_show_returns_json_200_with_token(): void {
        [$nepal, $bagmati, $owner, $teacher] = $this->liveSetup();
        $this->asToken($owner)->getJson('/api/school-centre/' . $nepal->id)->assertOk()
            ->assertJsonPath('data.school.name', 'Nepal Driving Center')
            ->assertJsonPath('data.member.role', 'Owner');
    }

    public function test_report_returns_json_200_with_token(): void {
        [$nepal, $bagmati, $owner, $teacher] = $this->liveSetup();
        $this->asToken($owner)->getJson('/api/school-centre/' . $nepal->id . '/report')->assertOk()
            ->assertJsonPath('data.school.name', 'Nepal Driving Center');
    }

    public function test_students_index_returns_json_200_with_token(): void {
        [$nepal, $bagmati, $owner, $teacher] = $this->liveSetup();
        $this->asToken($owner)->getJson('/api/school-centre/' . $nepal->id . '/students')->assertOk();
    }

    public function test_students_index_returns_json_200_with_a_student_and_exam(): void {
        [$nepal, $bagmati, $owner, $teacher] = $this->liveSetup();
        $student = User::factory()->create(['is_active' => true]);
        $nepal->memberships()->create(['user_id' => $student->id, 'role' => 'Student']);
        DB::table('school_student_exams')->insert([
            'driving_school_id' => $nepal->id, 'user_id' => $student->id, 'category' => 'B',
            'exam_type' => 'Written', 'exam_date' => now('Asia/Kathmandu')->addDay()->toDateString(),
            'result' => 'Scheduled', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->asToken($owner)->getJson('/api/school-centre/' . $nepal->id . '/students')->assertOk()
            ->assertJsonCount(1, 'data.data');
    }

    public function test_readiness_returns_json_200_with_token(): void {
        [$nepal, $bagmati, $owner, $teacher] = $this->liveSetup();
        $student = User::factory()->create(['is_active' => true]);
        $nepal->memberships()->create(['user_id' => $student->id, 'role' => 'Student']);
        $this->asToken($owner)->getJson('/api/school-centre/' . $nepal->id . '/students/' . $student->id . '/readiness')->assertOk();
    }

    // The live probe got plain 500 HTML pages, and POST read returned the
    // home-page HTML — both signs the probe did NOT send
    // Accept: application/json, so these endpoints rendered their Blade
    // views. The API route group has no session middleware, so the learner
    // layout's $errors (shared by ShareErrorsFromSession on web only) is
    // undefined and the view throws a 500. ForceJsonResponse now makes
    // every request in this group expect JSON, so these same requests must
    // return JSON 200 instead of rendering a view.
    public function test_centre_index_returns_json_without_accept_header(): void {
        [$nepal, $bagmati, $owner, $teacher] = $this->liveSetup();
        $this->asToken($owner)->get('/api/school-centre')->assertOk()
            ->assertJsonCount(2, 'data.schools');
    }

    public function test_centre_show_returns_json_without_accept_header(): void {
        [$nepal, $bagmati, $owner, $teacher] = $this->liveSetup();
        $this->asToken($owner)->get('/api/school-centre/' . $nepal->id)->assertOk()
            ->assertJsonPath('data.school.name', 'Nepal Driving Center');
    }

    public function test_report_returns_json_without_accept_header(): void {
        [$nepal, $bagmati, $owner, $teacher] = $this->liveSetup();
        $this->asToken($owner)->get('/api/school-centre/' . $nepal->id . '/report')->assertOk()
            ->assertJsonPath('data.school.name', 'Nepal Driving Center');
    }

    public function test_students_index_returns_json_without_accept_header(): void {
        [$nepal, $bagmati, $owner, $teacher] = $this->liveSetup();
        $this->asToken($owner)->get('/api/school-centre/' . $nepal->id . '/students')->assertOk();
    }

    public function test_read_returns_json_without_accept_header(): void {
        [$nepal, $bagmati, $owner, $teacher] = $this->liveSetup();
        $this->asToken($owner)->post('/api/school-centre/read')->assertOk()
            ->assertJsonPath('message', 'Notifications marked as read.');
    }

    public function test_student_show_returns_json_without_accept_header(): void {
        [$nepal, $bagmati, $owner, $teacher] = $this->liveSetup();
        $student = User::factory()->create(['is_active' => true]);
        $nepal->memberships()->create(['user_id' => $student->id, 'role' => 'Student']);
        $this->asToken($owner)->get('/api/school-centre/' . $nepal->id . '/students/' . $student->id)->assertOk()
            ->assertJsonPath('data.canEdit', true);
    }

    // Unauthenticated requests still redirect to the login page: Laravel's
    // middleware priority runs auth:sanctum before any route-group
    // middleware, so ForceJsonResponse cannot force JSON for them. That is
    // pre-existing behaviour (a 302, not a 500) and out of scope here —
    // fixing it would need global middleware.
    public function test_unauthenticated_request_redirects_to_login_without_accept_header(): void {
        $this->get('/api/school-centre')->assertRedirect(route('login'));
    }
}
