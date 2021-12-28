<?php

namespace Tests\Unit;

use Goldnead\StatamicToc\Modifiers\Toc;
use Goldnead\StatamicToc\Tests\TestCase;
use Statamic\Fields\Value;

class ModifierTest extends TestCase
{
  public $modifier;

  public function setUp(): void
  {
    parent::setUp();
    $this->modifier = resolve(Toc::class);
  }

  /** @test */
  public function modifier_outputs_string()
  {
    $output = $this->modifier->index(new Value($this->fakeHTMLContent(2, 3)));
    $this->assertIsString($output);
  }

  /** @test */
  public function modifier_adds_slugified_id_to_heading()
  {
    $output = $this->modifier->index(new Value($this->fakeHTMLContent(2, 3)));
    $this->assertStringContainsString('id="heading-1"', $output);
    $this->assertStringContainsString('id="heading-2"', $output);
    $this->assertStringContainsString('id="heading-3"', $output);
  }
}
