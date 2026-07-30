<?php

namespace Goldnead\StatamicToc\Extractors;

use Goldnead\StatamicToc\Heading;

class HtmlExtractor implements Extractor
{
    public function extract(mixed $content): array
    {
        if (! is_string($content) || trim($content) === '') {
            return [];
        }

        $doc = $this->load($content);

        if (! $doc) {
            return [];
        }

        $headings = [];

        foreach ((new \DOMXPath($doc))->query('//h1 | //h2 | //h3 | //h4 | //h5 | //h6') as $tag) {
            $id = $tag instanceof \DOMElement && $tag->hasAttribute('id')
                ? trim($tag->getAttribute('id'))
                : null;

            $headings[] = new Heading(
                title: $this->normalize($tag->textContent),
                level: (int) ltrim($tag->nodeName, 'hH'),
                explicitId: $id === '' ? null : $id,
            );
        }

        return $headings;
    }

    private function load(string $html): ?\DOMDocument
    {
        $doc = new \DOMDocument;

        $previous = libxml_use_internal_errors(true);

        // mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8') was the old way
        // of getting UTF-8 past libxml's Latin-1 assumption. It is deprecated
        // since PHP 8.2; encoding non-ASCII as numeric entities is the
        // documented replacement and DOM decodes them back on read.
        $encoded = mb_encode_numericentity($html, [0x80, 0x10FFFF, 0, 0x1FFFFF], 'UTF-8');

        $loaded = $doc->loadHTML(
            '<!DOCTYPE html><html><body>'.$encoded.'</body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $loaded ? $doc : null;
    }

    private function normalize(string $text): string
    {
        // Collapse the whitespace a formatted heading spreads across its markup.
        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }
}
