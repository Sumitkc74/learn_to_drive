<?php

namespace Tests\Feature;

use App\Models\DrivingSchool;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPaginationStyleTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::where('is_seed_admin', true)->firstOrFail();
        $this->actingAs($admin);

        return $admin;
    }

    public function test_schools_list_pagination_uses_bootstrap_markup_not_tailwind_arrows(): void
    {
        $this->admin();

        // 21 schools = 2 pages at the schools list's 20 rows per page.
        // The schools list renders pagination with ->links(), unlike the
        // shared table-pagination partial (plain Previous/Next buttons)
        // used by the questions/banks/tutorials lists.
        for ($i = 1; $i <= 21; $i++) {
            DrivingSchool::create([
                'name' => "Pagination style school {$i}",
                'address' => 'Kathmandu',
                'phone' => '+97714000000',
                'status' => 'Pending',
            ]);
        }

        $response = $this->get('/admin/schools?page=2');

        $response->assertOk();
        // Bootstrap 4 pagination markup, styled by the admin theme.
        $response->assertSee('<ul class="pagination"', false);
        $response->assertSee('page-link', false);
        // No Tailwind prev/next SVG arrows (the oversized-arrows bug).
        $response->assertDontSee('w-5 h-5', false);
    }
}
