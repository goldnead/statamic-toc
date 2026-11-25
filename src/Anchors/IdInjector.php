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
 *
 * The anchors are aligned by index with the headings the DOM extractor finds,
 * so this scan has to count exactly the same headings. libxml sees none inside
 * comments, `<script>` and `<style>` (an unclosed one swallows the rest of the
 * document), and a `<` inside an attribute value starts nothing. The pattern
 * therefore walks the markup tag by tag instead of searching for `<h2`.
 */
class IdInjector
{
    /**
     * One pass, tried in this order at every `<`: comment, script/style with
     * their content, a heading opening tag, any other tag (consumed whole, so
     * quotes in its attributes cannot start a comment or a script).
     */
    private const SCAN = '~<!--.*?(?:-->|\z)'
        .'|<(script|style)(?=[\s>/])(?:"[^"]*"|\'[^\']*\'|[^>"\'])*>.*?(?:</\1\s*>|\z)'
        .'|<(h[1-6])(?=[\s>/])((?:"[^"]*"|\'[^\']*\'|[^>"\'])*)>'
        .'|<[a-z][^\s>/]*(?:"[^"]*"|\'[^\']*\'|[^>"\'])*>~is';

    /** One attribute: name, then an optionally quoted value. */
    private const ATTRIBUTE = '~([^\s"\'>/=]+)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'>]+))?)?~';

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
            self::SCAN,
            function ($matches) use ($anchors, $attributes, &$index) {
                // Comment, script/style, or a tag that is not a heading.
                if (! isset($matches[3])) {
                    return $matches[0];
                }

                $tag = $matches[2];
                $anchor = $anchors[$index] ?? null;
                $index++;

                [$hasId, $attrs] = $this->readId($matches[3]);

                // A heading that already carries an id keeps it. The old guard
                // required whitespace or '>' behind the closing quote, which
                // never follows the last attribute, so it appended a second id
                // and produced invalid HTML.
                if ($anchor === null || $hasId) {
                    return $matches[0];
                }

                $extra = $attributes ? ' '.str_replace('[id]', $anchor, $attributes) : '';

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

    /**
     * Does this attribute string carry a real id, and what is left of it once
     * empty ones are gone?
     *
     * Attributes are read as attributes, not searched as text: `data-id`,
     * `hx-id` and a `title="my id=foo"` are not ids. The first `id` decides, as
     * in the DOM. A blank one (`id=""`, `id=" "`, a bare `id`) is no id: the
     * extractor generates an anchor for it, so every `id` is dropped here and
     * the generated one written instead of leaving two.
     *
     * @return array{0: bool, 1: string}
     */
    private function readId(string $attributes): array
    {
        if (preg_match_all(self::ATTRIBUTE, $attributes, $found, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) === false) {
            return [false, $attributes];
        }

        $ids = array_values(array_filter($found, fn ($m) => strtolower($m[1][0]) === 'id'));

        if ($ids === []) {
            return [false, $attributes];
        }

        $first = $ids[0];
        $value = trim(($first[2][0] ?? '') !== '' ? $first[2][0] : (($first[3][0] ?? '') !== '' ? $first[3][0] : ($first[4][0] ?? '')));

        if ($value !== '') {
            return [true, $attributes];
        }

        // Cut from the back so the offsets stay valid.
        foreach (array_reverse($ids) as $id) {
            $attributes = substr_replace($attributes, '', $id[0][1], strlen($id[0][0]));
        }

        return [false, $attributes];
    }
}
