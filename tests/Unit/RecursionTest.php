<?php

namespace Tests\Unit;

use Goldnead\StatamicToc\Tests\TestCase;
use Statamic\Facades\Antlers;

class RecursionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        app('statamic.tags')->put('toc', \Goldnead\StatamicToc\Tags\Toc::class);
    }

    /** @test */
    public function it_renders_toc_with_when_param()
    {
        $content = '<h1>Heading 1</h1><h2>Heading 2</h2>';
        
        $template = <<<'EOT'
    <ol>
    {{ toc :content="content" :when="show_toc" }}
        <li>
            <a href="#{{ toc_id }}">{{ toc_title }}</a>
            {{ if children }}
            <ol>
                {{ *recursive children* }}
            </ol>
            {{ /if }}
        </li>
    {{ /toc }}
    </ol>
EOT;

        $data = [
            'content' => $content,
            'show_toc' => true,
        ];

        $output = (string) Antlers::parse($template, $data);

        $this->assertStringContainsString('Heading 1', $output);
        
        // Test false
        $data['show_toc'] = false;
        $output = (string) Antlers::parse($template, $data);
        $this->assertStringNotContainsString('Heading 1', $output);
    }
}
