<?php

namespace Goldnead\StatamicToc\Tests\Concerns;

use Statamic\Facades\Antlers;

/**
 * Renders an Antlers template the way a real page does.
 *
 * The third argument of Antlers::parse() marks the template as trusted. Without
 * it Antlers treats the string as user data and skips every tag without a word
 * of complaint, so the test asserts against an empty string and passes for the
 * wrong reason. It belongs here once rather than in every test that renders.
 */
trait RendersAntlers
{
    protected function render(string $template, array $context = []): string
    {
        return (string) Antlers::parse($template, $context, true);
    }
}
