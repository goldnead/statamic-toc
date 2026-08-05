<?php

namespace Goldnead\StatamicToc\Tests\Unit;

use Goldnead\StatamicToc\Facades\ParserFacade;
use Goldnead\StatamicToc\Modifiers\Toc as TocModifier;
use Goldnead\StatamicToc\Parser;
use Goldnead\StatamicToc\ServiceProvider;
use Statamic\Fields\Value;
use Statamic\Testing\AddonTestCase;

/**
 * The three ways an anchor could end up pointing at nothing.
 *
 * All three are the same failure from the reader's side: they click an entry in
 * the list and the page does not move. They have three different causes, so
 * they get three groups of tests.
 */
class AnchorIntegrityTest extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;

    private function modify(string $html, $params = null): string
    {
        return resolve(TocModifier::class)->index(new Value($html), $params ?? []);
    }

    // ---------------------------------------------------------------- depth

    public function test_it_adds_ids_below_the_third_level()
    {
        $html = '<h1>A</h1><h2>B</h2><h3>C</h3><h4>D</h4><h5>E</h5><h6>F</h6>';

        $output = $this->modify($html);

        // The id injection is not the list. A heading that is not in the list is
        // harmless with an id on it, while a heading in the list without one is
        // a dead link.
        foreach (['a', 'b', 'c', 'd', 'e', 'f'] as $id) {
            $this->assertStringContainsString('id="'.$id.'"', $output);
        }
    }

    public function test_a_list_that_goes_six_deep_finds_every_anchor_it_links_to()
    {
        $html = '<h1>A</h1><h2>B</h2><h3>C</h3><h4>D</h4><h5>E</h5><h6>F</h6>';

        $list = (new Parser)->make($html)->depth(6)->build();
        $output = $this->modify($html);

        $this->assertNotEmpty($list);
        $this->walk($list, function (array $heading) use ($output) {
            $this->assertStringContainsString(
                'id="'.$heading['toc_id'].'"',
                $output,
                "The list links to #{$heading['toc_id']} and no element carries that id.",
            );
        });
    }

    // ------------------------------------------------------- shared state

    public function test_the_tags_depth_does_not_narrow_the_modifier()
    {
        $html = '<h1>A</h1><h2>B</h2><h3>C</h3>';

        // Rendering the list first is the ordinary case: the template puts the
        // table of contents above the article it belongs to.
        ParserFacade::make($html)->depth(1)->build();

        $output = $this->modify($html);

        // Before 1.10 the tag and the modifier shared one parser instance for
        // the whole request, so `depth="1"` above silently removed the ids from
        // every h2 and h3 below it.
        $this->assertStringContainsString('id="b"', $output);
        $this->assertStringContainsString('id="c"', $output);
    }

    public function test_two_lists_with_different_depths_do_not_affect_each_other()
    {
        $html = '<h1>A</h1><h2>B</h2><h3>C</h3>';

        $shallow = ParserFacade::make($html)->depth(1)->build();
        $deep = ParserFacade::make($html)->depth(3)->build();

        $this->assertCount(1, $this->flatten($shallow));
        $this->assertCount(3, $this->flatten($deep));
    }

    // ------------------------------------------------------- existing ids

    public function test_a_heading_that_already_has_an_id_keeps_exactly_that_one()
    {
        $output = $this->modify('<h2 id="mine">Kept</h2>');

        $this->assertSame(1, substr_count($output, 'id='), $output);
        $this->assertStringContainsString('id="mine"', $output);
        $this->assertStringNotContainsString('id="kept"', $output);
    }

    public function test_it_finds_an_existing_id_in_any_attribute_position()
    {
        // The old check required a character after the closing quote, so an id
        // written last — which is where people write it — was never seen.
        $cases = [
            '<h2 id="last">T</h2>',
            '<h2 class="x" id="last">T</h2>',
            "<h2 class='x' id='last'>T</h2>",
            '<h2 id="last" class="x">T</h2>',
            '<h2 data-foo="bar" id="last" data-baz="qux">T</h2>',
        ];

        foreach ($cases as $html) {
            $output = $this->modify($html);
            $this->assertSame(1, substr_count($output, 'id='), "Doubled an id in: {$html}");
        }
    }

    public function test_the_list_links_to_the_hand_written_id_rather_than_a_slug_of_the_title()
    {
        $html = '<h2 id="mine">Kept</h2>';

        $list = $this->flatten((new Parser)->make($html)->depth(6)->build());
        $output = $this->modify($html);

        // Whichever of the two wins, they have to agree. An element carrying
        // `mine` and a list linking `#kept` is the broken anchor by another route.
        $this->assertSame($list[0]['toc_id'], 'mine');
        $this->assertStringContainsString('id="mine"', $output);
    }

    public function test_a_heading_whose_attrs_are_an_object_does_not_crash_the_id_lookup()
    {
        // The same malformed-Bard shape ParserSafetyTest guards `level` against.
        // Reading a hand-written id has to survive it too.
        $node = [
            'type' => 'heading',
            'attrs' => (object) ['level' => 2, 'id' => 'mine'],
            'content' => [['type' => 'text', 'text' => 'Kept']],
        ];

        $this->assertIsArray((new Parser)->make([$node])->build());
    }

    // ------------------------------------------------------------ helpers

    private function walk(array $headings, callable $fn): void
    {
        foreach ($headings as $heading) {
            $fn($heading);

            if (! empty($heading['children'])) {
                $this->walk($heading['children'], $fn);
            }
        }
    }

    private function flatten(array $headings): array
    {
        $flat = [];

        $this->walk($headings, function (array $heading) use (&$flat) {
            $flat[] = $heading;
        });

        return $flat;
    }
}
