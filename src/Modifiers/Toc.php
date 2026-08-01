<?php

/**
 * This modifier takes a text-element and parses it for possible headings so
 * it can inject them with their corresponding Ids.
 */

namespace Goldnead\StatamicToc\Modifiers;

use Goldnead\StatamicToc\Anchors\IdInjector;
use Goldnead\StatamicToc\Extractors\Detector;
use Goldnead\StatamicToc\Registry;
use Statamic\Modifiers\Modifier;

class Toc extends Modifier
{
    /**
     * Injects IDs into the DOM.
     *
     * The anchors come from the shared registry, not from a second slug run of
     * its own. That is the whole point: the tag and this modifier read the same
     * decision, so an anchor cannot point at a heading that never got the id.
     *
     * @param  mixed  $value  The value to be modified
     * @param  array  $params  Any parameters used in the modifier
     * @param  array  $context  Contextual values
     * @return mixed
     */
    public function index($value, $params = [], $context = [])
    {
        $html = (string) $value;

        if ($html === '') {
            return $html;
        }

        $headings = (new Detector)->for($html)->extract($html);

        return (new IdInjector)->inject(
            $html,
            app(Registry::class)->anchorsFor($headings),
            empty($params) ? null : implode(' ', (array) $params)
        );
    }
}
