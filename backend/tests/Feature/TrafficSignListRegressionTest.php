<?php

namespace Tests\Feature;

use App\Models\TrafficSign;
use App\Models\VisionTest;
use Database\Seeders\RealContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

// Regression test for the live 500 on /learn/library/traffic-sign (Nov 19, 2026).
// Cause: library.blade.php had `@endif@elseif($media=$item->getFirstMedia())`
// on one dense line. Blade did not compile that `@elseif` (it compiled the
// `@else` prefix of it and left `if($media=...)` as literal text), so the
// compiled view evaluated `$media->getUrl()` with `$media` never assigned
// for every non-tutorial item. The suite missed it because the default seed
// creates no TrafficSign records, so every library-page test rendered the
// empty state and never executed the thumbnail branch.
class TrafficSignListRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_traffic_sign_list_renders_with_seeded_signs(): void
    {
        $this->seed(RealContentSeeder::class);
        $this->assertSame(15, TrafficSign::count());
        $sign = TrafficSign::latest('id')->firstOrFail();

        $this->get('/learn/library/traffic-sign')
            ->assertOk()
            ->assertSee($sign->name)
            ->assertSee('<img', false)
            ->assertDontSee('if($media=', false);
    }

    public function test_traffic_sign_list_renders_sign_without_media(): void
    {
        $this->seed(RealContentSeeder::class);
        TrafficSign::create(['name' => 'No image sign', 'nepaliSignName' => 'छैन', 'description' => 'No media attached', 'image' => 'test.svg']);

        $this->get('/learn/library/traffic-sign')
            ->assertOk()
            ->assertSee('Image unavailable');
    }

    public function test_traffic_sign_list_renders_when_media_files_are_missing_from_disk(): void
    {
        $this->seed(RealContentSeeder::class);
        // Live state (Nov 18 storage scan): media records exist but the 30
        // sign SVG files are gone from disk. The list must still render.
        foreach (Media::all() as $media) {
            Storage::disk($media->disk)->delete($media->getPathRelativeToRoot());
        }

        $this->get('/learn/library/traffic-sign')->assertOk();
    }

    public function test_traffic_sign_list_renders_protected_disk_media_with_missing_file(): void
    {
        config(['media-library.disk_name' => 'protected-media']);
        $sign = TrafficSign::create(['name' => 'Protected sign', 'nepaliSignName' => 'सुरक्षित', 'description' => 'On protected disk', 'image' => 'test.svg']);
        $sign->addMedia(database_path('seeders/data/traffic-signs/Nepal_road_sign_A1.svg'))->preservingOriginal()->toMediaCollection();
        $media = $sign->fresh()->getFirstMedia();
        $this->assertSame('protected-media', $media->disk);
        Storage::disk('protected-media')->delete($media->getPathRelativeToRoot());

        $this->get('/learn/library/traffic-sign')->assertOk();
    }

    public function test_vision_test_list_renders_with_an_item(): void
    {
        $test = VisionTest::create(['testNumber' => 6, 'image' => 'plate.png']);
        $test->addMedia(database_path('seeders/data/traffic-signs/Nepal_road_sign_A1.svg'))->preservingOriginal()->toMediaCollection();

        $this->get('/learn/library/vision-test')
            ->assertOk()
            ->assertSee('<img', false)
            ->assertDontSee('if($media=', false);
    }
}
