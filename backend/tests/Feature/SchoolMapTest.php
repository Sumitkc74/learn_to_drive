<?php

namespace Tests\Feature;

use App\Models\{DrivingSchool, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_map_only_lists_approved_schools_and_public_details(): void
    {
        DrivingSchool::create(['name' => 'Mapped Academy', 'status' => 'Approved', 'address' => 'Kathmandu', 'latitude' => 27.7, 'longitude' => 85.3, 'email' => 'private@example.com']);
        DrivingSchool::create(['name' => 'Unmapped Academy', 'status' => 'Approved']);
        DrivingSchool::create(['name' => 'Pending Academy', 'status' => 'Pending', 'latitude' => 27, 'longitude' => 85]);
        DrivingSchool::create(['name' => 'Suspended Academy', 'status' => 'Suspended']);
        $this->get(route('learn.schools'))->assertOk()->assertSee('Mapped Academy')->assertSee('Unmapped Academy')
            ->assertSee('data-lat="27.7"', false)->assertSee('Location not added yet')
            ->assertDontSee('private@example.com')->assertDontSee('Pending Academy')->assertDontSee('Suspended Academy');
        $this->get(route('learn.schools', ['q' => 'Kathmandu']))->assertOk()->assertSee('Mapped Academy')->assertDontSee('Unmapped Academy');
    }

    public function test_school_names_are_escaped_and_empty_search_is_supported(): void
    {
        DrivingSchool::create(['name' => '<script>alert(1)</script>', 'status' => 'Approved', 'latitude' => 0, 'longitude' => 0]);
        $this->get(route('learn.schools'))->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false)->assertSee('data-lat="0"', false);
        $this->get(route('learn.schools', ['q' => 'No match']))->assertOk()->assertSee('No approved schools match');
    }

    public function test_admin_coordinates_require_a_valid_pair_and_can_be_cleared(): void
    {
        $admin = User::factory()->create(['role' => 'Admin', 'is_active' => true]);
        $school = DrivingSchool::create(['name' => 'Academy']);
        $url = route('admin.schools.update', $school);
        $base = ['name' => 'Academy', 'status' => 'Pending'];
        $this->actingAs($admin)->patch($url, $base + ['latitude' => 27])->assertSessionHasErrors('longitude');
        $this->patch($url, $base + ['latitude' => 91, 'longitude' => 181])->assertSessionHasErrors(['latitude', 'longitude']);
        $this->patch($url, $base + ['latitude' => 27.7, 'longitude' => 85.3])->assertSessionHasNoErrors();
        $this->assertEquals(27.7, $school->fresh()->latitude);
        $this->patch($url, $base + ['latitude' => '', 'longitude' => ''])->assertSessionHasNoErrors();
        $this->assertNull($school->fresh()->latitude);
        $this->assertNull($school->fresh()->longitude);
    }
}
