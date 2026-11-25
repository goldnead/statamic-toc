<?php

namespace Goldnead\StatamicToc\Anchors;

use Illuminate\Support\Facades\Log;

/**
 * Writes the anchors into the rendered HTML.
 *
 * Deliberately a targeted replacement of the opening heading tags rather than a
 * round trip through DOMDocument, as the v2 sketch had it. This runs over
 * published content: re-serialising a whole article would quietly rewrite
 * markup nobody asked to change. Only `<h1>`..`<h6>` opening tags are touched,
 * everything else stays byte for byte.
 */
class IdInjector
{
    /**
     * @param  array<int, string|null>  $anchors  aligned by index with the
     *                                            headings in the document
     * @param  string|null  $attributes  extra attributes, where [id] is
     *                                   replaced with the anchor
     */
    public function inject(string $html, array $anchors, ?string $attributes = null): string
    {
        $index = 0;

        $result = preg_replace_callback(
            // Comments, script and style come first and are passed over: the
            // extractor (DOM) sees no headings in them, so counting one here
            // would hand every later heading its neighbour's anchor.
            '/<!--.*?-->|<(script|style)\b.*?<\/\1\s*>|<(h[1-6])((?:"[^"]*"|\'[^\']*\'|[^>"\'])*)>/is',
            function ($matches) use ($anchors, $attributes, &$index) {
                if (! isset($matches[3])) {
                    return $matches[0];
                }

                $tag = $matches[2];
                $attrs = $matches[3];
                $anchor = $anchors[$index] ?? null;
                $index++;

                // A heading that already carries an id keeps it. The old guard
                // required whitespace or '>' behind the closing quote, which
                // never follows the last attribute, so it appended a second id
                // and produced invalid HTML.
                if ($anchor === null || $this->hasId($attrs)) {
                    return $matches[0];
                }

                $extra = $attributes ? ' '.str_replace('[id]', $anchor, $attributes) : '';

                // An empty id="" is no id (see hasId()); drop it so the heading
                // does not end up with two.
                $attrs = preg_replace('/\s+(?<![\w-])id\s*=\s*(?:""|\'\')/i', '', $attrs) ?? $attrs;

                return sprintf('<%s%s id="%s"%s>', $tag, rtrim($attrs), $anchor, $extra);
            },
            $html
        );

        if ($result === null) {
            return $this->injectionFailed($html);
        }

        // Anchors are aligned by index with the headings. A different count
        // means the extractor and this scan disagree about the markup, and
        // every heading after the first difference carries someone else's id.
        if ($index !== count($anchors)) {
            Log::warning('statamic-toc: heading count differs from anchor count', [
                'headings' => $index,
                'anchors' => count($anchors),
            ]);
        }

        return $result;
    }

    /**
     * A PCRE error (backtrack limit, bad UTF-8) makes preg_replace_callback
     * return null. Log it instead of failing silently, and serve the page as
     * rendered: without anchors, but intact.
     */
    private function injectionFailed(string $html): string
    {
        Log::warning('statamic-toc: id injection failed', ['error' => preg_last_error_msg()]);

        return $html;
    }

    private function hasId(string $attributes): bool
    {
        // Only a real id attribute with a non-empty value counts, the same rule
        // HtmlExtractor applies to explicit ids. `data-id`, `hx-id` and `id=""`
        // are not ids.
        return preg_match('/(?<![\w-])id\s*=\s*("[^"]+"|\'[^\']+\'|[^\s>"\']+)/i', $attributes) === 1;
    }
}
