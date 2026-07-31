<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Defaults for the {{ toc }} tag
    |--------------------------------------------------------------------------
    |
    | Every one of these can still be overridden per tag. Publishing this file
    | is optional: without it the values below apply, which are the same ones
    | the addon has always used.
    |
    |     php artisan vendor:publish --tag=statamic-toc-config
    |
    */

    // The field the tag reads when no "field" or "content" parameter is given.
    'field' => 'article',

    // The heading level the list starts at.
    'from' => 'h1',

    // How many levels the list spans, counted from "from".
    'depth' => 3,

    // The level the list stops at, absolute. Wins over "depth" when set.
    'to' => null,

    // Return a flat array instead of a nested tree.
    'flat' => false,

];
