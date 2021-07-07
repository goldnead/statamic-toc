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
        $depth = $this->params->int("depth") ? $this->params->int("depth") : 3;
        // get raw data of the document
        $field = $this->params->get("field", "article");

        $content = $this->params->get("content");

        $raw = !$content ? $this->context->get($field)->raw() : $content;

        $isFlat = $this->params->bool("is_flat");
        // create parser and generate TOC items
        $elements = (new Parser($raw, $depth, $isFlat))->generateToc();

        return $this->output($elements);
    }

    /**
     * The {{ toc:count }} tag.
     *
     * @return integer
     */
    public function count()
    {
        $this->params->put("is_flat", true);
        return count($this->index());
    }
}
