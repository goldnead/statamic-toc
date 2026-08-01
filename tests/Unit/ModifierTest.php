<?php

namespace Goldnead\StatamicToc\Tests\Unit;

use Goldnead\StatamicToc\Modifiers\Toc;
use Goldnead\StatamicToc\Tests\TestCase;
use Statamic\Fields\Value;

class ModifierTest extends TestCase
{
    public $modifier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->modifier = resolve(Toc::class);
    }

    public function test_modifier_outputs_string()
    {
        $output = $this->modifier->index(new Value($this->fakeHTMLContent(2, 3)));
        $this->assertIsString($output);
    }

    public function test_modifier_adds_slugified_id_to_heading()
    {
        $output = $this->modifier->index(new Value($this->fakeHTMLContent(2, 3)));
        $this->assertStringContainsString('id="heading-1"', $output);
        $this->assertStringContainsString('id="heading-2"', $output);
        $this->assertStringContainsString('id="heading-3"', $output);
    }
}
