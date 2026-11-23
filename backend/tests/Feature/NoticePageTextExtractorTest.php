<?php

namespace Tests\Feature;

use App\Services\NoticePageTextExtractor;
use Tests\TestCase;

class NoticePageTextExtractorTest extends TestCase
{
    private function dotmPage(): string
    {
        return '<!DOCTYPE html><html lang="ne"><head><title>मिति २०८३।०३।०८ गते वितरणका लागि तयार भएका अत्यावश्यक सवारी चालक अनुमतिपत्रहरुको विवरण । | Department of Transportation managmenet</title><style>:root { --riri-color: #273c92; } .post__meta .author img { width: 40px !important; } .riri__audio { margin-top: 2rem; } @media (max-width: 400px) { .riri__audio { display: none; } }</style><script>var menu = document.querySelector("nav");</script></head><body>'
            .'<a href="#main">मुख्य सामग्रीमा जानुहोस्</a>'
            .'<header><nav><ul><li>गृहपृष्ठ</li><li>सूचनाहरू</li><li>सम्पर्क</li></ul></nav></header>'
            .'<main>'
            .'<div class="detail__page-inner-content"><h1>मिति २०८३।०३।०८ गते वितरणका लागि तयार भएका अत्यावश्यक सवारी चालक अनुमतिपत्रहरुको विवरण ।</h1></div>'
            .'<div class="meta date"><p>८ असार, २०८३</p></div>'
            .'<style>.view__more { display: none; }</style>'
            .'<div class="details__desc"><p>कृपया सवारी चालक अनुमतिपत्र लिन आउँदा सक्कल राजश्व तिरेको सबै रसिद तथा पुरानो सवारी चालक अनुमतिपत्र समेत लिई आउनुहोला । धन्यवाद!</p></div>'
            .'<div style="display:none">Hidden draft note</div>'
            .'<div class="fontsize__toggler"><button>A</button></div>'
            .'<div class="share__widget">Share this notice</div>'
            .'<div class="detail__related-news"><h3>सम्बन्धित सूचना</h3><a href="/content/1">पुर्व-प्रकाशित सूचना स्थगन गरिएको सम्बन्धमा।</a></div>'
            .'</main>'
            .'<footer>© Department of Transport Management</footer>'
            .'</body></html>';
    }

    public function test_extracts_notice_body_without_css_or_chrome(): void
    {
        $text = app(NoticePageTextExtractor::class)->extract($this->dotmPage());

        $this->assertStringContainsString('कृपया सवारी चालक अनुमतिपत्र लिन आउँदा', $text);
        $this->assertStringContainsString('८ असार, २०८३', $text);
        $this->assertStringNotContainsString('.post__meta', $text);
        $this->assertStringNotContainsString('--riri-color', $text);
        $this->assertStringNotContainsString('@media', $text);
        $this->assertStringNotContainsString('display: none', $text);
        $this->assertStringNotContainsString('var menu', $text);
        $this->assertStringNotContainsString('गृहपृष्ठ', $text);
        $this->assertStringNotContainsString('सम्बन्धित सूचना', $text);
        $this->assertStringNotContainsString('पुर्व-प्रकाशित सूचना', $text);
        $this->assertStringNotContainsString('Share this notice', $text);
        $this->assertStringNotContainsString('मुख्य सामग्रीमा जानुहोस्', $text);
        $this->assertStringNotContainsString('Hidden draft note', $text);
        $this->assertStringNotContainsString('© Department of Transport Management', $text);
    }

    public function test_falls_back_to_body_without_main(): void
    {
        $html = str_replace(['<main>', '</main>'], ['<div>', '</div>'], $this->dotmPage());
        $text = app(NoticePageTextExtractor::class)->extract($html);

        $this->assertStringContainsString('कृपया सवारी चालक अनुमतिपत्र लिन आउँदा', $text);
        $this->assertStringNotContainsString('.post__meta', $text);
        $this->assertStringNotContainsString('गृहपृष्ठ', $text);
    }

    public function test_empty_notice_page_returns_empty_text(): void
    {
        $this->assertSame('', app(NoticePageTextExtractor::class)->extract('<html><body><main></main></body></html>'));
    }
}
