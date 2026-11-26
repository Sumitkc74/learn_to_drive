<?php

namespace Tests\Feature;

use App\Models\LearningContentImport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Structural accessibility invariants for the admin panel (WCAG 2.1 AA).
 *
 * These checks run on the server-rendered HTML of the most-used admin
 * pages: exactly one h1 in logical heading order, no duplicate ids, a skip
 * link and a breadcrumb on every page, labelled controls, scoped table
 * headers, alt text on images, and named buttons/links.
 */
class AdminAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): void
    {
        $admin = User::where('is_seed_admin', true)->firstOrFail();
        $this->actingAs($admin);
    }

    public static function adminPages(): array
    {
        return array_map(fn ($url) => [$url], [
            '/admin',
            '/admin/analytics',
            '/admin/questions',
            '/admin/notices',
            '/admin/learning-content',
            '/admin/government-notices',
            '/admin/schools',
            '/admin/lessons',
            '/admin/lesson-bookings',
            '/admin/users',
            '/admin/premium-payments',
            '/admin/support',
            '/admin/bulk-import',
            '/admin/media-library',
            '/admin/audit-logs',
            '/admin/settings',
            '/admin/content-readiness',
            '/admin/profile-settings',
            '/admin/search?q=test',
        ]);
    }

    #[DataProvider('adminPages')]
    public function test_admin_page_meets_structural_accessibility_invariants(string $url): void
    {
        $this->admin();

        $response = $this->get($url);
        $response->assertOk();
        $this->assertAccessibleHtml($response->getContent(), $url);
    }

    public function test_learning_content_review_page_meets_structural_accessibility_invariants(): void
    {
        $this->admin();

        $resource = LearningContentImport::create([
            'source_key' => 'test-source',
            'source_name' => 'Test Source',
            'source_url' => 'https://example.com/page',
            'asset_url' => 'https://example.com/file.pdf',
            'url_hash' => hash('sha256', 'https://example.com/file.pdf'),
            'kind' => 'question-bank-document',
            'title' => 'Test question bank',
            'fetched_at' => now(),
        ]);

        $response = $this->get(route('learningContent.show', $resource));
        $response->assertOk();
        $this->assertAccessibleHtml($response->getContent(), 'learning-content show');
    }

    public function test_admin_edit_pages_have_one_h1_and_linked_labels(): void
    {
        $this->admin();

        $editUser = User::factory()->create();
        $response = $this->get('/admin/edit-user/' . $editUser->id);
        $response->assertOk();
        $this->assertAccessibleHtml($response->getContent(), 'edit-user');
    }

    public function test_sidebar_brand_link_has_explicit_focus_visible_rule(): void
    {
        // Static stylesheet assertion only: it proves the rule exists in
        // the shipped CSS, not that a browser renders it. The global
        // .ltd-body :focus-visible rule should already cover the logo
        // link, but a live check found no visible outline on it, so the
        // brand gets its own explicit rule.
        $css = file_get_contents(public_path('dist/css/admin-theme.css'));
        $this->assertNotFalse($css, 'admin-theme.css could not be read');

        $this->assertMatchesRegularExpression(
            '/\.ltd-sidebar__brand:focus-visible\s*\{[^}]*outline\s*:/',
            $css,
            'admin-theme.css must give the sidebar logo link an explicit :focus-visible outline'
        );

        // The sidebar clips overflow and the brand sits flush against its
        // edges, so only the outline's bottom edge renders live. The inset
        // shadow is the ring that always renders, in both themes.
        $this->assertMatchesRegularExpression(
            '/\.ltd-sidebar__brand:focus-visible\s*\{[^}]*box-shadow\s*:\s*inset 0 0 0 3px var\(--yellow\)/',
            $css,
            'admin-theme.css must give the sidebar logo link an inset focus ring'
        );

        $this->assertMatchesRegularExpression(
            '/\.ltd-body\.ltd-theme-dark\s+\.ltd-sidebar__brand:focus-visible\s*\{[^}]*box-shadow\s*:\s*inset 0 0 0 3px #ffde17/',
            $css,
            'admin-theme.css must give the sidebar logo link an inset focus ring in the dark theme'
        );
    }

    private function assertAccessibleHtml(string $html, string $page): void
    {
        // Exactly one h1, first heading on the page, levels never skipped.
        preg_match_all('/<h([1-6])[\s>]/', $html, $headings);
        $levels = array_map('intval', $headings[1]);
        $this->assertNotEmpty($levels, "[$page] has no headings");
        $this->assertSame(1, $levels[0], "[$page] first heading is not h1");
        $this->assertCount(1, array_filter($levels, fn ($level) => $level === 1), "[$page] must have exactly one h1");
        $previous = 0;
        foreach ($levels as $level) {
            $this->assertLessThanOrEqual($previous + 1, $level, "[$page] heading level skips from h{$previous} to h{$level}");
            $previous = $level;
        }

        // No duplicate ids.
        preg_match_all('/\bid="([^"]+)"/', $html, $ids);
        $duplicates = array_filter(array_count_values($ids[1]), fn ($count) => $count > 1);
        $this->assertSame([], $duplicates, "[$page] duplicate ids: " . implode(', ', array_keys($duplicates)));

        // Skip link, main landmark, and the shared breadcrumb.
        $this->assertStringContainsString('class="ltd-skip-link"', $html, "[$page] missing skip link");
        $this->assertStringContainsString('id="ltd-main-content"', $html, "[$page] missing main landmark id");
        $this->assertStringContainsString('aria-label="Breadcrumb"', $html, "[$page] missing breadcrumb");

        // aria-current must be a plain value, never Blade-escaped quotes
        // ({{ ... ? 'aria-current="page"' : '' }} renders &quot;).
        $this->assertStringNotContainsString('aria-current="&quot;', $html, "[$page] aria-current value is HTML-escaped");
        $this->assertStringNotContainsString('&quot;page&quot;', $html, "[$page] contains an escaped aria-current value");
        preg_match_all('/<a\b[^>]*ltd-nav__item is-active[^>]*>/', $html, $activeLinks);
        foreach ($activeLinks[0] as $link) {
            $this->assertStringContainsString('aria-current="page"', $link, "[$page] active nav link lacks aria-current=\"page\": $link");
        }

        // Every image carries alt text.
        preg_match_all('/<img\b[^>]*>/', $html, $images);
        foreach ($images[0] as $img) {
            $this->assertStringContainsString('alt=', $img, "[$page] image without alt: $img");
        }

        // Every header cell in a thead is scoped.
        preg_match_all('/<thead>.*?<\/thead>/s', $html, $heads);
        foreach ($heads[0] as $thead) {
            preg_match_all('/<th\b[^>]*>/', $thead, $ths);
            foreach ($ths[0] as $th) {
                $this->assertStringContainsString('scope="col"', $th, "[$page] th without scope: $th");
            }
        }

        // Labelled controls: every input/select/textarea that is not hidden
        // has an id matched by a label, an aria-label(lledby), or is wrapped
        // in a <label>.
        preg_match_all('/<label\b[^>]*>(.*?)<\/label>/s', $html, $labelBlocks, PREG_OFFSET_CAPTURE);
        $labelFors = [];
        preg_match_all('/<label\b[^>]*\bfor="([^"]+)"/', $html, $forMatches);
        $labelFors = $forMatches[1];
        // One label per input: a second <label for> on the same id is
        // valid HTML but leaves screen readers with an ambiguous name.
        $labelForCounts = array_count_values($labelFors);
        $labelDupes = array_filter($labelForCounts, fn ($count) => $count > 1);
        $this->assertSame([], $labelDupes, "[$page] input labelled more than once: " . implode(', ', array_keys($labelDupes)));
        $wrappedRanges = [];
        foreach ($labelBlocks[0] as $block) {
            $wrappedRanges[] = [$block[1], $block[1] + strlen($block[0])];
        }
        preg_match_all('/<(input|select|textarea)\b[^>]*>/', $html, $controls, PREG_OFFSET_CAPTURE);
        foreach ($controls[0] as [$tag, $offset]) {
            if (preg_match('/type="(hidden|submit|button)"/', $tag)) {
                continue;
            }
            if (str_contains($tag, 'aria-label') || str_contains($tag, 'aria-labelledby')) {
                continue;
            }
            if (preg_match('/\bid="([^"]+)"/', $tag, $idMatch) && in_array($idMatch[1], $labelFors, true)) {
                continue;
            }
            $wrapped = false;
            foreach ($wrappedRanges as [$start, $end]) {
                if ($offset >= $start && $offset <= $end) {
                    $wrapped = true;
                    break;
                }
            }
            $this->assertTrue($wrapped, "[$page] unlabelled control: $tag");
        }

        // Every button and link has an accessible name (text, aria-label,
        // or an image with alt text inside it).
        preg_match_all('/<button\b([^>]*)>(.*?)<\/button>/s', $html, $buttons);
        foreach ($buttons[0] as $index => $button) {
            $text = trim(strip_tags($buttons[2][$index]));
            $attrs = $buttons[1][$index];
            $this->assertTrue($text !== '' || str_contains($attrs, 'aria-label'), "[$page] button without a name: $button");
        }
        preg_match_all('/<a\b([^>]*)>(.*?)<\/a>/s', $html, $links);
        foreach ($links[0] as $index => $link) {
            $text = trim(strip_tags($links[2][$index]));
            $attrs = $links[1][$index];
            $hasImgAlt = (bool) preg_match('/<img\b[^>]*alt="[^"]+"/', $links[2][$index]);
            $this->assertTrue($text !== '' || str_contains($attrs, 'aria-label') || $hasImgAlt, "[$page] link without a name: $link");
        }
    }
}
