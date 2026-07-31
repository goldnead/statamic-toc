<?php

/**
 * This Class handles all the parsing logic for generating TOCs from
 * Bard fields.
 */

namespace Goldnead\StatamicToc;

use Goldnead\StatamicToc\Extractors\Detector;
use Illuminate\Support\Str;

class Parser
{
    private $content;

    private $slugs = [];

    private $maxLevel = 3;

    private $minLevel = 1;

    private $headings = [];
    
    private $exclude;

    private $isFlat = false;

    /**
     * Constructor.
     *
     * @param  string  $content
     */
    public function __construct($content = null)
    {
        $this->slugs = collect($this->slugs);

        if ($content) {
            $this->setContent($content);
        }

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
        $this->slugs = collect();
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
     * Sets the given List-Depth
     *
     * @param  int  $depth
     * @return $this
     */
    public function depth($depth)
    {
        $this->maxLevel = $depth + $this->minLevel - 1;

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
        // parse string if it has the syntax "h(int)" (eg. h2)
        if (is_string($start)) {
            $start = intval(ltrim($start, 'h'));
        }
        // reset starting value if it is below or above the supported ones
        if ($start < 1) {
            $start = 1;
        } elseif ($start > 6) {
            $start = 6;
        }

        $currentDepth = $this->maxLevel - $this->minLevel + 1;
        $this->minLevel = $start;
        // our depth is relative to the minLevel. So we need to update it if
        // the minLevel changes
        $this->depth($currentDepth);

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
        $this->exclude = $exclude;

        return $this;
    }

    /**
     * Sets a marker so the list won't be processed recursively.
     *
     * @return $this
     */
    public function flatten()
    {
        $this->isFlat = true;

        return $this;
    }

    /**
     * Stops the recursion at the given level.
     * TODO/FEATURE/WHY?
     *
     * @param [type] $level
     * @return void
     */
    public function flattenFrom($level) {}

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
        return $this->assemble(
            (new Detector)->for($this->content)->extract($this->content)
        );
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
    private function assemble(array $extracted): array
    {
        foreach ($extracted as $heading) {
            if ($heading->level < $this->minLevel || $heading->level > $this->maxLevel) {
                continue;
            }

            if ($heading->isEmpty() || ! $this->shouldIncludeHeading($heading->title)) {
                continue;
            }

            $this->headings[] = [
                'toc_title' => $heading->title,
                'level' => $heading->level,
                'toc_id' => $this->generateId($heading->title, true),
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
        return $this->isFlat ? $this->headings : $this->nestHeadings();
    }

    /**
     * Checks if a heading should be included based on the exclusion pattern.
     */
    private function shouldIncludeHeading(string $title): bool
    {
        if (! $this->exclude) {
            return true;
        }

        // Treat as regex only when it looks like a delimited pattern (e.g. /foo/i).
        // This avoids calling preg_match on plain strings, which would emit warnings.
        if (preg_match('/^([^\w\s\\\\])[^\1]*\1[gimsuy]*$/', $this->exclude)) {
            try {
                return ! preg_match($this->exclude, $title);
            } catch (\Throwable $e) {
                // Invalid regex — fall through to string match
            }
        }

        // Comma-separated string match; skip empty tokens to avoid matching everything
        foreach (explode(',', $this->exclude) as $exc) {
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
     * Injects header HTML-Elements with thparamseir corersponding ids.
     */
    public function injectIds($value, $params = null): string
    {
        // Do all the regex magic here
        $injected = preg_replace_callback(
            '#<(h[1-'.$this->maxLevel.'])(.*?)>(.*?)</\1>#si',
            // callback
            function ($matches) use ($params) {
                // the html tag
                $tag = $matches[1];
                // decode html entities to support special characters in headings/slug
                $title = html_entity_decode(strip_tags($matches[3]));
                $hasId = preg_match('/id=(["\'])(.*?)\1[\s>]/si', $matches[2], $matchedIds);
                $id = $hasId ? $matchedIds[2] : $this->generateId($title, false);

                if ($hasId) {
                    return $matches[0];
                }
                if ($params && is_array($params)) {
                    $params = implode(' ', $params);
                } else {
                    $params = '';
                }

                $params = str_replace('[id]', $id, $params);

                // rebuild the tag with Id.
                return sprintf('<%s%s id="%s" %s>%s</%s>', $tag, $matches[2], $id, $params, $matches[3], $tag);
            },
            $value
        );

        return $injected;
    }

    /**
     * Slugifies a given title
     *
     * @return string [description]
     */
    private function generateId($title, $list = false): string
    {
        $id = $raw = Str::slug($title);
        $count = 2;
        $suffix = $list ? 'list' : 'text';

        // make sure we don't have any duplicate ids via adding a counter at
        // the end of an id if it already exists.
        while ($this->slugs->contains($id.'-'.$suffix)) {
            $id = $raw.'-'.$count;
            $count++;
        }

        $this->slugs->push($id.'-'.$suffix);

        return $id;
    }
}
