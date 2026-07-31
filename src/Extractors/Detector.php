<?php

namespace Goldnead\StatamicToc\Extractors;

class Detector
{
    /**
     * An actual heading tag, not just any string that happens to contain "<h".
     */
    private const HTML_HEADING = '/<h[1-6]\b[^>]*>/i';

    /**
     * An ATX heading at the start of a line, or a setext underline. A stray "#"
     * in a sentence or a hex colour is not markdown.
     */
    private const MARKDOWN_HEADING = '/^[ ]{0,3}#{1,6}[ \t]|^[^\n]+\n[ ]{0,3}(=+|-+)[ \t]*$/m';

    public function for(mixed $content): Extractor
    {
        if ($this->isBard($content)) {
            return new BardExtractor;
        }

        if ($this->isMarkdown($content)) {
            return new MarkdownExtractor;
        }

        return new HtmlExtractor;
    }

    public function isBard(mixed $content): bool
    {
        return is_array($content);
    }

    public function isHtml(mixed $content): bool
    {
        return is_string($content) && preg_match(self::HTML_HEADING, $content) === 1;
    }

    public function isMarkdown(mixed $content): bool
    {
        if (! is_string($content) || $this->isHtml($content)) {
            return false;
        }

        return preg_match(self::MARKDOWN_HEADING, $content) === 1;
    }
}
