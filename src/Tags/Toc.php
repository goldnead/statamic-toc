<?php

/**
 * This is the Tag that generates the TOC-Element.
 * This only works for articles where the contents are stored in a
 * value named "article".
 *
 */

namespace Goldnead\StatamicToc\Tags;

use Statamic\Tags\Tags;
use Goldnead\StatamicToc\Parser;
use Statamic\Tags\Concerns;

class Toc extends Tags
{
    use Concerns\OutputsItems;

    /**
     * The {{ toc }} tag.
     *
     * @return string|array
     */
    public function index()
    {
        // get the supported header-levels
        $depth = $this->params->int("depth", 3);
        $start = $this->params->get('from', "h1");
        // get raw data of the document
        $field = $this->params->get("field", "article");

        $content = $this->params->get("content");

        if (!$content && !$this->context->get($field)) {
            // return an empty array so the $this->count() function works properly
            return [];
        }

        $raw = !$content ? $this->context->get($field)->raw() : $content;

        $isFlat = $this->params->bool("is_flat");

        // create parser and generate TOC items
        $toc = new Parser($raw);
        $toc->depth($depth)
            ->from($start)
            ->flattenIf($isFlat);

        return $this->output(
            $toc->build()
        );
    }

    /**
     * The {{ toc:count }} tag.
     *
     * @return integer
     */
    public function count()
    {
        $this->params->put("depth", $this->params->int("depth", 6));

        $result = $this->index();

        return isset($result[0]["total_results"]) ? $result[0]["total_results"] : $result["total_results"];
    }
}
