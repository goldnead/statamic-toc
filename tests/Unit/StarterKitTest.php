<?php

namespace Goldnead\StatamicToc\Tests\Unit;

use Goldnead\StatamicToc\ServiceProvider;
use Statamic\Facades\Antlers;
use Statamic\Testing\AddonTestCase;

class StarterKitTest extends AddonTestCase
{
    // Statamic's own harness. The hand-rolled TestCase in this repo never boots
    // the addon: its manifest fixture has no 'autoload' or 'provider' key, so
    // AddonServiceProvider::boot() returns early and the view namespace is
    // never registered.
    protected string $addonServiceProvider = ServiceProvider::class;

    private function render(string $template, array $context = []): string
    {
        // The third argument marks the template as trusted. Without it Antlers
        // treats the string as user data and skips every tag.
        return (string) Antlers::parse($template, $context, true);
    }

    private function article(): string
    {
        return '<h1>Einstieg</h1><h2>Atem</h2><h3>Stütze</h3><h2>Resonanz</h2>';
    }

    public function test_the_view_namespace_resolves()
    {
        $this->assertTrue(
            view()->exists('statamic-toc::starter-kit'),
            'The addon registers its views under the packageName, which is "statamic-toc" and not "goldnead/statamic-toc".'
        );
    }

    public function test_renders_from_the_context_field()
    {
        $output = $this->render(
            '{{ partial:statamic-toc::starter-kit }}',
            ['article' => $this->article()]
        );

        $this->assertStringContainsString('Einstieg', $output);
        $this->assertStringContainsString('Atem', $output);
        $this->assertStringContainsString('href="#einstieg"', $output);
    }

    public function test_nests_children()
    {
        $output = $this->render(
            '{{ partial:statamic-toc::starter-kit }}',
            ['article' => $this->article()]
        );

        // "Stütze" is an h3 below "Atem", so it must appear inside a nested list
        $this->assertMatchesRegularExpression(
            '#Atem.*<ol[^>]*>.*St(ü|&uuml;)tze#s',
            $output
        );
    }

    public function test_renders_nothing_without_headings()
    {
        $output = $this->render(
            '{{ partial:statamic-toc::starter-kit }}',
            ['article' => '<p>Kein einziger Zwischentitel.</p>']
        );

        $this->assertStringNotContainsString('<nav', $output);
        $this->assertSame('', trim($output));
    }

    public function test_honours_the_field_parameter()
    {
        $output = $this->render(
            '{{ partial:statamic-toc::starter-kit field="body" }}',
            ['body' => $this->article(), 'article' => '<h1>Falsches Feld</h1>']
        );

        $this->assertStringContainsString('Einstieg', $output);
        $this->assertStringNotContainsString('Falsches Feld', $output);
    }

    public function test_honours_from_and_depth()
    {
        $output = $this->render(
            '{{ partial:statamic-toc::starter-kit from="h2" depth="1" }}',
            ['article' => $this->article()]
        );

        $this->assertStringNotContainsString('Einstieg', $output);
        $this->assertStringContainsString('Atem', $output);
        $this->assertStringNotContainsString('tze', $output); // Stütze is h3
    }

    public function test_honours_the_exclude_parameter()
    {
        $output = $this->render(
            '{{ partial:statamic-toc::starter-kit exclude="Resonanz" }}',
            ['article' => $this->article()]
        );

        $this->assertStringContainsString('Atem', $output);
        $this->assertStringNotContainsString('Resonanz', $output);
    }

    public function test_honours_the_title_parameter()
    {
        $output = $this->render(
            '{{ partial:statamic-toc::starter-kit title="Auf dieser Seite" }}',
            ['article' => $this->article()]
        );

        $this->assertStringContainsString('Auf dieser Seite', $output);
        $this->assertStringNotContainsString('Table of Contents', $output);
    }

    public function test_the_count_matches_the_rendered_items()
    {
        $output = $this->render(
            '{{ partial:statamic-toc::starter-kit }}',
            ['article' => $this->article()]
        );

        // four headings in the fixture, all within the default depth
        $this->assertSame(4, substr_count($output, '<a href="#'));
        $this->assertStringContainsString('4 ', $output);
    }

    public function test_anchors_match_the_ids_the_modifier_injects()
    {
        $output = $this->render(
            '{{ partial:statamic-toc::starter-kit }}{{ article | toc }}',
            ['article' => $this->article()]
        );

        preg_match_all('/href="#([^"]+)"/', $output, $anchors);
        preg_match_all('/<h[1-6] id="([^"]+)"/', $output, $ids);

        $this->assertNotEmpty($anchors[1]);
        $this->assertEqualsCanonicalizing($anchors[1], $ids[1]);
    }
}
