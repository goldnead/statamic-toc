<?php

namespace Tests\Unit;

use Goldnead\StatamicToc\Tests\TestCase;
use Goldnead\StatamicToc\Tags\Toc as TocTag;
use Statamic\Facades\Antlers;
use Statamic\Fields\Value;
use Statamic\Fieldtypes\Bard;

class TagTest extends TestCase
{
  public $tag;

  public function setUp(): void
  {
    parent::setUp();
    $this->tag = resolve(TocTag::class)
      ->setParser(Antlers::parser())
      ->setContext([]);
  }

  /** @test */
  public function test_it_can_be_instantiated()
  {
    $this->assertInstanceOf(TocTag::class, $this->tag);
  }

  /** @test */
  public function tag_outputs_array()
  {
    $this->tag->setParameters([
      'content' => $this->fakeHTMLContent(2, 3),
    ]);
    $output = $this->tag->index();

    $this->assertIsArray($output);
  }

  /** @test */
  public function tag_outputs_correct_number_of_items()
  {
    $this->tag->setParameters([
      'content' => $this->fakeBardArray(2, 3),
    ]);
    $output = $this->tag->index();

    $this->assertEquals($this->countChildren($output), 3);
  }

  /** @test */
  public function tag_outputs_array_with_correct_number_of_items_when_flat()
  {
    $this->tag->setParameters([
      'content' => $this->fakeHTMLContent(2, 3),
      "is_flat" => true,
    ]);
    $output = $this->tag->index();
    $this->assertEquals($this->countChildren($output), 3);
  }

  /** @test */
  public function tag_outputs_array_with_correct_number_of_items_when_not_flat()
  {
    $this->tag->setParameters([
      'content' => $this->fakeHTMLContent(2, 3),
    ]);
    $output = $this->tag->index();
    $this->assertEquals($this->countChildren($output), 3);
  }

  /** @test */
  public function tag_outputs_array_with_correct_number_of_items_when_not_flat_and_depth_is_set()
  {
    $this->tag->setParameters([
      'content' => $this->fakeBardArray(3, 6, false),
      "depth" => 2,
    ]);
    $output = $this->tag->index();
    $this->assertEquals($this->countChildren($output), 2);
  }

  /** @test */
  public function tag_outputs_array_with_correct_number_of_items_when_not_flat_and_depth_is_set_and_from_is_set()
  {
    $this->tag->setParameters([
      'content' => $this->fakeHTMLContent(3, 6, false),
      "depth" => 2,
      "from" => "h1",
    ]);
    $output = $this->tag->index();
    $this->assertEquals($this->countChildren($output), 2);
  }

  /** @test */
  public function tag_outputs_array_with_correct_number_of_items_when_not_flat_and_depth_is_set_and_from_is_set_and_field_is_set()
  {
    $this->tag->setParameters([
      "depth" => 2,
      "from" => "h2",
      "field" => "article",
    ]);
    $bard = new Value($this->fakeBardArray(2, 3), "article", new Bard());
    $this->tag->setContext([
      "article" => $bard,
    ]);

    $output = $this->tag->index();

    $this->assertEquals($this->countChildren($output), 2);
  }

  /** @test */
  public function tag_outputs_array_with_correct_number_of_items_when_not_flat_and_depth_is_set_and_from_is_set_and_field_is_set_and_content_is_set()
  {
    $this->tag->setParameters([
      "depth" => 2,
      "from" => "h2",
      "field" => "article",
      "content" => $this->fakeHTMLContent(2, 3),
    ]);
    $bard = new Value($this->fakeBardArray(2, 3), "article", new Bard());
    $this->tag->setContext([
      "article" => $bard,
    ]);

    $output = $this->tag->index();

    $this->assertEquals($this->countChildren($output), 2);
  }

  /** @test */
  public function tag_outputs_array_with_correct_number_of_items_when_not_flat_and_depth_is_set_and_from_is_set_and_field_is_set_and_content_is_set_and_is_flat_is_set()
  {
    $this->tag->setParameters([
      "depth" => 2,
      "from" => "h2",
      "field" => "article",
      "content" => $this->fakeHTMLContent(2, 3),
      "is_flat" => true,
    ]);
    $bard = new Value($this->fakeBardArray(2, 3), "article", new Bard());
    $this->tag->setContext([
      "article" => $bard,
    ]);

    $output = $this->tag->index();

    $this->assertEquals($this->countChildren($output), 2);
  }

  /** @test */
  public function tag_outputs_array_with_correct_number_of_items_when_not_flat_and_depth_is_set_and_from_is_set_and_field_is_set_and_content_is_set_and_is_flat_is_set_and_from_is_set()
  {
    $this->tag->setParameters([
      "depth" => 2,
      "from" => "h2",
      "field" => "article",
      "content" => $this->fakeHTMLContent(2, 3),
      "is_flat" => true,
    ]);
    $bard = new Value($this->fakeBardArray(2, 3), "article", new Bard());
    $this->tag->setContext([
      "article" => $bard,
    ]);

    $output = $this->tag->index();

    $this->assertEquals($this->countChildren($output), 2);
  }
}
