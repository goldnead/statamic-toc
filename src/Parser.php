<?php

/**
 * This Class handles all the parsing logic for generating TOCs from
 * Bard fields.
 */

namespace Goldnead\StatamicToc;

use Goldnead\StatamicToc\Anchors\IdInjector;
use Goldnead\StatamicToc\Extractors\Detector;

class Parser
{
    private $content;

    private $headings = [];

    private Options $options;

    /**
     * Constructor.
     *
     * @param  string  $content
     */
    public function __construct($content = null)
    {
        $this->options = Options::default();

        if ($content) {
            $this->setContent($content);
        }

        return $this;
    }

    /**
     * Replaces the whole option set at once. The fluent setters below stay for
     * anyone driving the parser directly.
     */
    public function options(Options $options): self
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Set the content to be parsed
     *
     * @param  string  $content
     * @return void
     */
    public function setContent($content): object
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Parser::make($content).
     * Creates a parser object and stores all information in local variables
     */
    public function make($content)
    {
        $this->headings = [];
        $this->content = $content;

        return $this;
    }

    /**
     * Determines if the given content is a string of HTML.
     */
    public function isHTML(): bool
    {
        return (new Detector)->isHtml($this->content);
    }

    /**
     * Determines if the given content is a bard array.
     */
    public function isBard(): bool
    {
        return (new Detector)->isBard($this->content);
    }

    /**
     * Determines if the given content is a string of markdown.
     */
    public function isMarkdown(): bool
    {
        return (new Detector)->isMarkdown($this->content);
    }

    /**
     * How many levels the list spans, counted from the starting level.
     *
     * @param  int  $depth
     * @return $this
     */
    public function depth($depth)
    {
        $this->options = $this->options->withDepth((int) $depth);

        return $this;
    }

    /**
     * The deepest level the list shows, as an absolute level. Says the same
     * thing as depth() without the arithmetic.
     *
     * @param  string|int  $level
     * @return $this
     */
    public function to($level)
    {
        $this->options = $this->options->withTo($level);

        return $this;
    }

    /**
     * Sets the starting point from which the list should be displayed.
     *
     * @param  string|int  $start
     * @return $this
     */
    public function from($start)
    {
        $this->options = $this->options->withFrom($start);

        return $this;
    }

    /**
     * Set the exclusion pattern
     * 
     * @param string|null $exclude
     * @return $this
     */
    public function exclude($exclude)
    {
        $this->options = $this->options->withExclude($exclude);

        return $this;
    }

    /**
     * Sets a marker so the list won't be processed recursively.
     *
     * @return $this
     */
    public function flatten()
    {
        $this->options = $this->options->withFlat();

        return $this;
    }

    /**
     * Sets the flattening only if the given parameter is true.
     *
     * @param  bool  $bool
     * @return void
     */
    public function flattenIf($bool)
    {
        return $bool ? $this->flatten() : $this;
    }

    /**
     * Generates the output array.
     */
    public function build(): array
    {
        return $this->supplementExtraOutput(
            $this->generate()
        );
    }

    private function generate(): array
    {
        $extracted = (new Detector)->for($this->content)->extract($this->content);

        // Anchors are decided once per document, for every heading, and shared
        // with the modifier. The level range only decides what the list shows,
        // never what a heading is called.
        return $this->assemble($extracted, $this->registry()->anchorsFor($extracted));
    }

    private function registry(): Registry
    {
        return app(Registry::class);
    }

    public function supplementExtraOutput(array $toc): array
    {
        $extra = [];

        $count = count($this->headings);

        if (count($toc) > 0) {
            $toc[0]['total_results'] = $count;
            if ($count < 1) {
                $toc[0]['no_results'] = true;
            }

            return $toc;
        }

        return [
            'total_results' => $count,
            'no_results' => $count < 1,
        ];
    }

    /**
     * Turns the extracted headings into the array the tag renders: filtered to
     * the requested level range, slugged, then linked up into a tree.
     */
    private function assemble(array $extracted, array $anchors): array
    {
        foreach ($extracted as $index => $heading) {
            if (! $this->options->covers($heading->level)) {
                continue;
            }

            if ($heading->isEmpty() || ! $this->shouldIncludeHeading($heading->title)) {
                continue;
            }

            if (($anchors[$index] ?? null) === null) {
                continue;
            }

            $this->headings[] = [
                'toc_title' => $heading->title,
                'level' => $heading->level,
                'toc_id' => $anchors[$index],
                'id' => count($this->headings) + 1,
            ];
        }

        if (empty($this->headings)) {
            return [];
        }

        // get root & max level info
        $rootLevel = collect($this->headings)->min('level');
        $maxLevel = collect($this->headings)->max('level');

        // get additional info for each heading and specify parent & children relationships
        if (! empty($this->headings)) {
            collect($this->headings)->each(function ($heading, $key) use ($rootLevel, $maxLevel) {
                // Standardmäßig parent auf null setzen
                $this->headings[$key]['parent'] = null;
                $this->headings[$key]['has_children'] = false;

                if ($heading['level'] == $rootLevel) {
                    $this->headings[$key]['is_root'] = true;
                }

                // Prüfen, ob die nächste Überschrift eine tiefere Ebene hat
                if (isset($this->headings[$key + 1]) && $this->headings[$key + 1]['level'] > $heading['level']) {
                    $this->headings[$key]['has_children'] = true;
                }

                if ($heading['level'] == $maxLevel) {
                    $this->headings[$key]['is_deepest_children'] = true;
                }

                if ($key > 0) {
                    $prevHeading = $this->headings[$key - 1];

                    if ($heading['level'] > $prevHeading['level']) {
                        // Die aktuelle Überschrift ist eine Unterüberschrift der vorherigen
                        $this->headings[$key]['parent'] = $prevHeading['id'];
                    } elseif ($heading['level'] == $prevHeading['level']) {
                        // Die aktuelle Überschrift ist auf derselben Ebene wie die vorherige
                        $this->headings[$key]['parent'] = $prevHeading['parent'];
                    } else {
                        // Die aktuelle Überschrift ist eine übergeordnete Ebene
                        $i = $key - 1;
                        while ($i >= 0) {
                            if ($this->headings[$i]['level'] < $heading['level']) {
                                $this->headings[$key]['parent'] = $this->headings[$i]['id'];
                                break;
                            }
                            $i--;
                        }
                        // Wenn kein Elternteil gefunden wurde, bleibt parent null
                    }
                } else {
                    // Erste Überschrift, parent bleibt null
                }
            });
        }

        // return flat array if flag is true, nest it if not
        return $this->options->flat ? $this->headings : $this->nestHeadings();
    }

    /**
     * Checks if a heading should be included based on the exclusion pattern.
     */
    private function shouldIncludeHeading(string $title): bool
    {
        if (! $this->options->exclude) {
            return true;
        }

        // Treat as regex only when it looks like a delimited pattern (e.g. /foo/i).
        // This avoids calling preg_match on plain strings, which would emit warnings.
        if (preg_match('/^([^\w\s\\\\])[^\1]*\1[gimsuy]*$/', $this->options->exclude)) {
            try {
                return ! preg_match($this->options->exclude, $title);
            } catch (\Throwable $e) {
                // Invalid regex — fall through to string match
            }
        }

        // Comma-separated string match; skip empty tokens to avoid matching everything
        foreach (explode(',', $this->options->exclude) as $exc) {
            $exc = trim($exc);
            if ($exc !== '' && stripos($title, $exc) !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Recursive function to nest a list of headings using the keys 'id' & 'parent'.
     *
     * @param  int  $parent
     * @return null|array
     */
    private function nestHeadings($parent = 0)
    {
        $headings = [];
        foreach ($this->headings as $key => $heading) {
            if (! array_key_exists('parent', $heading) || $heading['parent'] != $parent) {
                continue;
            }

            $headings[] = $heading;

            if ($children = $this->nestHeadings($heading['id'])) {
                $length = count($headings);
                $headings[$length - 1]['children'] = $children;
                $headings[$length - 1]['total_children'] = count($children);
            }
        }

        return empty($headings) ? [] : $headings;
    }

    /**
     * Injects the anchors into a rendered HTML string.
     *
     * Kept as public API for anyone calling the parser directly. It no longer
     * computes ids of its own: it looks up the anchors this document already
     * got, so tag and modifier can never disagree.
     */
    public function injectIds($value, $params = null): string
    {
        if (! is_string($value)) {
            return (string) $value;
        }

        $extracted = (new Detector)->for($value)->extract($value);

        $attributes = is_array($params) ? implode(' ', $params) : $params;

        return (new IdInjector)->inject(
            $value,
            app(Registry::class)->anchorsFor($extracted),
            $attributes ?: null
        );
    }
}
