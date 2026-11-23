<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UploadDropzoneTest extends TestCase
{
    use RefreshDatabase;

    private function admin()
    {
        $admin = User::where('is_seed_admin', true)->firstOrFail();
        $this->actingAs($admin);

        return $admin;
    }

    public function test_bulk_import_page_renders_dropzones_with_original_inputs(): void
    {
        $this->admin();

        $response = $this->get(route('bulkImport', ['type' => 'tutorials']));

        $response->assertOk();
        $response->assertSee('js/upload-dropzone.js', false);
        $response->assertSee('css/upload.css', false);
        $response->assertSee('data-upload-field', false);
        $response->assertSee('Drag &amp; drop files here or browse', false);
        $response->assertSee('name="file"', false);
        $response->assertSee('accept=".csv,.xlsx"', false);
        $response->assertSee('name="attachments[]"', false);
    }

    public function test_learner_settings_page_renders_avatar_upload_dropzone(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user);

        $response = $this->get(route('learn.settings'));

        $response->assertOk();
        $response->assertSee('data-upload-field', false);
        $response->assertSee('name="photo"', false);
        $response->assertSee('accept=".jpg,.jpeg,.png,.webp"', false);
    }

    public function test_add_traffic_sign_page_renders_dropzone_with_original_input(): void
    {
        $this->admin();

        $response = $this->get(route('addTrafficSign'));

        $response->assertOk();
        $response->assertSee('data-upload-field', false);
        $response->assertSee('name="image"', false);
        $response->assertSee('accept=".jpg,.jpeg,.png,.webp"', false);
    }

    public function test_admin_profile_settings_renders_avatar_variant(): void
    {
        $this->admin();

        $response = $this->get(route('profileSettings'));

        $response->assertOk();
        $response->assertSee('data-upload-trigger', false);
        $response->assertSee('name="profileImage"', false);
        $response->assertSee('accept=".jpg,.jpeg,.png,.webp"', false);
    }

    public function test_no_raw_file_input_or_legacy_preview_hook_remains(): void
    {
        $views = base_path('resources/views');
        $rawInputs = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($views));
        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')
                && $file->getPathname() !== $views.'/components/upload-field.blade.php') {
                $contents = file_get_contents($file->getPathname());
                if (str_contains($contents, 'type="file"')) {
                    $rawInputs[] = $file->getPathname();
                }
                $this->assertStringNotContainsString('data-image-input', $contents, $file->getFilename());
            }
        }

        $this->assertSame([], $rawInputs, 'Raw file inputs outside the component: '.implode(', ', $rawInputs));
    }

    public function test_avatar_photo_upload_still_works_through_the_new_field(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user);

        $response = $this->patch(route('learn.settings.avatar'), [
            'photo' => \Illuminate\Http\UploadedFile::fake()->image('photo.png', 100, 100),
        ]);

        $response->assertRedirect(route('learn.settings'));
        $this->assertNotSame(asset('dist/img/avatar.png'), $user->fresh()->avatar_url);
    }
}
