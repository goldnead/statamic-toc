<?php

namespace Tests\Unit;

use Goldnead\StatamicToc\Parser;
use Goldnead\StatamicToc\Tests\TestCase;

class ParserTest extends TestCase
{
    public $parser;

    /** @test */
    public function test_can_detect_html()
    {
        $html = $this->fakeHTMLContent(2, 3);
        $parser = new Parser($html);
        $this->assertTrue($parser->isHtml($html));

        $markdown = $this->fakeMarkdownContent(2, 3);
        $parser = new Parser($markdown);
        $this->assertFalse($parser->isHTML($markdown));
    }

    /** @test */
    public function test_can_detect_markdown()
    {
        $markdown = $this->fakeMarkdownContent(2, 3);
        $parser = new Parser($markdown);
        $this->assertTrue($parser->isMarkdown($markdown));

        $html = $this->fakeHTMLContent(2, 3);
        $parser = new Parser($html);
        $this->assertFalse($parser->isMarkdown($html));
    }

    /** @test */
    public function test_can_build_toc_tree_from_markdown()
    {
        $markdown = $this->fakeMarkdownContent(4, 3);
        $markdown .= $this->fakeMarkdownContent(2, 3, true, false);
        $parser = new Parser($markdown);
        $tree = $parser->depth(6)->build();

        $this->assertEquals(1, count($tree));
        $this->assertEquals(2, count($tree[0]['children']));
        $this->assertEquals(1, count($tree[0]['children'][0]['children']));
        $this->assertEquals(1, count($tree[0]['children'][1]['children']));
    }

    /** @test */
    public function test_can_create_toc_tree()
    {
        $html = $this->fakeHTMLContent(4, 3);
        $html .= $this->fakeHTMLContent(2, 3, true, false);
        $parser = new Parser($html);
        $tree = $parser->depth(6)->build();

        $this->assertEquals(1, count($tree));
        $this->assertEquals(2, count($tree[0]['children']));
        $this->assertEquals(1, count($tree[0]['children'][0]['children']));
        $this->assertEquals(1, count($tree[0]['children'][1]['children']));
    }

    /** @test */
    public function test_can_flatten_toc_tree()
    {
        $html = $this->fakeHTMLContent(4, 3);
        $html .= $this->fakeHTMLContent(2, 3, true, false);
        $parser = new Parser($html);
        $tree = $parser->depth(6)->flatten()->build();

        $this->assertEquals(5, count($tree));
    }

    /** @test */
    public function test_can_flatten_toc_tree_with_depth()
    {
        $html = $this->fakeHTMLContent(4, 3);
        $html .= $this->fakeHTMLContent(2, 3, true, false);
        $parser = new Parser($html);
        $tree = $parser->depth(2)->flatten()->build();

        $this->assertEquals(3, count($tree));
    }

    /** @test */
    public function can_build_tree_from_html()
    {
        $html = $this->fakeHTMLContent(4, 3);
        $html .= $this->fakeHTMLContent(2, 3, true, false);
        $parser = new Parser($html);
        $tree = $parser->depth(6)->build();

        $this->assertEquals(1, count($tree));
        $this->assertEquals(2, count($tree[0]['children']));
        $this->assertEquals(1, count($tree[0]['children'][0]['children']));
        $this->assertEquals(1, count($tree[0]['children'][1]['children']));
    }

    /** @test */
    public function test_can_build_tree_from_bard()
    {
        $content = array_merge($this->fakeBardArray(4, 3), $this->fakeBardArray(2, 3, true, false));
        $parser = new Parser($content);
        $tree = $parser->depth(6)->build();

        $this->assertEquals(1, count($tree));
        $this->assertEquals(2, count($tree[0]['children']));
        $this->assertEquals(1, count($tree[0]['children'][0]['children']));
        $this->assertEquals(1, count($tree[0]['children'][1]['children']));
    }

    /** @test */
    public function test_contains_total_results_and_no_results()
    {
        $html = $this->fakeHTMLContent(4, 3);
        $html .= $this->fakeHTMLContent(2, 3, true, false);
        $parser = new Parser($html);
        $tree = $parser->depth(6)->build();
        $this->assertEquals(5, $tree[0]['total_results']);

        $html = '<p>No results found.</p>';
        $parser = new Parser($html);
        $tree = $parser->depth(6)->build();
        $this->assertEquals(true, $tree['no_results']);
    }

    /** @test */
    public function test_array_tree_format_is_correct()
    {
        $content = array_merge($this->fakeBardArray(4, 3), $this->fakeBardArray(2, 3, true, false));
        $parser = new Parser($content);
        $tree = $parser->depth(6)->build();

        $this->assertChild($tree[0]);
    }

    private function assertChild($child)
    {
        $this->assertIsString($child['toc_title']);
        $this->assertIsString($child['toc_id']);
        if (isset($child['is_root'])) {
            $this->assertIsBool($child['is_root']);
        }
        if (isset($child['has_children'])) {
            $this->assertIsBool($child['has_children']);
        }
        if (isset($child['children'])) {
            $this->assertIsArray($child['children']);
            // If children exist, total_children should also exist
            $this->assertArrayHasKey('total_children', $child);
            $this->assertIsInt($child['total_children']);
            $this->assertEquals(count($child['children']), $child['total_children']);
            foreach ($child['children'] as $child) {
                $this->assertChild($child);
            }
        }
        if ($child['level'] > 1) {
            $this->assertIsInt($child['parent']);
            $this->assertGreaterThan(0, $child['parent']);
        }
        $this->assertIsInt($child['level']);
    }

    /** @test */
    public function test_can_generate_ids()
    {
        $html = $this->fakeHTMLContent(4, 3);
        $html .= $this->fakeHTMLContent(2, 3, true, false);
        $parser = new Parser($html);
        $tree = $parser->depth(6)->build();

        $this->assertEquals('heading-1', $tree[0]['toc_id']);
        $this->assertEquals('heading-2', $tree[0]['children'][0]['toc_id']);
        $this->assertEquals('heading-2-2', $tree[0]['children'][1]['toc_id']);
        $this->assertEquals('heading-3', $tree[0]['children'][0]['children'][0]['toc_id']);
    }
}
