<?php

namespace App\Services;

use App\Models\GovernmentNoticeImport;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DotmNoticeImporter
{
    private const SOURCE_NAME = 'Department of Transport Management, Nepal';
    private const ALLOWED_HOSTS = ['dotm.gov.np', 'www.dotm.gov.np'];
    private const INCLUDE = ['चालक', 'अनुमतिपत्र', 'अनुमति पत्र', 'लाइसेन्स', 'लाईसेन्स', 'लिखित परीक्षा', 'प्रयोगात्मक', 'trial', 'driving license'];
    private const EXCLUDE = ['नामावली', 'नतिजा', 'परीक्षाफल'];

    public function fetch(): array
    {
        $sourceUrl = config('services.dotm.notices_url', 'https://dotm.gov.np/');
        $host = strtolower((string) parse_url($sourceUrl, PHP_URL_HOST));
        if (parse_url($sourceUrl, PHP_URL_SCHEME) !== 'https' || !in_array($host, self::ALLOWED_HOSTS, true)) {
            throw new RuntimeException('The configured DoTM source is not on the official allowlist.');
        }

        $response = Http::timeout(20)->retry(2, 1000)->withHeaders([
            'User-Agent' => 'LearnToDrive-OfficialNoticeChecker/1.0 (one-page public notice check)',
            'Accept' => 'text/html,application/xhtml+xml',
        ])->get($sourceUrl)->throw();

        $links = $this->extractLinks($response->body(), $sourceUrl);
        $created = 0;
        foreach ($links as $link) {
            $hash = hash('sha256', $link['url'].'|'.$link['title']);
            $existing = GovernmentNoticeImport::where('source_url', $link['url'])->orWhere('content_hash', $hash)->exists();
            if ($existing) continue;
            GovernmentNoticeImport::create(['source_name' => self::SOURCE_NAME, 'source_url' => $link['url'], 'source_domain' => parse_url($link['url'], PHP_URL_HOST), 'title' => $link['title'], 'content_hash' => $hash, 'status' => 'Pending', 'fetched_at' => now()]);
            $created++;
        }

        return ['found' => count($links), 'created' => $created, 'duplicates' => count($links) - $created];
    }

    public function extractLinks(string $html, string $baseUrl): array
    {
        $document = new DOMDocument();
        $previousErrorMode = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrorMode);
        $xpath = new DOMXPath($document);
        $results = [];

        foreach ($xpath->query('//a[@href]') as $anchor) {
            $title = trim(preg_replace('/\s+/u', ' ', $anchor->textContent));
            if (!$this->isRelevant($title)) continue;
            $url = $this->absoluteUrl($anchor->getAttribute('href'), $baseUrl);
            if (!$url || !$this->isAllowedUrl($url)) continue;
            $results[$url] = ['title' => mb_substr($title, 0, 500), 'url' => $url];
        }

        return array_values($results);
    }

    private function isRelevant(string $title): bool
    {
        $lower = mb_strtolower($title);
        if ($title === '' || collect(self::EXCLUDE)->contains(fn ($word) => str_contains($lower, mb_strtolower($word)))) return false;
        return collect(self::INCLUDE)->contains(fn ($word) => str_contains($lower, mb_strtolower($word)));
    }

    private function absoluteUrl(string $href, string $baseUrl): ?string
    {
        $href = trim($href);
        if ($href === '' || str_starts_with($href, '#') || str_starts_with(strtolower($href), 'javascript:')) return null;
        if (str_starts_with($href, '//')) return 'https:'.$href;
        if (filter_var($href, FILTER_VALIDATE_URL)) return $href;
        $parts = parse_url($baseUrl);
        if (!isset($parts['scheme'], $parts['host'])) return null;
        return $parts['scheme'].'://'.$parts['host'].'/'.ltrim($href, '/');
    }

    private function isAllowedUrl(string $url): bool
    {
        return parse_url($url, PHP_URL_SCHEME) === 'https' && in_array(strtolower((string) parse_url($url, PHP_URL_HOST)), self::ALLOWED_HOSTS, true);
    }
}
