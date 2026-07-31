<?php

namespace Goldnead\StatamicToc\Anchors;

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

        return preg_replace_callback(
            '/<(h[1-6])((?:"[^"]*"|\'[^\']*\'|[^>"\'])*)>/i',
            function ($matches) use ($anchors, $attributes, &$index) {
                $tag = $matches[1];
                $attrs = $matches[2];
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

                return sprintf('<%s%s id="%s"%s>', $tag, rtrim($attrs), $anchor, $extra);
            },
            $html
        ) ?? $html;
    }

    private function hasId(string $attributes): bool
    {
        return preg_match('/\bid\s*=\s*(["\']).*?\1/i', $attributes) === 1;
    }
}
