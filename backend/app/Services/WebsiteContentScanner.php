<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use RuntimeException;

class WebsiteContentScanner
{
    public function __construct(private PublicWebsiteRequest $web) {}

    public function scan(string $url, string $kind): array
    {
        $response = $this->web->get($url);
        $stream = $response->toPsrResponse()->getBody();
        try {
            if (!$response->successful()) throw new RuntimeException('The page could not be scanned. Redirects are not followed; enter its final HTTPS address.');
            if (!str_contains(strtolower($response->header('Content-Type')), 'text/html')) throw new RuntimeException('Enter an HTML page containing PDF links or images.');
            $html = '';
            while (!$stream->eof()) {
                $html .= $stream->read(65536);
                if (strlen($html) > 2 * 1024 * 1024) throw new RuntimeException('Page exceeds the 2 MB scan limit.');
            }
        } finally { $stream->close(); }
        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try { $dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING); }
        finally { libxml_clear_errors(); libxml_use_internal_errors($previous); }
        $xpath = new DOMXPath($dom);
        $pdf = $kind === 'question-bank-document';
        $nodes = $xpath->query($pdf ? '//a[@href] | //iframe[@src] | //embed[@src] | //object[@data]' : '//img[@src or @data-src]');
        $assets = [];
        foreach ($nodes as $node) {
            $reference = $node->getAttribute($node->nodeName === 'a' ? 'href' : ($node->nodeName === 'object' ? 'data' : 'src'));
            if (!$pdf && $node->getAttribute('data-src')) $reference = $node->getAttribute('data-src');
            if (trim($reference) === '') continue;
            try {
                $asset = (string) UriResolver::resolve(new Uri($url), new Uri($reference))->withFragment('');
                $this->web->host($asset);
            } catch (\Throwable $e) { continue; }
            if ($pdf && !preg_match('/\.pdf$/i', (string) parse_url($asset, PHP_URL_PATH))) continue;
            if (!$pdf && preg_match('/\.(svg|gif|ico)$/i', (string) parse_url($asset, PHP_URL_PATH))) continue;
            $title = trim(preg_replace('/\s+/u', ' ', $pdf ? $node->textContent : $node->getAttribute('alt')));
            $assets[hash('sha256', $asset)] = ['url' => $asset, 'title' => mb_substr($title ?: rawurldecode(basename(parse_url($asset, PHP_URL_PATH))) ?: 'Untitled resource', 0, 500)];
            if (count($assets) >= 50) break;
        }
        return array_values($assets);
    }
}
