<?php

/**
 * This modifier takes a text-element and parses it for possible headings so
 * it can inject them with their corresponding Ids.
 */

namespace Goldnead\StatamicToc\Modifiers;

use Goldnead\StatamicToc\Facades\ParserFacade as Parser;
use Statamic\Modifiers\Modifier;

class Toc extends Modifier
{
    /**
     * Injects IDs into the DOM.
     *
     * @param  mixed  $value    The value to be modified
     * @param  array  $params   Any parameters used in the modifier
     * @param  array  $context  Contextual values
     * @return mixed
     */
    public function index($value, $params = [])
    {
        // initiate parser and let him inject ids into the DOM
        $content = Parser::injectIds($value, empty($params) ? null : $params);

        return $content;
    }
}
