<?php

namespace Goldnead\StatamicToc\Tests\Unit;

use Goldnead\StatamicToc\Anchors\IdInjector;
use Goldnead\StatamicToc\Anchors\Slugger;
use Goldnead\StatamicToc\Extractors\BardExtractor;
use Goldnead\StatamicToc\Extractors\HtmlExtractor;
use Goldnead\StatamicToc\Heading;
use Goldnead\StatamicToc\Registry;
use Goldnead\StatamicToc\ServiceProvider;
use Illuminate\Support\Facades\Log;
use Statamic\Testing\AddonTestCase;

class AnchorsTest extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;

    // ---------------------------------------------------------------- Slugger

    public function test_slugs_are_unique_within_a_document()
    {
        $anchors = (new Slugger)->assign([
            new Heading('Atem', 2),
            new Heading('Atem', 2),
            new Heading('Atem', 3),
        ]);

        $this->assertSame(['atem', 'atem-2', 'atem-3'], $anchors);
    }

    public function test_an_existing_id_wins_over_a_generated_slug()
    {
        $anchors = (new Slugger)->assign([
            new Heading('Atem und Stütze', 2, 'atemstuetze'),
            new Heading('Resonanz', 2),
        ]);

        $this->assertSame(['atemstuetze', 'resonanz'], $anchors);
    }

    public function test_a_generated_slug_never_collides_with_an_existing_id()
    {
        // The manual anchor sits behind the heading that would generate the
        // same slug. Claiming ids up front is what keeps them apart.
        $anchors = (new Slugger)->assign([
            new Heading('Atem', 2),
            new Heading('Etwas anderes', 2, 'atem'),
        ]);

        $this->assertSame(['atem-2', 'atem'], $anchors);
    }

    public function test_an_empty_heading_gets_no_anchor()
    {
        $anchors = (new Slugger)->assign([
            new Heading('', 2),
            new Heading('Atem', 2),
        ]);

        $this->assertSame([null, 'atem'], $anchors);
    }

    public function test_a_title_that_slugifies_to_nothing_still_gets_an_anchor()
    {
        $anchors = (new Slugger)->assign([
            new Heading('***', 2),
            new Heading('###', 2),
        ]);

        $this->assertSame(['section', 'section-2'], $anchors);
    }

    // ------------------------------------------------------------- IdInjector

    public function test_injects_into_every_level()
    {
        $html = '<h1>A</h1><h4>B</h4><h6>C</h6>';
        $anchors = ['a', 'b', 'c'];

        $out = (new IdInjector)->inject($html, $anchors);

        $this->assertSame('<h1 id="a">A</h1><h4 id="b">B</h4><h6 id="c">C</h6>', $out);
    }

    public function test_leaves_an_existing_id_alone_wherever_it_sits()
    {
        // The old guard required whitespace or '>' behind the closing quote, so
        // an id as the last attribute went undetected and a second was added.
        $cases = [
            '<h2 id="a">X</h2>',
            '<h2 id="a" class="b">X</h2>',
            '<h2 class="b" id="a">X</h2>',
            "<h2 id='a'>X</h2>",
        ];

        foreach ($cases as $html) {
            $this->assertSame($html, (new IdInjector)->inject($html, ['x']), $html);
        }
    }

    public function test_a_data_attribute_ending_in_id_is_not_an_id()
    {
        // data-id, data-heading-id and hx-id contain "id=" but are not the id
        // attribute, so the heading still needs one.
        $cases = [
            '<h2 data-id="x">Titel</h2>' => '<h2 data-id="x" id="titel">Titel</h2>',
            '<h2 data-heading-id="x">Titel</h2>' => '<h2 data-heading-id="x" id="titel">Titel</h2>',
            '<h2 hx-id="x">Titel</h2>' => '<h2 hx-id="x" id="titel">Titel</h2>',
        ];

        foreach ($cases as $html => $expected) {
            $this->assertSame($expected, (new IdInjector)->inject($html, ['titel']), $html);
        }
    }

    public function test_an_explicit_id_is_kept_when_other_attributes_mention_id()
    {
        $html = '<h2 data-id="x" id="mein-anker">Titel</h2>';

        $this->assertSame($html, (new IdInjector)->inject($html, ['titel']));
    }

    public function test_keeps_the_rest_of_the_markup_byte_for_byte()
    {
        $html = '<h2 class="mt-4" data-x="a>b">Titel</h2><p>Text mit <em>Betonung</em> &amp; Zeichen.</p><br>';

        $out = (new IdInjector)->inject($html, ['titel']);

        $this->assertStringContainsString('<p>Text mit <em>Betonung</em> &amp; Zeichen.</p><br>', $out);
        $this->assertStringContainsString('id="titel"', $out);
    }

    public function test_adds_extra_attributes_with_the_id_placeholder()
    {
        $out = (new IdInjector)->inject('<h2>Titel</h2>', ['titel'], 'x-on:click="go(\'[id]\')"');

        $this->assertSame('<h2 id="titel" x-on:click="go(\'titel\')">Titel</h2>', $out);
    }

    public function test_an_empty_id_is_replaced_not_duplicated()
    {
        $this->assertSame(
            '<h2 id="titel">Titel</h2>',
            (new IdInjector)->inject('<h2 id="">Titel</h2>', ['titel'])
        );
    }

    public function test_a_heading_inside_a_comment_or_script_does_not_shift_the_anchors()
    {
        // The extractor sees neither: a comment and the JSON-LD in a script are
        // no headings. Counting them in the injector gave every heading after
        // them the anchor of its neighbour.
        $html = '<!-- <h2>Alt</h2> --><script type="application/ld+json">{"x":"<h2>Nein</h2>"}</script>'
            .'<style>/* <h3>x</h3> */</style><h2>Eins</h2><h2>Zwei</h2>';

        $out = (new IdInjector)->inject($html, ['eins', 'zwei']);

        $this->assertStringContainsString('<h2 id="eins">Eins</h2>', $out);
        $this->assertStringContainsString('<h2 id="zwei">Zwei</h2>', $out);
        $this->assertStringContainsString('<!-- <h2>Alt</h2> -->', $out);
        $this->assertStringContainsString('{"x":"<h2>Nein</h2>"}', $out);

        // And the extractor agrees on the count, which is the whole contract.
        $this->assertCount(2, (new HtmlExtractor)->extract($html));
    }

    public function test_a_count_mismatch_between_headings_and_anchors_is_logged()
    {
        Log::spy();

        (new IdInjector)->inject('<h2>Eins</h2><h2>Zwei</h2>', ['eins']);

        Log::shouldHaveReceived('warning')
            ->with('statamic-toc: heading count differs from anchor count', \Mockery::type('array'))
            ->once();
    }

    public function test_a_heading_without_an_anchor_is_left_alone()
    {
        $this->assertSame('<h2></h2>', (new IdInjector)->inject('<h2></h2>', [null]));
    }

    public function test_a_pcre_failure_is_logged_and_the_html_comes_back_unchanged()
    {
        Log::spy();

        // A long run of quoted values inside one tag exhausts the PCRE JIT
        // stack, so preg_replace_callback returns null. Changing pcre.jit or
        // pcre.backtrack_limit at runtime does not reliably trigger it, because
        // the compiled pattern is cached per process.
        $html = '<h2 class="'.str_repeat('"a" ', 100000).'">Titel</h2>';

        $out = (new IdInjector)->inject($html, ['titel']);

        $this->assertSame($html, $out);
        Log::shouldHaveReceived('warning')
            ->with('statamic-toc: id injection failed', \Mockery::on(
                fn ($context) => isset($context['error']) && $context['error'] !== ''
            ))
            ->once();
    }

    // ----------------------------------------------------------------- Shared

    public function test_bard_and_its_rendered_html_get_the_same_anchors()
    {
        // The real page: the tag reads the Bard array, the modifier gets the
        // rendered HTML of the same field. Two very different strings, one
        // sequence of headings. Hashing the content instead of the headings
        // would hand out two different sets of anchors here.
        $bard = [
            ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Atem']]],
            ['type' => 'heading', 'attrs' => ['level' => 3], 'content' => [['type' => 'text', 'text' => 'Übung']]],
            ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Atem']]],
        ];

        $html = '<h2>Atem</h2><p>Text.</p><h3>Übung</h3><h2>Atem</h2>';

        $registry = new Registry;

        $fromBard = $registry->anchorsFor((new BardExtractor)->extract($bard));
        $fromHtml = $registry->anchorsFor((new HtmlExtractor)->extract($html));

        $this->assertSame(['atem', 'ubung', 'atem-2'], $fromBard);
        $this->assertSame($fromBard, $fromHtml);
    }

    public function test_the_registry_decides_once_per_document()
    {
        $registry = new Registry;
        $headings = [new Heading('Atem', 2)];

        $first = $registry->anchorsFor($headings);
        $second = $registry->anchorsFor($headings);

        // Asking twice must not walk the counter forward. This is the defect
        // behind two modifier calls on one page renumbering each other.
        $this->assertSame(['atem'], $first);
        $this->assertSame($first, $second);
    }

    public function test_two_different_documents_do_not_share_a_counter()
    {
        $registry = new Registry;

        $one = $registry->anchorsFor([new Heading('Atem', 2)]);
        $two = $registry->anchorsFor([new Heading('Atem', 3)]);

        $this->assertSame(['atem'], $one);
        $this->assertSame(['atem'], $two);
    }
}
