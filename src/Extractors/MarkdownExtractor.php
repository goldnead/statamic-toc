<?php

namespace Goldnead\StatamicToc\Extractors;

use League\CommonMark\CommonMarkConverter;

class MarkdownExtractor implements Extractor
{
    public function __construct(private readonly HtmlExtractor $html = new HtmlExtractor) {}

    public function extract(mixed $content): array
    {
        if (! is_string($content) || trim($content) === '') {
            return [];
        }

        return $this->html->extract($this->toHtml($content));
    }

    private function toHtml(string $markdown): string
    {
        $converter = new CommonMarkConverter;

        // convertToHtml() is deprecated in league/commonmark 2.x.
        return method_exists($converter, 'convert')
            ? (string) $converter->convert($markdown)
            : (string) $converter->convertToHtml($markdown);
    }
}
