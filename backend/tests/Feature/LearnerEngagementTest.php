<?php
namespace Tests\Feature;

use App\Models\{User, LearnerPracticeAttempt, TrafficSign};
use App\Services\LearnerProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LearnerEngagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_progress_uses_nepal_days_and_only_own_completed_sessions(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-09-17 19:00:00', 'UTC'));
        $user=User::factory()->create(['is_active'=>true]);
        $other=User::factory()->create(['is_active'=>true]);
        foreach (['2026-09-17 18:30:00','2026-09-17 18:40:00','2026-09-16 19:00:00'] as $time) {
            LearnerPracticeAttempt::create(['user_id'=>$user->id,'questions'=>[['id'=>1],['id'=>2]],'score'=>1,'completed_at'=>$time,'expires_at'=>now()]);
        }
        LearnerPracticeAttempt::create(['user_id'=>$other->id,'questions'=>[['id'=>1]],'score'=>1,'completed_at'=>'2026-09-15 19:00:00','expires_at'=>now()]);
        LearnerPracticeAttempt::create(['user_id'=>$user->id,'questions'=>[['id'=>1]],'expires_at'=>now()]);
        $progress=app(LearnerProgress::class)->forUser($user->id);
        $this->assertTrue($progress['todayDone']);
        $this->assertSame(2,$progress['streak']);
        $this->assertSame(3,$progress['sessions']);
        $this->assertSame(6,$progress['answered']);
        $this->assertSame(50,$progress['accuracy']);
        $this->actingAs($user)->get('/learn/account')->assertOk()->assertSee('Today’s goal? Done.')->assertSee('First step');
        $this->travelTo(\Carbon\Carbon::parse('2026-09-18 19:00:00','UTC'));
        $this->assertSame(2,app(LearnerProgress::class)->forUser($user->id)['streak']);
        $this->assertFalse(app(LearnerProgress::class)->forUser($user->id)['todayDone']);
        $this->travelTo(\Carbon\Carbon::parse('2026-09-19 19:00:00','UTC'));
        $this->assertSame(0,app(LearnerProgress::class)->forUser($user->id)['streak']);
    }

    public function test_public_flashcards_reveal_real_signs_and_skip_signs_without_media(): void
    {
        Storage::fake('public');
        $this->get('/learn/sign-flashcards')->assertOk()->assertSee('More signs are on the way.');
        TrafficSign::create(['name'=>'No image','nepaliSignName'=>'No image','description'=>'Unavailable','image'=>'']);
        $sign=TrafficSign::create(['name'=>'Stop sign','nepaliSignName'=>'Stop','description'=>'Come to a stop','image'=>'']);
        $sign->addMedia(UploadedFile::fake()->image('sign.png'))->toMediaCollection('default','public');
        $this->get('/learn/sign-flashcards')->assertOk()->assertSee('Reveal the answer')->assertSee('Stop sign')->assertDontSee('No image')->assertSee('Start again');
        $this->get('/')->assertOk()->assertSee('Your next three steps')->assertSee('Try flashcards')->assertDontSee('YOUR DAILY CHALLENGE')->assertDontSee('Latest notices');
    }
}
