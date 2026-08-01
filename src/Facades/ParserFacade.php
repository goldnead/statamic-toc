<?php

namespace Goldnead\StatamicToc\Facades;

use Goldnead\StatamicToc\Options;
use Goldnead\StatamicToc\Parser;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Parser make($content)
 * @method static Parser setContent($content)
 * @method static Parser options(Options $options)
 * @method static Parser depth($depth)
 * @method static Parser from($start)
 * @method static Parser to($level)
 * @method static Parser exclude($exclude)
 * @method static Parser flatten()
 * @method static Parser flattenIf($bool)
 * @method static bool isHTML()
 * @method static bool isBard()
 * @method static bool isMarkdown()
 * @method static array build()
 * @method static string injectIds($value, $params = null)
 *
 * @see Parser
 */
class ParserFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return Parser::class;
    }
}
