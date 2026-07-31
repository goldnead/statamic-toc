<?php

namespace Tests\Unit;

use Goldnead\StatamicToc\Extractors\BardExtractor;
use Goldnead\StatamicToc\Extractors\Detector;
use Goldnead\StatamicToc\Extractors\HtmlExtractor;
use Goldnead\StatamicToc\Extractors\MarkdownExtractor;
use Goldnead\StatamicToc\Tests\TestCase;

class ExtractionTest extends TestCase
{
    private function titles(array $headings): array
    {
        return array_map(fn ($h) => $h->title, $headings);
    }

    private function levels(array $headings): array
    {
        return array_map(fn ($h) => $h->level, $headings);
    }

    // ---------------------------------------------------------------- Detector

    public function test_detects_bard()
    {
        $this->assertTrue((new Detector)->isBard([['type' => 'heading']]));
        $this->assertFalse((new Detector)->isBard('<h1>A</h1>'));
    }

    public function test_detects_html_by_an_actual_heading_tag()
    {
        $d = new Detector;

        $this->assertTrue($d->isHtml('<h2>A</h2>'));
        $this->assertTrue($d->isHtml('<h2 class="x">A</h2>'));
        $this->assertFalse($d->isHtml('<p>A paragraph about <hr> and nothing else.</p>'));
    }

    public function test_a_hash_in_prose_is_not_markdown()
    {
        $d = new Detector;

        // The old check was Str::contains($content, '#'), which turned any hex
        // colour or hashtag into a markdown document.
        $this->assertFalse($d->isMarkdown('<p>Our brand colour is #ff0000.</p>'));
        $this->assertFalse($d->isMarkdown('Meet us at #chorfestival, it is worth it.'));
        $this->assertTrue($d->isMarkdown("# Einstieg\n\nText."));
        $this->assertTrue($d->isMarkdown("Einstieg\n========\n\nText."));
    }

    public function test_html_without_headings_is_not_markdown()
    {
        // It used to fall through to the markdown branch and get run through
        // CommonMark, which is not what anyone asked for.
        $this->assertFalse((new Detector)->isMarkdown('<p>Kein Zwischentitel.</p>'));
    }

    public function test_picks_the_matching_extractor()
    {
        $d = new Detector;

        $this->assertInstanceOf(BardExtractor::class, $d->for([['type' => 'heading']]));
        $this->assertInstanceOf(HtmlExtractor::class, $d->for('<h2>A</h2>'));
        $this->assertInstanceOf(MarkdownExtractor::class, $d->for("## A\n"));
    }

    // ------------------------------------------------------------------- HTML

    public function test_html_reads_every_level()
    {
        $headings = (new HtmlExtractor)->extract('<h1>A</h1><h4>B</h4><h6>C</h6>');

        $this->assertSame(['A', 'B', 'C'], $this->titles($headings));
        $this->assertSame([1, 4, 6], $this->levels($headings));
    }

    public function test_html_keeps_umlauts_intact()
    {
        // The old path went through mb_convert_encoding(..., 'HTML-ENTITIES'),
        // deprecated since PHP 8.2.
        $headings = (new HtmlExtractor)->extract('<h2>Größe und Stütze</h2><h2>Öffnung &amp; Weite</h2>');

        $this->assertSame(['Größe und Stütze', 'Öffnung & Weite'], $this->titles($headings));
    }

    public function test_html_flattens_inline_markup()
    {
        $headings = (new HtmlExtractor)->extract('<h2>Die <strong>Stütze</strong> im <em>Chor</em></h2>');

        $this->assertSame(['Die Stütze im Chor'], $this->titles($headings));
    }

    public function test_html_reports_an_existing_id()
    {
        $headings = (new HtmlExtractor)->extract('<h2 id="atemstuetze">Atem</h2><h2>Resonanz</h2>');

        $this->assertTrue($headings[0]->hasExplicitId());
        $this->assertSame('atemstuetze', $headings[0]->explicitId);
        $this->assertFalse($headings[1]->hasExplicitId());
    }

    public function test_html_survives_broken_markup()
    {
        $headings = (new HtmlExtractor)->extract('<h2>Offen<h3>Verschachtelt</h2></h3><p>');

        $this->assertNotEmpty($headings);
    }

    // ------------------------------------------------------------------- Bard

    public function test_bard_finds_headings_in_nested_sets()
    {
        $content = [
            ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Oben']]],
            [
                'type' => 'set',
                'attrs' => [
                    'values' => [
                        'type' => 'columns',
                        'left' => [
                            ['type' => 'heading', 'attrs' => ['level' => 3], 'content' => [['type' => 'text', 'text' => 'Links']]],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertSame(['Oben', 'Links'], $this->titles((new BardExtractor)->extract($content)));
    }

    public function test_bard_concatenates_inline_nodes()
    {
        // A heading that starts with a mark used to be dropped completely, and
        // one with a mark in the middle was cut short. That is issue #26.
        $content = [[
            'type' => 'heading',
            'attrs' => ['level' => 2],
            'content' => [
                ['type' => 'text', 'marks' => [['type' => 'bold']], 'text' => 'Stütze'],
                ['type' => 'text', 'text' => ' im Chor'],
            ],
        ]];

        $this->assertSame(['Stütze im Chor'], $this->titles((new BardExtractor)->extract($content)));
    }

    public function test_bard_reports_an_anchor_from_attrs()
    {
        $content = [[
            'type' => 'heading',
            'attrs' => ['level' => 2, 'id' => 'atemstuetze'],
            'content' => [['type' => 'text', 'text' => 'Atem']],
        ]];

        $this->assertSame('atemstuetze', (new BardExtractor)->extract($content)[0]->explicitId);
    }

    public function test_bard_skips_malformed_nodes_without_crashing()
    {
        $content = [
            ['type' => 'heading', 'content' => [['type' => 'text', 'text' => 'Ohne attrs']]],
            ['type' => 'heading', 'attrs' => ['level' => 2]],
            ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => 123],
            ['type' => 'heading', 'attrs' => ['level' => 'zwei'], 'content' => [['type' => 'text', 'text' => 'X']]],
            ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Heil']]],
        ];

        $headings = (new BardExtractor)->extract($content);

        $this->assertSame(['', '', 'Heil'], $this->titles($headings));
    }

    // --------------------------------------------------------------- Markdown

    public function test_markdown_reads_atx_and_setext()
    {
        $headings = (new MarkdownExtractor)->extract("# Einstieg\n\nText.\n\n## Atem\n\nUnterüberschrift\n----------------\n");

        $this->assertSame(['Einstieg', 'Atem', 'Unterüberschrift'], $this->titles($headings));
        $this->assertSame([1, 2, 2], $this->levels($headings));
    }

    public function test_markdown_ignores_a_hash_inside_a_code_block()
    {
        $headings = (new MarkdownExtractor)->extract("## Echt\n\n```\n# nur ein Kommentar\n```\n");

        $this->assertSame(['Echt'], $this->titles($headings));
    }

    // ------------------------------------------------------- shared behaviour

    public function test_extractors_do_not_filter_by_level()
    {
        // Filtering belongs to the caller. The modifier needs every heading,
        // even the ones the list is not going to show.
        $this->assertCount(6, (new HtmlExtractor)->extract('<h1>1</h1><h2>2</h2><h3>3</h3><h4>4</h4><h5>5</h5><h6>6</h6>'));
    }

    public function test_empty_input_yields_nothing()
    {
        $this->assertSame([], (new HtmlExtractor)->extract(''));
        $this->assertSame([], (new HtmlExtractor)->extract(null));
        $this->assertSame([], (new BardExtractor)->extract('not an array'));
        $this->assertSame([], (new MarkdownExtractor)->extract('   '));
    }
}
