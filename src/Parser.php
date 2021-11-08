<?php

/**
 * This Class handles all the parsing logic for generating TOCs from
 * Bard fields.
 */

namespace Goldnead\StatamicToc;

use Illuminate\Support\Str;

class Parser
{
    private $content;

    private $slugs = [];

    private $maxLevel = 3;

    private $minLevel = 1;

    private $headings = [];

    private $isFlat = false;

    public function __construct()
    {
        $this->slugs = collect($this->slugs);
    }

    /**
     * Parser::make($content).
     * Creates a parser object and stores all information in local variables
     */
    public function make($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Determines if the given content is a bard structure or a HTML-String.
     *
     * @return boolean
     */
    public function isHTML(): bool
    {
        return is_string($this->content);
    }

    /**
     * Sets the given List-Depth
     *
     * @param int $depth
     * @return $this
     */
    public function depth($depth)
    {
        $this->maxLevel = $depth + $this->minLevel - 1;
        return $this;
    }

    /**
     * Sets the strating point from which the list should be displayed.
     *
     * @param string|int $start
     * @return $this
     */
    public function from($start)
    {
        // parse string if it has the syntax "h(int)" (eg. h2)
        if (is_string($start)) {
            $start = intval(ltrim($start, "h"));
        }
        // reset starting value if it is below or above the supported ones
        if ($start < 1) {
            $start = 1;
        } elseif ($start > 6) {
            $start = 6;
        }

        $this->minLevel = $start;
        // our depth is relative to the minLevel. So we need to update is if
        // the minLevel changes
        $this->depth($this->maxLevel);
        return $this;
    }

    /**
     * Sets a marker so the list won't be proicessed recursively.
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
     * TODO
     *
     * @param [type] $level
     * @return void
     */
    public function flattenFrom($level)
    {
    }

    /**
     * Sets the flattening only if the given parameter is true.
     *
     * @param boolean $bool
     * @return void
     */
    public function flattenIf($bool)
    {
        if ($bool) {
            $this->flatten();
        }
        return $this;
    }

    /**
     * Generates the output array.
     *
     * @return array
     */
    public function build(): array
    {
        return $this->supplementExtraOutput(
            $this->generate()
        );
    }

    private function generate(): array
    {
        if ($this->isHTML()) {
            return $this->generateFromHtml();
        }
        return  $this->generateFromStructure();
    }

    public function supplementExtraOutput(array $toc): array
    {
        $extra = [];

        $count = count($this->headings);
        $extra['total_results'] = $count;

        if ($count < 1) {
            $extra['no_results'] = true;
        }

        if (count($toc) > 0) {
            return array_merge($toc[0], $extra);
        }

        return $extra;
    }

    /**
     * Parses a HTML-input and returns a fake bard-structure to be processed
     * by $this->generateFromStructure().
     *
     * @return array
     */
    private function generateFromHtml(): array
    {
        // tidy up & load our DOM.
        $tidy_config = array(
            "indent"               => true,
            "output-xml"           => true,
            "output-xhtml"         => false,
            "drop-empty-paras"     => false,
            "hide-comments"        => true,
            "numeric-entities"     => true,
            "doctype"              => "omit",
            "char-encoding"        => "utf8",
            "repeated-attributes"  => "keep-last"
        );
        $html = tidy_repair_string($this->content, $tidy_config);
        $doc = new \DOMDocument();
        $doc->loadHTML($html);

        // create an xPath Query to get all headings in order.
        $xpath = new \DOMXpath($doc);
        $htags = $xpath->query('//h1 | //h2 | //h3 | //h4 | //h5 | //h6');

        // empty container collection or our headings
        $headings = collect([]);
        // iterage over each tag and set an object similar to the one used by bard
        // which can be parsed by $this->generateFromStructure();
        foreach ($htags as $tag) {
            $headings->push([
                "type" => "heading",
                "attrs" => [
                    "level" => (int) ltrim($tag->nodeName, 'h')
                ],
                "content" => [
                    [
                        "type" => "text",
                        // force utf-8 decoding
                        "text" => utf8_decode($tag->nodeValue),
                    ],
                ],
            ]);
        }

        return $this->generateFromStructure($headings->toArray());
    }

    /**
     * Generates an array of elements necessairy for the TOC-Tag to
     * function.
     *
     * @return Array
     */
    private function generateFromStructure($structure = null): array
    {
        // create a collection with the content array
        $raw = !$structure ? collect($this->content) : collect($structure);

        // filter out all the headings
        $headings = $raw->filter(function ($item) {
            return is_array($item) && $item["type"] === "heading" && $item["attrs"]["level"] >= $this->minLevel && $item["attrs"]["level"] <= $this->maxLevel;
        });

        if ($headings->count() > 0) {
            // iterate through each heading and push its information into
            // an array.
            $headings->each(function ($heading, $key) use (&$tocArray) {
                // Check, if the heading isn't empty or if the content type is really text
                if (!isset($heading["content"]) || $heading["content"][0]["type"] !== "text") {
                    return;
                }

                $title = $heading["content"][0]["text"];
                $this->headings[] = [
                    "toc_title" => $title,
                    "level" => $heading["attrs"]["level"],
                    "toc_id" => $this->generateId($title, true),
                ];
                $this->headings[sizeof($this->headings) - 1]['id'] = sizeof($this->headings);
            });
        }

        // get root & max level info
        $rootLevel = collect($this->headings)->min("level");
        $maxLevel = collect($this->headings)->max("level");

        // get additional info for each heading and specify parent & children relationships
        if (!empty($this->headings)) {
            collect($this->headings)->each(function ($heading, $key) use ($rootLevel, $maxLevel) {
                if ($heading['level'] == $rootLevel) {
                    $this->headings[$key]['is_root'] = true;
                    // we need a default value for the nesting function to work properly
                    $this->headings[$key]["parent"] = null;
                }

                // if the next item in line level is lower, the current item has children
                if (isset($this->headings[$key + 1]) && $this->headings[$key + 1]['level'] > $heading['level']) {
                    $this->headings[$key]['has_children'] = true;
                }

                if ($heading['level'] == $maxLevel) {
                    $this->headings[$key]['is_deepest_children'] = true;
                }

                // get parent ids for all items that aren't at root level
                if ($heading['level'] > $rootLevel) {
                    if ($this->headings[$key - 1]['level'] < $heading['level']) {
                        $this->headings[$key]['parent'] = $this->headings[$key - 1]['id'];
                    }
                    if ($this->headings[$key - 1]['level'] === $heading['level']) {
                        $this->headings[$key]['parent'] = $this->headings[$key - 1]['parent'];
                    }
                    if ($this->headings[$key - 1]['level'] > $heading['level']) {
                        $i = $key;
                        while ($i--) {
                            if ($this->headings[$i]['level'] < $heading['level']) {
                                $this->headings[$key]['parent'] = $this->headings[$i]['id'];
                                break;
                            }
                        }
                    }
                }
            });
        }
        // return flat array if flag is true, nest it if not
        return $this->isFlat ? $this->headings : $this->nestHeadings();
    }

    /**
     * Recursive function to nest a list of headings using the keys 'id' & 'parent'.
     *
     * @param integer $parent
     * @return null|array
     */
    private function nestHeadings($parent = 0)
    {
        $headings = [];
        foreach ($this->headings as $key => $heading) {
            if (!array_key_exists('parent', $heading) || $heading['parent'] != $parent) continue;

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
     * Injects header HTML-Elements with their corersponding ids.
     * @return String
     */
    public function injectIds($value): string
    {
        // Do all the regex magic here
        $injected = preg_replace_callback(
            '#<(h[1-' . $this->maxLevel . '])(.*?)>(.*?)</\1>#si',
            // callback
            function ($matches) {
                // the html tag
                $tag = $matches[1];
                // decode html entities to support special characters in headings/slug
                $title = html_entity_decode(strip_tags($matches[3]));
                $hasId = preg_match('/id=(["\'])(.*?)\1[\s>]/si', $matches[2], $matchedIds);
                $id = $hasId ? $matchedIds[2] : $this->generateId($title, false);

                if ($hasId) {
                    return $matches[0];
                }
                // rebuild the tag with Id.
                return sprintf('<%s%s id="%s">%s</%s>', $tag, $matches[2], $id, $matches[3], $tag);
            },
            $value
        );
        return $injected;
    }

    /**
     * Slugifies a given title
     * @return string        [description]
     */
    private function generateId($title, $list = false): string
    {
        $id = $raw = Str::slug($title);
        $count = 2;
        $suffix = $list ? 'list' : 'text';

        // make sure we don't have any duplicate ids via adding a counter at
        // the end of an id if it already exists.
        while ($this->slugs->contains($id . '-' . $suffix)) {
            $id = $raw . '-' . $count;
            $count++;
        }

        $this->slugs->push($id . '-' . $suffix);

        return $id;
    }
}
