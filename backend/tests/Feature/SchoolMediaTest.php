<?php

namespace Tests\Feature;

use App\Models\{AppSetting, DrivingSchool, SchoolMembership, User};
use App\Support\SchoolMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SchoolMediaTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'Admin', 'is_active' => true]);
    }

    private function member(string $role = 'Owner', string $status = 'Approved'): array
    {
        $user = User::factory()->create(['is_active' => true]);
        $school = DrivingSchool::create(['name' => 'Media Academy', 'status' => $status]);
        SchoolMembership::create(['driving_school_id' => $school->id, 'user_id' => $user->id, 'role' => $role]);

        return [$user, $school];
    }

    private function logo(): UploadedFile
    {
        return UploadedFile::fake()->image('logo.png');
    }

    private function photo(): UploadedFile
    {
        // PNG only: the static test PHP has imagepng but no imagejpeg/imagewebp.
        return UploadedFile::fake()->image('photo.png');
    }

    private function video(): UploadedFile
    {
        return UploadedFile::fake()->create('clip.mp4', 100, 'video/mp4');
    }

    public function test_admin_uploads_replaces_and_removes_logo(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.schools.store'), [
            'name' => 'Logo Academy', 'logo' => $this->logo(),
        ])->assertSessionHasNoErrors();
        $school = DrivingSchool::where('name', 'Logo Academy')->firstOrFail();
        $this->assertNotNull($school->logoUrl());
        $this->assertSame(1, $school->getMedia(SchoolMedia::LOGO_COLLECTION)->count());

        $this->actingAs($admin)->patch(route('admin.schools.update', $school), [
            'name' => 'Logo Academy', 'status' => 'Pending', 'logo' => UploadedFile::fake()->image('new-logo.png'),
        ])->assertSessionHasNoErrors();
        $school->refresh();
        $this->assertSame(1, $school->getMedia(SchoolMedia::LOGO_COLLECTION)->count());
        $this->assertStringContainsString('new-logo.png', $school->logoUrl());

        $this->actingAs($admin)->patch(route('admin.schools.update', $school), [
            'name' => 'Logo Academy', 'status' => 'Pending', 'remove_logo' => '1',
        ])->assertSessionHasNoErrors();
        $school->refresh();
        $this->assertNull($school->logoUrl());
        $this->assertSame(0, $school->getMedia(SchoolMedia::LOGO_COLLECTION)->count());
    }

    public function test_admin_adds_gallery_image_and_video(): void
    {
        [$owner, $school] = $this->member();
        $this->actingAs($this->admin())->patch(route('admin.schools.update', $school), [
            'name' => $school->name, 'status' => 'Approved',
            'gallery_images' => [$this->photo()], 'gallery_videos' => [$this->video()],
        ])->assertSessionHasNoErrors();
        $gallery = $school->refresh()->getMedia(SchoolMedia::GALLERY_COLLECTION);
        $this->assertSame(2, $gallery->count());
        // The fake video has no real MP4 bytes, so its stored mime is
        // guessed, not video/* — isVideo() falls back to the extension,
        // which is what the upload rules validated.
        $this->assertTrue($gallery->contains(fn ($m) => SchoolMedia::isVideo($m)));
    }

    public function test_invalid_and_oversize_uploads_are_rejected(): void
    {
        [$owner, $school] = $this->member();
        $admin = $this->admin();
        $update = fn (array $data) => $this->actingAs($admin)->patch(route('admin.schools.update', $school), ['name' => $school->name, 'status' => 'Approved'] + $data);

        $update(['logo' => UploadedFile::fake()->create('evil.exe', 10, 'application/x-msdownload')])->assertSessionHasErrors('logo');
        $update(['logo' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')])->assertSessionHasErrors('logo');
        $update(['logo' => UploadedFile::fake()->image('big.png')->size(2049)])->assertSessionHasErrors('logo');
        $update(['gallery_videos' => [UploadedFile::fake()->create('clip.exe', 10, 'application/x-msdownload')]])->assertSessionHasErrors('gallery_videos.0');
        $this->assertSame(0, $school->refresh()->getMedia(SchoolMedia::LOGO_COLLECTION)->count());
        $this->assertSame(0, $school->getMedia(SchoolMedia::GALLERY_COLLECTION)->count());

        AppSetting::updateOrCreate(['key' => 'video_upload_limit_mb'], ['value' => '1']);
        $update(['gallery_videos' => [UploadedFile::fake()->create('big.mp4', 1025, 'video/mp4')]])->assertSessionHasErrors('gallery_videos.0');
    }

    public function test_gallery_cap_is_enforced(): void
    {
        [$owner, $school] = $this->member();
        for ($i = 0; $i < SchoolMedia::GALLERY_MAX_ITEMS; $i++) {
            $school->addMedia($this->photo())->toMediaCollection(SchoolMedia::GALLERY_COLLECTION);
        }
        $this->actingAs($this->admin())->patch(route('admin.schools.update', $school), [
            'name' => $school->name, 'status' => 'Approved', 'gallery_images' => [$this->photo()],
        ])->assertSessionHasErrors('gallery_images');
        $this->assertSame(SchoolMedia::GALLERY_MAX_ITEMS, $school->refresh()->getMedia(SchoolMedia::GALLERY_COLLECTION)->count());
    }

    public function test_application_uploads_logo_and_gallery(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user)->post(route('learn.school.apply'), [
            'name' => 'Applied Academy', 'address' => 'Kathmandu', 'phone' => '9800000000', 'email' => 'applied@example.com',
            'logo' => $this->logo(), 'gallery_images' => [$this->photo()], 'gallery_videos' => [$this->video()],
        ])->assertSessionHasNoErrors();
        $school = DrivingSchool::where('name', 'Applied Academy')->firstOrFail();
        $this->assertSame('Pending', $school->status);
        $this->assertNotNull($school->logoUrl());
        $this->assertSame(2, $school->getMedia(SchoolMedia::GALLERY_COLLECTION)->count());
    }

    public function test_application_rejects_invalid_video(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user)->post(route('learn.school.apply'), [
            'name' => 'Bad Video Academy', 'address' => 'Kathmandu', 'phone' => '9800000000', 'email' => 'bad@example.com',
            'gallery_videos' => [UploadedFile::fake()->create('clip.mov', 100, 'video/quicktime')],
        ])->assertSessionHasErrors('gallery_videos.0');
        $this->assertDatabaseMissing('driving_schools', ['name' => 'Bad Video Academy']);
    }

    public function test_public_list_renders_logo_gallery_and_video_for_approved_school_only(): void
    {
        [$owner, $school] = $this->member('Owner', 'Approved');
        $school->addMedia($this->logo())->toMediaCollection(SchoolMedia::LOGO_COLLECTION);
        $school->addMedia($this->photo())->toMediaCollection(SchoolMedia::GALLERY_COLLECTION);
        $school->addMedia($this->video())->toMediaCollection(SchoolMedia::GALLERY_COLLECTION);

        $pending = DrivingSchool::create(['name' => 'Hidden Academy', 'status' => 'Pending']);
        $pending->addMedia($this->logo())->toMediaCollection(SchoolMedia::LOGO_COLLECTION);

        $response = $this->get(route('learn.schools'))->assertOk();
        $response->assertSee($school->logoUrl(), false);
        $response->assertSee('<video', false);
        $response->assertSee('Media Academy');
        $response->assertDontSee('Hidden Academy');
        $response->assertDontSee($pending->logoUrl(), false);
    }

    public function test_public_list_shows_initial_fallback_without_logo(): void
    {
        [$owner, $school] = $this->member('Owner', 'Approved');
        $this->get(route('learn.schools'))->assertOk()->assertSee('school-logo-fallback', false)->assertSee('>M<', false);
    }

    public function test_owner_and_manager_can_manage_media_but_students_and_strangers_cannot(): void
    {
        [$owner, $school] = $this->member('Owner');
        $manager = User::factory()->create(['is_active' => true]);
        SchoolMembership::create(['driving_school_id' => $school->id, 'user_id' => $manager->id, 'role' => 'Manager']);
        $student = User::factory()->create(['is_active' => true]);
        SchoolMembership::create(['driving_school_id' => $school->id, 'user_id' => $student->id, 'role' => 'Student']);
        $stranger = User::factory()->create(['is_active' => true]);

        $action = fn ($user, $action, $data) => $this->actingAs($user)->post(route('learn.school.action', [$school->id, $action]), $data);

        $action($owner, 'logo', ['logo' => $this->logo()])->assertSessionHasNoErrors();
        $this->assertNotNull($school->refresh()->logoUrl());
        $action($manager, 'gallery', ['gallery_images' => [$this->photo()]])->assertSessionHasNoErrors();
        $this->assertSame(1, $school->refresh()->getMedia(SchoolMedia::GALLERY_COLLECTION)->count());

        $mediaId = $school->getMedia(SchoolMedia::GALLERY_COLLECTION)->first()->id;
        $action($student, 'logo', ['logo' => $this->logo()])->assertForbidden();
        $action($student, 'gallery-remove', ['media_id' => $mediaId])->assertForbidden();
        $action($stranger, 'logo', ['logo' => $this->logo()])->assertNotFound();
        $this->actingAs($student)->patch(route('admin.schools.update', $school), ['name' => $school->name, 'status' => 'Approved', 'logo' => $this->logo()])->assertForbidden();

        $action($owner, 'gallery-remove', ['media_id' => $mediaId])->assertSessionHasNoErrors();
        $this->assertSame(0, $school->refresh()->getMedia(SchoolMedia::GALLERY_COLLECTION)->count());
    }

    public function test_gallery_remove_only_deletes_this_schools_items(): void
    {
        [$owner, $school] = $this->member('Owner');
        [$otherOwner, $other] = $this->member('Owner');
        $other->addMedia($this->photo())->toMediaCollection(SchoolMedia::GALLERY_COLLECTION);
        $foreignId = $other->getMedia(SchoolMedia::GALLERY_COLLECTION)->first()->id;

        $this->actingAs($owner)->post(route('learn.school.action', [$school->id, 'gallery-remove']), ['media_id' => $foreignId])
            ->assertSessionHasNoErrors();
        $this->assertSame(1, $other->refresh()->getMedia(SchoolMedia::GALLERY_COLLECTION)->count());
    }

    public function test_deleting_school_removes_its_media(): void
    {
        $admin = $this->admin();
        $school = DrivingSchool::create(['name' => 'Doomed Academy', 'status' => 'Pending']);
        $school->addMedia($this->logo())->toMediaCollection(SchoolMedia::LOGO_COLLECTION);
        $school->addMedia($this->photo())->toMediaCollection(SchoolMedia::GALLERY_COLLECTION);
        $school->addMedia($this->video())->toMediaCollection(SchoolMedia::GALLERY_COLLECTION);

        $this->actingAs($admin)->delete(route('admin.schools.destroy', $school))->assertSessionHas('success');
        $this->assertDatabaseMissing('driving_schools', ['id' => $school->id]);
        $this->assertDatabaseMissing('media', ['model_type' => DrivingSchool::class, 'model_id' => $school->id]);
    }
}
