<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use DOMXPath;
use RuntimeException;

/**
 * Extract the readable notice body from a DoTM notice page.
 *
 * The previous extraction (`strip_tags` over the whole document) kept the
 * text content of <style> and <script> elements, so the first characters
 * sent to AI screening were raw CSS rules and navigation text while the
 * notice body — which sits near the end of the document, inside <main> —
 * was cut off by the character limit. This extractor reads only the main
 * content region, drops scripts, styles and in-page chrome, and returns
 * plain text with one paragraph per line.
 */
class NoticePageTextExtractor
{
    private const MAX_HTML_BYTES = 2 * 1024 * 1024;

    /** Containers tried in order; the first yielding real text wins. */
    private const CONTAINER_XPATHS = [
        '//main',
        '//article',
        '//*[@role="main"]',
        '//*[@id="content" or @id="main-content" or @id="primary"]',
        '//body',
    ];

    /** Chrome removed from the chosen container before text is read. */
    private const REMOVE_TAGS = ['script', 'style', 'noscript', 'svg', 'iframe', 'object', 'embed', 'form', 'nav', 'header', 'footer', 'aside', 'button', 'input', 'select', 'textarea', 'link', 'meta', 'head'];

    /** Class-name fragments marking in-page chrome (related links, share buttons, …). */
    private const REMOVE_CLASS_FRAGMENTS = ['related-news', 'share__widget', 'fontsize__toggler', 'gov-popup', 'breadcrumb', 'pagination', 'sidebar', 'advertisement'];

    private const BLOCK_TAGS = ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'li', 'tr', 'div', 'section', 'article', 'blockquote', 'table', 'br', 'hr', 'ul', 'ol'];

    public function extract(string $html): string
    {
        if (strlen($html) > self::MAX_HTML_BYTES) {
            throw new RuntimeException('Source HTML exceeds the 2 MB limit.');
        }
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $xpath = new DOMXPath($document);
        $best = '';
        foreach (self::CONTAINER_XPATHS as $query) {
            $nodes = $xpath->query($query);
            if ($nodes === false || $nodes->length === 0) continue;
            $container = $nodes->item(0);
            $this->stripChrome($container, $xpath);
            $text = $this->readText($container);
            if (mb_strlen($text) >= 100) return $text;
            if (mb_strlen($text) > mb_strlen($best)) $best = $text;
        }

        return $best;
    }

    private function stripChrome(DOMNode $container, DOMXPath $xpath): void
    {
        $tagQuery = './/'.implode(' | .//', self::REMOVE_TAGS);
        $classQuery = './/*['.implode(' or ', array_map(fn ($fragment) => "contains(@class, '{$fragment}')", self::REMOVE_CLASS_FRAGMENTS)).']';
        $hiddenQuery = './/*[@hidden or contains(@style, "display:none") or contains(@style, "visibility:hidden")]';
        $jumpLinkQuery = './/a[starts-with(@href, "#")]';
        $found = [];
        foreach ([$tagQuery, $classQuery, $hiddenQuery, $jumpLinkQuery] as $query) {
            $nodes = $xpath->query($query, $container);
            if ($nodes === false) continue;
            foreach ($nodes as $node) $found[] = $node;
        }
        foreach ($found as $node) {
            if ($node->parentNode !== null) $node->parentNode->removeChild($node);
        }
    }

    private function readText(DOMNode $node): string
    {
        $lines = [];
        $this->collectLines($node, $lines);
        $text = implode("\n", $lines);
        $text = (string) preg_replace('/[ \t\x{00A0}]+/u', ' ', $text);
        $text = (string) preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }

    private function collectLines(DOMNode $node, array &$lines): void
    {
        if ($node instanceof DOMText) {
            $text = trim((string) preg_replace('/\s+/u', ' ', $node->textContent));
            if ($text !== '') $lines[] = $text;

            return;
        }
        if ($node instanceof DOMElement) {
            foreach ($node->childNodes as $child) $this->collectLines($child, $lines);
            if (in_array(strtolower($node->tagName), self::BLOCK_TAGS, true)) $lines[] = '';
        }
    }
}
