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

    protected static $handle = 'toc';

    /**
     * The {{ toc }} tag.
     *
     * @return string|array
     */
    public function index()
    {
        $when = $this->params->get('when', true);
        if ($when instanceof \Statamic\Fields\Value) {
            $when = $when->raw();
        }
        if ($when === false || $when === 'false' || $when === 0 || $when === '0') {
            return [];
        }
        
        // get the supported header-levels

        // get the supported header-levels
        $depth = $this->params->int("depth", 3);
        $start = $this->params->get('from', "h1");
        // get raw data of the document
        $field = $this->params->get("field", "article");

        $content = $this->params->get("content");

        // Statamic 5+ runtime Antlers passes dynamic params as Value objects.
        // Unwrap so the Parser receives a raw string or Bard array.
        if ($content instanceof \Statamic\Fields\Value) {
            $content = $content->raw();
        }

        if (!$content && !$this->context->get($field)) {
            // return an empty array so the $this->count() function works properly
            return [];
        }

        $raw = $content;

        if (!$content) {
            $field = $this->context->get($field);
            $raw = is_string($field) ? $field : $field->raw();
        }

        $isFlat = $this->params->bool("is_flat");
        $exclude = $this->params->get("exclude");

        // create parser and generate TOC items
        $toc = new Parser($raw);
        $toc->depth($depth)
            ->from($start)
            ->exclude($exclude)
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

        if (empty($result)) {
            return 0;
        }

        return isset($result[0]) ? $result[0]["total_results"] : $result["total_results"];
    }
}
