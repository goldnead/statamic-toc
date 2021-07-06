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

    /**
     * new Parser($content, 3).
     * Creates a parser object and stores all information in local variables
     */
    public function __construct($content, $depth = 3, $isFlat = false)
    {
        $this->content = $content;
        $this->level = $depth;
        $this->isFlat = $isFlat;
    }

    /**
     * Generates an array of elements necessairy for the TOC-Tag to
     * function.
     *
     * @return Array
     */
    public function generateToc(): array
    {
        // create a collection with the content array
        $raw = collect($this->content);

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
                    "toc_id" => $this->generateId($title),
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
            if ($heading['parent'] != $parent) continue;

            $headings[] = $heading;


            if ($children = $this->nestHeadings($heading['id'])) {
                $length = count($headings);
                $headings[$length - 1]['children'] = $children;
            }
        }
        return empty($headings) ? null : $headings;
    }

    /**
     * Injects header HTML-Elements with their corersponding ids.
     * @return String
     */
    public function injectIds(): string
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
                $id = $hasId ? $matchedIds[2] : $this->generateId($title);

                if ($hasId) {
                    return $matches[0];
                }
                // rebuild the tag with Id.
                return sprintf('<%s%s id="%s">%s</%s>', $tag, $matches[2], $id, $matches[3], $tag);
            },
            $this->content
        );
        return $injected;
    }

    /**
     * Slugifies a given title
     * @return string        [description]
     */
    public function generateId($title): string
    {
        $id = $raw = Str::slug($title);
        $count = 2;

        // make sure we don't have any duplicate ids via adding a counter at
        // the end of an id if it already exists.
        while (in_array($id, $this->slugs)) {
            $id = $raw . '-' . $count;
            $count++;
        }

        $this->slugs[] = $id;
        return $id;
    }
}