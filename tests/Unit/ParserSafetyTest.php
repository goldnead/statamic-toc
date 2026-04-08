<?php

namespace Tests\Unit;

use Goldnead\StatamicToc\Parser;
use Goldnead\StatamicToc\Tests\TestCase;

class ParserSafetyTest extends TestCase
{
    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
    public function test_from_called_twice_does_not_expand_depth()
    {
        $html = '<h2>A</h2><h3>B</h3><h4>C</h4>';

        $once = (new Parser($html))->from('h2')->depth(2)->build();
        $twice = (new Parser($html))->from('h2')->depth(2)->from('h2')->build();

        $this->assertEquals(count($once), count($twice));
        $this->assertEquals(count($once[0]['children']), count($twice[0]['children']));
    }
}
