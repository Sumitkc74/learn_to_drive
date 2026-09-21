<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OfficialContentPreview
{
    private const MAX_HTML_BYTES = 2 * 1024 * 1024;

    public function fetch(array $source): array
    {
        if (! $this->allowed($source['url'], $source['hosts'])) {
            throw new RuntimeException('Source must use HTTPS on an approved host.');
        }
        $response = Http::connectTimeout(10)->timeout(25)
            ->withHeaders(['User-Agent' => 'LearnToDrive-ContentPreview/1.0', 'Accept' => 'text/html'])
            ->withOptions(['allow_redirects' => false, 'stream' => true, 'verify' => config('official-content.ca_bundle') ?: true])->get($source['url']);
        if (! $response->successful()) {
            throw new RuntimeException('Source returned HTTP '.$response->status().'; redirects are not followed.');
        }
        if (! str_contains(strtolower($response->header('Content-Type')), 'text/html')) {
            throw new RuntimeException('Expected an HTML source page.');
        }
        $stream = $response->toPsrResponse()->getBody();
        $html = '';
        try {
            while (! $stream->eof()) {
                $html .= $stream->read(65536);
                if (strlen($html) > self::MAX_HTML_BYTES) {
                    throw new RuntimeException('Source HTML exceeds the 2 MB preview limit.');
                }
            }
        } finally {
            $stream->close();
        }

        return $this->extract($html, $source);
    }

    public function extract(string $html, array $source): array
    {
        if (strlen($html) > self::MAX_HTML_BYTES) {
            throw new RuntimeException('Source HTML exceeds the 2 MB preview limit.');
        }
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $xpath = new DOMXPath($document);
        $images = in_array($source['kind'], ['traffic-sign-sheet', 'vision-test-image'], true);
        $nodes = $xpath->query($images ? ($source['image_xpath'] ?? '//main//img[@src]') : '//a[@href] | //iframe[@src] | //embed[@src] | //object[@data]');
        $assets = [];
        $excluded = [];
        foreach ($nodes as $node) {
            $reference = $node->getAttribute($node->nodeName === 'a' ? 'href' : ($node->nodeName === 'object' ? 'data' : 'src'));
            try {
                $url = (string) UriResolver::resolve(new Uri($source['url']), new Uri(trim($reference)))->withFragment('');
            } catch (\InvalidArgumentException) {
                continue;
            }
            if (! $images && ! preg_match('/\.pdf$/i', (string) parse_url($url, PHP_URL_PATH))) {
                continue;
            }
            if (! $this->allowed($url, $source['hosts'])) {
                $excluded[$url] = 'Unapproved asset host or non-HTTPS URL';

                continue;
            }
            $label = trim(preg_replace('/\s+/u', ' ', $images ? $node->getAttribute('alt') : $node->textContent));
            $assets[$url] = [
                'kind' => $source['kind'],
                'title' => mb_substr($label ?: $source['name'], 0, 500),
                'source_name' => $source['name'],
                'source_url' => $source['url'],
                'asset_url' => $url,
                'fingerprint' => hash('sha256', $url),
                'review_required' => true,
                'asset_downloaded' => false,
            ];
        }

        return ['assets' => array_values($assets), 'excluded_assets' => $excluded];
    }

    private function allowed(string $url, array $hosts): bool
    {
        $parts = parse_url($url);

        return is_array($parts) && ($parts['scheme'] ?? '') === 'https'
            && in_array(strtolower($parts['host'] ?? ''), $hosts, true)
            && ! isset($parts['user']) && ! isset($parts['pass'])
            && (! isset($parts['port']) || $parts['port'] === 443);
    }
}
