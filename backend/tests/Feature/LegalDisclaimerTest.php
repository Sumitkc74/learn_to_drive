<?php

namespace Tests\Feature;

use App\Models\Notice;
use App\Models\TrafficSign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalDisclaimerTest extends TestCase
{
    use RefreshDatabase;

    private const SHORT = 'Practice questions are written for study and may not match the real exam questions.';
    private const FOOTER = 'It is not provided by the Government of Nepal or the Department of Transport Management.';

    public function test_disclaimer_on_home_page_and_footer(): void
    {
        $this->get('/')->assertOk()
            ->assertSee(self::SHORT)
            ->assertSee(self::FOOTER);
    }

    public function test_disclaimer_on_practice_start_page(): void
    {
        $this->get('/learn/practice')->assertOk()->assertSee(self::SHORT);
    }

    public function test_disclaimer_on_library_hub_and_lists(): void
    {
        $this->get('/learn/library')->assertOk()->assertSee(self::SHORT);
        $this->get('/learn/library/traffic-sign')->assertOk()->assertSee(self::SHORT);
        $this->get('/learn/library/question-bank')->assertOk()->assertSee(self::SHORT);
    }

    public function test_disclaimer_on_traffic_sign_detail_page(): void
    {
        $sign = TrafficSign::create(['name' => 'Stop and give way', 'nepaliSignName' => 'रोक्नुहोस् र बाटो दिनुहोस्', 'description' => 'Come to a complete stop.', 'image' => 'stop.svg']);

        $this->get('/learn/library/traffic-sign/'.$sign->id)->assertOk()->assertSee(self::SHORT);
    }

    public function test_notice_detail_shows_source_attribution_and_official_link(): void
    {
        $notice = Notice::create([
            'title' => 'Licence exam schedule',
            'description' => 'Summary of the official notice.',
            'nepaliTitle' => 'Licence exam schedule',
            'nepaliDescription' => 'Summary of the official notice.',
            'link' => 'https://dotm.gov.np/notice-1',
            'status' => 'Published',
            'source_name' => 'Department of Transport Management, Nepal',
            'source_url' => 'https://dotm.gov.np/notice-1',
        ]);

        $this->get('/learn/library/notice/'.$notice->id)->assertOk()
            ->assertSee('Source: Department of Transport Management, Nepal')
            ->assertSee('Open notice source')
            ->assertSee('https://dotm.gov.np/notice-1');
    }

    public function test_disclaimer_is_translated_in_nepali(): void
    {
        $this->withSession(['learner_locale' => 'ne'])->get('/')->assertOk()
            ->assertSee('यो नेपाल सरकार वा यातायात व्यवस्था विभागद्वारा प्रदान गरिएको होइन');
    }
}
