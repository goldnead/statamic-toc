<?php

namespace Goldnead\StatamicToc\Tests\Unit;

use Goldnead\StatamicToc\Parser;
use Goldnead\StatamicToc\ServiceProvider;
use Statamic\Facades\Antlers;
use Statamic\Testing\AddonTestCase;

/**
 * The contract this addon lives or dies by:
 *
 *   every anchor the {{ toc }} tag renders points at an id the {{ | toc }}
 *   modifier actually injected, and every heading the modifier touches is
 *   reachable.
 *
 * Nothing enforced it so far. Tag and modifier each run their own Parser with
 * their own slug registry and agree only by coincidence, kept apart by the
 * '-list' and '-text' suffixes in Parser::generateId(). Where the coincidence
 * breaks, the table of contents links into the void.
 *
 * These tests describe the target state. They are expected to fail until the
 * v2 rework puts both sides on one shared registry.
 */
class AnchorContractTest extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;

    /**
     * Renders a template the way a real page does. The third argument marks it
     * as trusted; without it Antlers treats the string as user data and
     * silently skips every tag.
     */
    private function render(string $template, array $context = []): string
    {
        return (string) Antlers::parse($template, $context, true);
    }

    /**
     * The anchors the list links to, in order.
     */
    private function anchors(string $html): array
    {
        preg_match_all('/href="#([^"]+)"/', $html, $m);

        return $m[1];
    }

    /**
     * The ids that actually exist on headings in the rendered content.
     *
     * Takes the first id per heading, the way an HTML parser does: when an
     * element carries the same attribute twice, the first one wins and the rest
     * are dropped. Matching the last one would paper over exactly the defect
     * test_no_heading_carries_two_ids is about.
     */
    private function ids(string $html): array
    {
        preg_match_all('/<h[1-6]\b[^>]*>/i', $html, $tags);

        return collect($tags[0])
            ->map(fn ($tag) => preg_match('/\bid="([^"]*)"/', $tag, $m) ? $m[1] : null)
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Half the contract, and the half that always holds: no anchor may point at
     * a heading that does not carry that id.
     */
    private function assertNoDanglingAnchors(string $html, string $because)
    {
        $anchors = $this->anchors($html);

        $this->assertNotEmpty($anchors, 'The list rendered no anchors at all.');

        $dangling = array_values(array_diff($anchors, $this->ids($html)));
        $this->assertSame([], $dangling, $because."\nAnchors without a matching heading id: ".implode(', ', $dangling));
    }

    /**
     * The other half, and only for documents the list covers completely. A
     * heading outside `from`/`depth` still gets an id, and nothing links to it.
     * That is what the caller asked for, not a defect.
     */
    private function assertAnchorsResolve(string $html, string $because)
    {
        $this->assertNoDanglingAnchors($html, $because);

        $unreachable = array_values(array_diff($this->ids($html), $this->anchors($html)));
        $this->assertSame([], $unreachable, $because."\nHeadings the list cannot reach: ".implode(', ', $unreachable));
    }

    /**
     * A page as a customer builds it: the list, then the content itself run
     * through the modifier.
     */
    private const PAGE = '{{ toc :content="body" :from="from" :depth="depth" }}<a href="#{{ toc_id }}">{{ toc_title }}</a>{{ if children }}{{ *recursive children* }}{{ /if }}{{ /toc }}{{ body | toc }}';

    private function page(string $body, string $from = 'h1', int $depth = 3): string
    {
        return $this->render(self::PAGE, compact('body', 'from', 'depth'));
    }

    public function test_the_simple_case_holds()
    {
        // The baseline. If this ever fails, the test itself is wrong.
        $html = $this->page('<h1>Einstieg</h1><h2>Atem</h2><h3>Stütze</h3>');

        $this->assertAnchorsResolve($html, 'Unique titles within the default depth must line up.');
    }

    public function test_duplicate_titles_line_up()
    {
        // Two headings with the same text. Both sides have to number them the
        // same way, or the second link goes to the first heading.
        $html = $this->page('<h2>Atem</h2><h3>Übung</h3><h2>Resonanz</h2><h3>Übung</h3>');

        $this->assertAnchorsResolve($html, 'Repeated headings must get the same suffix on both sides.');
    }

    public function test_a_duplicate_above_the_starting_level_does_not_shift_the_numbering()
    {
        // With from="h2" the tag never sees the h1, but the modifier injects
        // into it and counts it. That used to make the two sides disagree about
        // the second "Atem".
        //
        // The h1 keeps an id nobody links to, which is exactly what from="h2"
        // asks for, so only the dangling direction is checked here.
        $html = $this->page('<h1>Atem</h1><h2>Atem</h2><h2>Resonanz</h2>', 'h2');

        $this->assertNoDanglingAnchors($html, 'A skipped heading above `from` must not shift the anchors below it.');
        $this->assertSame(['atem-2', 'resonanz'], $this->anchors($html), 'The list must link the two h2 headings.');
        $this->assertSame(['atem', 'atem-2', 'resonanz'], $this->ids($html), 'Every heading still gets an id.');
    }

    public function test_headings_below_the_third_level_get_ids()
    {
        // from="h2" with depth 3 lists h2 to h4. The modifier builds its regex
        // from a Parser that still sits at the default maxLevel of 3.
        $html = $this->page('<h2>Atem</h2><h3>Stütze</h3><h4>Zwerchfell</h4>', 'h2');

        $this->assertAnchorsResolve($html, 'Every level the list shows must also be injected.');
    }

    public function test_a_manual_anchor_wins_on_both_sides()
    {
        // Bard's anchor button and save_html both produce headings that already
        // carry an id. That id is the anchor, and the list has to use it.
        $html = $this->page('<h2 id="atemstuetze">Atem und Stütze</h2><h2>Resonanz</h2>');

        $this->assertAnchorsResolve($html, 'A hand-written id is the anchor; the list must not slugify past it.');
    }

    public function test_no_heading_carries_two_ids()
    {
        // The guard in Parser::injectIds requires whitespace or '>' behind the
        // closing quote, which never follows the last attribute. So an existing
        // id is not detected and a second one is appended. The result is invalid
        // HTML, and the browser keeps the first id, not the one the list links to.
        $html = $this->page('<h2 id="atemstuetze">Atem und Stütze</h2>');

        preg_match_all('/<h[1-6]\b[^>]*>/i', $html, $tags);

        foreach ($tags[0] as $tag) {
            $this->assertLessThan(
                2,
                preg_match_all('/\bid="/', $tag),
                'A heading must not end up with two id attributes: '.$tag
            );
        }
    }

    public function test_two_fields_on_one_page_stay_independent()
    {
        // The modifier goes through a facade, and the facade caches its Parser
        // for the whole request. The second call inherits the first registry.
        $html = $this->render(
            '{{ toc :content="one" }}<a href="#{{ toc_id }}">{{ toc_title }}</a>{{ /toc }}{{ one | toc }}'
            .'{{ toc :content="two" }}<a href="#{{ toc_id }}">{{ toc_title }}</a>{{ /toc }}{{ two | toc }}',
            ['one' => '<h2>Atem</h2>', 'two' => '<h2>Atem</h2>']
        );

        // Same title in both fields, so both sides must produce the same two
        // ids in the same order.
        $this->assertSame(
            $this->anchors($html),
            $this->ids($html),
            'Two modifier calls in one request must not renumber each other.'
        );
    }

    public function test_special_characters_survive_both_paths()
    {
        $html = $this->page('<h2>Öffnung &amp; Weite</h2><h3>Größe</h3>');

        $this->assertAnchorsResolve($html, 'Umlauts and entities must slugify identically on both sides.');
    }

    public function test_formatted_headings_line_up()
    {
        $html = $this->page('<h2>Die <strong>Stütze</strong> im Chor</h2><h3>Übung</h3>');

        $this->assertAnchorsResolve($html, 'Inline markup must not change the slug on one side only.');
    }

    public function test_excluding_a_heading_does_not_shift_the_anchors_of_the_others()
    {
        $html = '<h2>Intro</h2><h2>Skip me</h2><h2>Intro</h2>';

        $withEverything = $this->listFor($html);
        $withExclusion = $this->listFor($html, fn ($p) => $p->exclude('Skip me'));

        // A heading the list does not show still exists in the document, so it
        // still owns its slug. Excluding it must not renumber the duplicate
        // below it, or the anchors move for a parameter about visibility.
        $this->assertSame('intro', $withEverything[0]['toc_id']);
        $this->assertSame('intro-2', $withEverything[2]['toc_id']);

        $this->assertSame('intro', $withExclusion[0]['toc_id']);
        $this->assertSame('intro-2', $withExclusion[1]['toc_id']);
    }

    private function listFor(string $html, ?callable $configure = null): array
    {
        $parser = (new Parser)->make($html)->depth(6)->flatten();

        if ($configure) {
            $configure($parser);
        }

        return array_values(array_filter(
            $parser->build(),
            fn ($row) => is_array($row) && isset($row['toc_id']),
        ));
    }
}
