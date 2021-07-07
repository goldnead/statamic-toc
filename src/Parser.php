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

    private $level;

    private $headings = [];

    public function __construct()
    {
        $this->slugs = collect($this->slugs);
    }

    /**
     * new Parser($content, 3).
     * Creates a parser object and stores all information in local variables
     */
    public function make($content, $depth = 3, $isFlat = false)
    {
        $this->content = $content;
        $this->level = $depth;
        $this->isFlat = $isFlat;
        $this->isHtml = is_string($content);
        return $this;
    }

    public function generateToc(): array
    {
        if ($this->isHtml) {
            return $this->generateFromHtml();
        }
        return $this->generateFromStructure();
    }

    private function generateFromHtml(): array
    {
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

        $xpath = new \DOMXpath($doc);
        $htags = $xpath->query('//h1 | //h2 | //h3 | //h4 | //h5 | //h6');

        $headings = collect([]);
        foreach ($htags as $tag) {
            $headings->push([
                "type" => "heading",
                "attrs" => [
                    "level" => (int) ltrim($tag->nodeName, 'h')
                ],
                "content" => [
                    [
                        "type" => "text",
                        "text" => $tag->nodeValue,
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
        if ($this->isHtml && !$structure) {
            return $this->generateFromHtml();
        }

        // create a collection with the content array
        $raw = !$structure ? collect($this->content) : collect($structure);

        // filter out all the headings
        $headings = $raw->filter(function ($item) {
            return $item["type"] === "heading" && $item["attrs"]["level"] <= $this->level;
        });

        if ($headings->count() > 0) {
            // iterate through each heading and push its information into
            // an array.
            $headings->each(function ($heading, $key) use (&$tocArray) {
                // Check, if the content type is really text
                if ($heading["content"][0]["type"] !== "text") {
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
     * Nests a list of headings using the keys 'id' & 'parent'.
     *
     * @param integer $parent
     * @return null|array
     */
    public function nestHeadings($parent = 0)
    {
        $headings = [];
        foreach ($this->headings as $key => $heading) {
            if (!array_key_exists('parent', $heading) || $heading['parent'] != $parent) continue;

            $headings[] = $heading;


            if ($children = $this->nestHeadings($heading['id'])) {
                $length = count($headings);
                $headings[$length - 1]['children'] = $children;
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
            '#<(h[1-' . $this->level . '])(.*?)>(.*?)</\1>#si',
            // callback
            function ($matches) {
                // the html tag
                $tag = $matches[1];
                $title = strip_tags($matches[3]);
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
    public function generateId($title, $list = false): string
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
