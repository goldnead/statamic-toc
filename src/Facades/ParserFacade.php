<?php

namespace Goldnead\StatamicToc\Facades;

use Illuminate\Support\Facades\Facade;
use Goldnead\StatamicToc\Parser;

/**
 * @method static int values($one, $two)
 * @method static int strings(string $one, string $two)
 * @method static int numbers($one, $two)
 *
 * @see \Statamic\Support\Comparator
 */
class ParserFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return Parser::class;
    }
}
