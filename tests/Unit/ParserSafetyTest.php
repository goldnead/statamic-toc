<?php

namespace Goldnead\StatamicToc\Tests\Unit;

use Goldnead\StatamicToc\Parser;
use Goldnead\StatamicToc\ServiceProvider;
use Illuminate\Support\Facades\Log;
use Statamic\Testing\AddonTestCase;

class ParserSafetyTest extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;

    public function test_handles_heading_with_missing_attrs()
    {
        $content = [
            [
                'type' => 'heading',
                // missing 'attrs' entirely
                'content' => [['type' => 'text', 'text' => 'Test']],
            ],
        ];

        $result = (new Parser($content))->build();

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['total_results']);
    }

    public function test_handles_heading_with_missing_content()
    {
        $content = [
            [
                'type' => 'heading',
                'attrs' => ['level' => 2],
                // missing 'content' entirely
            ],
        ];

        $result = (new Parser($content))->build();

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['total_results']);
    }

    public function test_handles_heading_with_empty_content_array()
    {
        $content = [
            [
                'type' => 'heading',
                'attrs' => ['level' => 2],
                'content' => [],
            ],
        ];

        $result = (new Parser($content))->build();

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['total_results']);
    }

    public function test_handles_items_missing_type_key()
    {
        $content = [
            ['text' => 'not a heading'],
            [
                'type' => 'heading',
                'attrs' => ['level' => 2],
                'content' => [['type' => 'text', 'text' => 'Valid']],
            ],
        ];

        $result = (new Parser($content))->build();

        $this->assertIsArray($result);
        // When results exist, total_results is attached to the first item
        $this->assertEquals(1, $result[0]['total_results']);
    }

    public function test_handles_content_first_element_not_array()
    {
        $content = [
            [
                'type' => 'heading',
                'attrs' => ['level' => 2],
                'content' => ['not an array element'],
            ],
        ];

        $result = (new Parser($content))->build();

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['total_results']);
    }

    public function test_handles_scalar_content()
    {
        // A malformed Bard node whose 'content' is a scalar (e.g. an int)
        // instead of an array must not crash on the content dereference.
        $content = [
            [
                'type' => 'heading',
                'attrs' => ['level' => 2],
                'content' => 123,
            ],
        ];

        $result = (new Parser($content))->build();

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['total_results']);
    }

    public function test_handles_object_attrs()
    {
        // A malformed Bard node whose 'attrs' is an object (stdClass) instead
        // of an array must not crash on the level access with "Cannot use
        // object of type stdClass as array". The null-coalesce on
        // $items['attrs']['level'] does not protect against this.
        $attrs = new \stdClass;
        $attrs->level = 2;

        $content = [
            [
                'type' => 'heading',
                'attrs' => $attrs,
                'content' => [['type' => 'text', 'text' => 'Test']],
            ],
        ];

        $result = (new Parser($content))->build();

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['total_results']);
    }

    public function test_handles_non_numeric_level()
    {
        $content = [
            [
                'type' => 'heading',
                'attrs' => ['level' => 'zwei'],
                'content' => [['type' => 'text', 'text' => 'Test']],
            ],
        ];

        $result = (new Parser($content))->build();

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['total_results']);
    }

    public function test_handles_content_first_element_missing_text_key()
    {
        $content = [
            [
                'type' => 'heading',
                'attrs' => ['level' => 2],
                'content' => [['type' => 'text']], // missing 'text' key
            ],
        ];

        $result = (new Parser($content))->build();

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['total_results']);
    }

    public function test_from_preserves_depth_when_minlevel_changes()
    {
        // depth(2) from h2 should only include h2 and h3, not h4
        $html = '<h2>A</h2><h3>B</h3><h4>C</h4>';

        $result = (new Parser($html))->from('h2')->depth(2)->build();

        $this->assertCount(1, $result);
        $this->assertEquals('A', $result[0]['toc_title']);
        $this->assertCount(1, $result[0]['children']);
        $this->assertEquals('B', $result[0]['children'][0]['toc_title']);
        $this->assertEmpty($result[0]['children'][0]['children'] ?? []);
    }

    public function test_from_called_twice_does_not_expand_depth()
    {
        $html = '<h2>A</h2><h3>B</h3><h4>C</h4>';

        $once = (new Parser($html))->from('h2')->depth(2)->build();
        $twice = (new Parser($html))->from('h2')->depth(2)->from('h2')->build();

        $this->assertEquals(count($once), count($twice));
        $this->assertEquals(count($once[0]['children']), count($twice[0]['children']));
    }

    public function test_plain_exclude_matches_umlaut_titles_case_insensitively()
    {
        $html = '<h2>Über uns</h2><h2>Atem</h2>';

        $result = (new Parser($html))->exclude('über')->flatten()->build();

        $this->assertSame(['Atem'], array_column($result, 'toc_title'));
    }

    public function test_regex_exclude_matches_umlaut_titles_case_insensitively()
    {
        // Without the /u modifier PCRE compares bytes, so the capital Ü never
        // matches the lowercase ü in the pattern.
        $html = '<h2>Über uns</h2><h2>Atem</h2>';

        $result = (new Parser($html))->exclude('/über/i')->flatten()->build();

        $this->assertSame(['Atem'], array_column($result, 'toc_title'));
    }

    public function test_building_the_same_instance_twice_gives_the_same_result()
    {
        $html = '<h2>A</h2><h3>B</h3><h2>C</h2>';

        $parser = (new Parser($html))->depth(6);
        $first = $parser->build();
        $second = $parser->build();

        $this->assertSame($first, $second);
        $this->assertSame(3, $second[0]['total_results']);
    }

    public function test_a_regex_exclude_that_cannot_run_is_logged_not_swallowed()
    {
        Log::spy();

        // Catastrophic backtracking ends in a PCRE error: preg_match returns false.
        $jit = ini_get('pcre.jit');
        $limit = ini_get('pcre.backtrack_limit');
        ini_set('pcre.jit', '0');
        ini_set('pcre.backtrack_limit', '50');
        $this->beforeApplicationDestroyed(function () use ($jit, $limit) {
            ini_set('pcre.jit', $jit);
            ini_set('pcre.backtrack_limit', $limit);
        });

        $parser = (new Parser)->exclude('/(a+)+$/');
        $parser->make('<h2>'.str_repeat('a', 40).'b</h2>');
        $parser->build();

        Log::shouldHaveReceived('warning')
            ->with('statamic-toc: exclude pattern could not be applied', \Mockery::type('array'))
            ->once();
    }
}
