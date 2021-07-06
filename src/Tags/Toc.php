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

class Toc extends Tags
{
    /**
     * The {{ toc }} tag.
     *
     * @return string|array
     */
    public function index()
    {
        // get the supported header-levels
        $level = $this->params->int("level") ? $this->params->int("level") : 3;
        // get raw data of the document
        $raw = $this->context->get("article")->raw();

        $isFlat = $this->params->bool("is_flat");
        // create parser and generate TOC items
        $elements = (new Parser($raw, $level, $isFlat))->generateToc();

        return $elements;
    }

    public function count()
    {
        return count($this->index());
    }
}
