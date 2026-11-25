<?php

namespace Goldnead\StatamicToc\Tests\Unit;

use Goldnead\StatamicToc\Parser;
use Goldnead\StatamicToc\ServiceProvider;
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

    public function test_make_resets_options_set_on_the_previous_run()
    {
        $html = '<h1>A</h1><h2>B</h2><h3>C</h3><h4>D</h4>';

        $parser = (new Parser)->flatten()->depth(1);
        $parser->make($html);
        $result = $parser->build();

        // The default range is levels 1 to 3, so three headings are listed.
        // A leftover flat/depth(1) from the previous run would list only one.
        $this->assertSame(3, $result[0]['total_results']);
    }
}
