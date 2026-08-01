<?php

namespace Goldnead\StatamicToc\Tests\Unit;

use Goldnead\StatamicToc\ServiceProvider;
use Goldnead\StatamicToc\Tests\Concerns\RendersAntlers;
use Statamic\Testing\AddonTestCase;

class ConfigTest extends AddonTestCase
{
    use RendersAntlers;

    protected string $addonServiceProvider = ServiceProvider::class;

    private const DOCUMENT = '<h1>Eins</h1><h2>Zwei</h2><h3>Drei</h3><h4>Vier</h4><h5>Fünf</h5>';

    private function titles(string $params = '', array $context = []): array
    {
        $out = $this->render(
            '{{ toc '.$params.' }}[{{ toc_title }}]{{ if children }}{{ *recursive children* }}{{ /if }}{{ /toc }}',
            $context + ['article' => self::DOCUMENT]
        );

        preg_match_all('/\[([^\]]+)\]/', $out, $m);

        return $m[1];
    }

    public function test_the_defaults_ship_with_the_addon()
    {
        // Publishing the config is optional. Without it these apply, and they
        // are the values the addon has always used.
        $this->assertSame('article', config('statamic-toc.field'));
        $this->assertSame('h1', config('statamic-toc.from'));
        $this->assertSame(3, config('statamic-toc.depth'));
        $this->assertNull(config('statamic-toc.to'));
        $this->assertFalse(config('statamic-toc.flat'));
    }

    public function test_the_default_field_is_configurable()
    {
        config()->set('statamic-toc.field', 'body');

        $this->assertSame(
            ['Eins'],
            $this->titles('', ['body' => '<h1>Eins</h1>', 'article' => '<h1>Falsches Feld</h1>'])
        );
    }

    public function test_the_default_range_is_configurable()
    {
        config()->set('statamic-toc.from', 'h2');
        config()->set('statamic-toc.depth', 2);

        $this->assertSame(['Zwei', 'Drei'], $this->titles());
    }

    public function test_a_configured_to_wins_over_a_configured_depth()
    {
        config()->set('statamic-toc.depth', 2);
        config()->set('statamic-toc.to', 'h4');

        $this->assertSame(['Eins', 'Zwei', 'Drei', 'Vier'], $this->titles());
    }

    public function test_a_tag_parameter_still_wins_over_the_config()
    {
        config()->set('statamic-toc.from', 'h2');
        config()->set('statamic-toc.depth', 1);

        $this->assertSame(['Drei'], $this->titles('from="h3" depth="1"'));
    }

    public function test_the_default_flatness_is_configurable()
    {
        config()->set('statamic-toc.flat', true);

        // Flat means no nesting, so the recursive block never fires and every
        // heading in range shows up at the top level.
        $out = $this->render(
            '{{ toc }}[{{ toc_title }}{{ if children }}+kinder{{ /if }}]{{ /toc }}',
            ['article' => self::DOCUMENT]
        );

        $this->assertStringNotContainsString('+kinder', $out);
        $this->assertSame(3, substr_count($out, '['));
    }
}
