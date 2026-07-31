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
        // convertToHtml() is deprecated in league/commonmark 2, which is the
        // only major this package requires, so convert() is always there.
        return (string) (new CommonMarkConverter)->convert($markdown);
    }
}
