<?php

namespace Goldnead\StatamicToc\Tests\Unit;

use Goldnead\StatamicToc\Options;
use Goldnead\StatamicToc\Parser;
use Goldnead\StatamicToc\Tests\TestCase;

class OptionsTest extends TestCase
{
    private function levels(array $tree): array
    {
        $levels = [];

        foreach ($tree as $key => $item) {
            if (! is_int($key)) {
                continue;
            }

            $levels[] = $item['level'];
            $levels = array_merge($levels, $this->levels($item['children'] ?? []));
        }

        return $levels;
    }

    private function document(): string
    {
        return '<h1>1</h1><h2>2</h2><h3>3</h3><h4>4</h4><h5>5</h5><h6>6</h6>';
    }

    // ---------------------------------------------------------------- Options

    public function test_a_level_is_read_as_a_number_or_as_h_notation()
    {
        $this->assertSame(2, Options::level('h2', 1));
        $this->assertSame(2, Options::level('H2', 1));
        $this->assertSame(2, Options::level(2, 1));
        $this->assertSame(6, Options::level('h9', 1), 'Clamped to what HTML has.');
        $this->assertSame(1, Options::level('h0', 1), 'Nonsense falls back.');
        $this->assertSame(3, Options::level(null, 3));
        $this->assertSame(3, Options::level('', 3));
    }

    public function test_the_range_keeps_its_width_when_the_start_moves()
    {
        $options = Options::default()->withDepth(2)->withFrom('h3');

        $this->assertSame(3, $options->from);
        $this->assertSame(4, $options->to);
        $this->assertSame(2, $options->depth());
    }

    public function test_setting_the_start_twice_does_not_widen_the_range()
    {
        $once = Options::default()->withDepth(2)->withFrom('h2');
        $twice = Options::default()->withDepth(2)->withFrom('h2')->withFrom('h2');

        $this->assertSame($once->to, $twice->to);
    }

    public function test_to_is_absolute()
    {
        $options = Options::default()->withFrom('h2')->withTo('h5');

        $this->assertSame(2, $options->from);
        $this->assertSame(5, $options->to);
        $this->assertSame(4, $options->depth());
    }

    public function test_to_never_falls_below_from()
    {
        $options = Options::default()->withFrom('h4')->withTo('h2');

        $this->assertSame(4, $options->to);
    }

    public function test_options_are_immutable()
    {
        $base = Options::default();
        $changed = $base->withDepth(6);

        $this->assertSame(3, $base->to);
        $this->assertSame(6, $changed->to);
    }

    // ----------------------------------------------------------------- Parser

    public function test_the_parser_takes_a_whole_option_set()
    {
        $tree = (new Parser($this->document()))
            ->options(Options::default()->withFrom('h2')->withTo('h4'))
            ->flatten()
            ->build();

        $this->assertSame([2, 3, 4], $this->levels($tree));
    }

    public function test_to_and_depth_describe_the_same_range()
    {
        $withDepth = (new Parser($this->document()))->from('h2')->depth(3)->flatten()->build();
        $withTo = (new Parser($this->document()))->from('h2')->to('h4')->flatten()->build();

        $this->assertSame($this->levels($withDepth), $this->levels($withTo));
        $this->assertSame([2, 3, 4], $this->levels($withTo));
    }

    public function test_the_order_of_from_and_depth_no_longer_matters()
    {
        $a = (new Parser($this->document()))->from('h2')->depth(2)->flatten()->build();
        $b = (new Parser($this->document()))->depth(2)->from('h2')->flatten()->build();

        $this->assertSame([2, 3], $this->levels($a));
        $this->assertSame($this->levels($a), $this->levels($b));
    }
}
