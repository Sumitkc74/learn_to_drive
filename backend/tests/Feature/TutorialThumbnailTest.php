<?php

namespace Tests\Feature;

use App\Models\Tutorial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TutorialThumbnailTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'Admin']);
    }

    private function tutorial(string $videoLink): Tutorial
    {
        return Tutorial::create([
            'title' => 'Night driving',
            'description' => 'Dip your headlights for oncoming traffic.',
            'videoLink' => $videoLink,
        ]);
    }

    public function test_youtube_video_id_parses_watch_short_and_embed_links(): void
    {
        $cases = [
            'https://www.youtube.com/watch?v=BKEkpBmZGiM' => 'BKEkpBmZGiM',
            'https://www.youtube.com/watch?v=BKEkpBmZGiM&t=30s' => 'BKEkpBmZGiM',
            'https://youtube.com/watch?v=BKEkpBmZGiM' => 'BKEkpBmZGiM',
            'https://m.youtube.com/watch?v=BKEkpBmZGiM' => 'BKEkpBmZGiM',
            'https://youtu.be/BKEkpBmZGiM' => 'BKEkpBmZGiM',
            'https://youtu.be/BKEkpBmZGiM?si=abc123' => 'BKEkpBmZGiM',
            'https://www.youtube.com/shorts/ewnzE-WWGvo' => 'ewnzE-WWGvo',
            'https://www.youtube.com/embed/ewnzE-WWGvo' => 'ewnzE-WWGvo',
            'https://www.youtube-nocookie.com/embed/ewnzE-WWGvo' => 'ewnzE-WWGvo',
            'https://www.youtube.com/v/ewnzE-WWGvo' => 'ewnzE-WWGvo',
            'https://www.youtube.com/live/ewnzE-WWGvo' => 'ewnzE-WWGvo',
        ];

        foreach ($cases as $url => $id) {
            $this->assertSame($id, Tutorial::youTubeVideoId($url), "Failed to parse: {$url}");
        }
    }

    public function test_youtube_video_id_returns_null_for_missing_or_non_youtube_links(): void
    {
        $cases = [
            null,
            '',
            'not a url',
            'https://example.com/video',
            'https://vimeo.com/12345678901',
            'https://www.youtube.com/watch',
            'https://www.youtube.com/watch?x=1',
            'https://www.youtube.com/watch?v=tooshort',
            'https://youtu.be/',
            'https://www.youtube.com.evil.com/watch?v=BKEkpBmZGiM',
            'https://fakeyoutube.com/watch?v=BKEkpBmZGiM',
        ];

        foreach ($cases as $url) {
            $this->assertNull(Tutorial::youTubeVideoId($url), 'Should be null for: '.var_export($url, true));
        }
    }

    public function test_thumbnail_url_derives_from_youtube_link_when_no_media(): void
    {
        $tutorial = $this->tutorial('https://www.youtube.com/watch?v=BKEkpBmZGiM');

        $this->assertSame(
            'https://img.youtube.com/vi/BKEkpBmZGiM/hqdefault.jpg',
            $tutorial->thumbnailUrl()
        );
    }

    public function test_thumbnail_url_returns_null_without_media_or_youtube_link(): void
    {
        $this->assertNull($this->tutorial('')->thumbnailUrl());
        $this->assertNull($this->tutorial('https://example.com/video')->thumbnailUrl());
    }

    public function test_uploaded_media_wins_over_the_youtube_thumbnail(): void
    {
        Storage::fake('public');
        $tutorial = $this->tutorial('https://www.youtube.com/watch?v=BKEkpBmZGiM');
        $tutorial->addMedia(UploadedFile::fake()->image('thumb.png'))->toMediaCollection();

        $url = $tutorial->fresh()->thumbnailUrl();

        $this->assertStringContainsString('thumb.png', $url);
        $this->assertStringNotContainsString('img.youtube.com', $url);
    }

    public function test_admin_tutorials_list_shows_derived_thumbnail(): void
    {
        $this->tutorial('https://www.youtube.com/watch?v=BKEkpBmZGiM');

        $this->actingAs($this->admin())->get(route('allTutorial'))
            ->assertOk()
            ->assertSee('https://img.youtube.com/vi/BKEkpBmZGiM/hqdefault.jpg', false)
            ->assertDontSee('src=""', false);
    }

    public function test_admin_edit_page_shows_derived_thumbnail(): void
    {
        $tutorial = $this->tutorial('https://www.youtube.com/watch?v=BKEkpBmZGiM');

        $this->actingAs($this->admin())->get('/admin/edit-tutorial/'.$tutorial->id)
            ->assertOk()
            ->assertSee('https://img.youtube.com/vi/BKEkpBmZGiM/hqdefault.jpg', false);
    }

    public function test_learner_library_and_detail_show_derived_thumbnail(): void
    {
        $tutorial = $this->tutorial('https://www.youtube.com/watch?v=BKEkpBmZGiM');

        $this->get(route('learn.library', 'tutorial'))
            ->assertOk()
            ->assertSee('https://img.youtube.com/vi/BKEkpBmZGiM/hqdefault.jpg', false);

        $this->get(route('learn.detail', ['tutorial', $tutorial->id]))
            ->assertOk()
            ->assertSee('https://www.youtube-nocookie.com/embed/BKEkpBmZGiM', false)
            ->assertSee('Watch tutorial')
            ->assertDontSee('https://img.youtube.com/vi/BKEkpBmZGiM/hqdefault.jpg', false);
    }

    public function test_tutorial_detail_embeds_video_from_shorts_link(): void
    {
        $tutorial = $this->tutorial('https://www.youtube.com/shorts/ewnzE-WWGvo');

        $this->get(route('learn.detail', ['tutorial', $tutorial->id]))
            ->assertOk()
            ->assertSee('<iframe', false)
            ->assertSee('https://www.youtube-nocookie.com/embed/ewnzE-WWGvo', false)
            ->assertSee('Watch tutorial');
    }

    public function test_admin_can_create_tutorial_without_a_thumbnail_image(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('insertTutorial'), [
            'title' => 'Night driving',
            'description' => 'Dip your headlights for oncoming traffic.',
            'videoLink' => 'https://www.youtube.com/watch?v=BKEkpBmZGiM',
        ])->assertRedirect(route('allTutorial'));

        $tutorial = Tutorial::where('title', 'Night driving')->firstOrFail();
        $this->assertFalse($tutorial->hasMedia());
        $this->assertSame(
            'https://img.youtube.com/vi/BKEkpBmZGiM/hqdefault.jpg',
            $tutorial->thumbnailUrl()
        );

        $this->actingAs($admin)->get(route('allTutorial'))
            ->assertOk()
            ->assertSee('https://img.youtube.com/vi/BKEkpBmZGiM/hqdefault.jpg', false);
    }

    public function test_tutorial_without_thumbnail_or_link_renders_cleanly(): void
    {
        $tutorial = $this->tutorial('https://example.com/video');

        $this->get(route('learn.library', 'tutorial'))
            ->assertOk()
            ->assertDontSee('img.youtube.com')
            ->assertDontSee('src=""', false);

        $this->get(route('learn.detail', ['tutorial', $tutorial->id]))
            ->assertOk()
            ->assertDontSee('img.youtube.com')
            ->assertDontSee('youtube-nocookie.com/embed', false)
            ->assertDontSee('<iframe', false)
            ->assertDontSee('src=""', false);
    }
}
