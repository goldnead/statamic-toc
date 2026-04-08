<?php

namespace Tests\Unit;

use Goldnead\StatamicToc\Tests\TestCase;
use Goldnead\StatamicToc\Tags\Toc as TocTag;
use Statamic\Facades\Antlers;

class RecursionTest extends TestCase
{
    protected $tag;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tag = resolve(TocTag::class)
            ->setParser(Antlers::parser())
            ->setContext([]);
    }

    /** @test */
    public function when_true_renders_toc()
    {
        $this->tag->setParameters([
            'content' => '<h1>Heading 1</h1><h2>Heading 2</h2>',
            'when' => true,
        ]);
        $output = $this->tag->index();

        $this->assertNotEmpty($output);
        $this->assertEquals('Heading 1', $output[0]['toc_title']);
    }

    /** @test */
    public function when_false_suppresses_toc()
    {
        $this->tag->setParameters([
            'content' => '<h1>Heading 1</h1><h2>Heading 2</h2>',
            'when' => false,
        ]);

        $this->assertSame([], $this->tag->index());
    }

    /** @test */
    public function when_string_false_suppresses_toc()
    {
        $this->tag->setParameters([
            'content' => '<h1>Heading 1</h1><h2>Heading 2</h2>',
            'when' => 'false',
        ]);

        $this->assertSame([], $this->tag->index());
    }

    /** @test */
    public function when_string_true_renders_toc()
    {
        $this->tag->setParameters([
            'content' => '<h1>Heading 1</h1><h2>Heading 2</h2>',
            'when' => 'true',
        ]);
        $output = $this->tag->index();

        $this->assertNotEmpty($output);
    }
}
