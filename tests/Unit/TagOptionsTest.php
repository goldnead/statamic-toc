<?php

namespace Goldnead\StatamicToc\Tests\Unit;

use Goldnead\StatamicToc\ServiceProvider;
use Goldnead\StatamicToc\Tests\Concerns\RendersAntlers;
use Statamic\Testing\AddonTestCase;

class TagOptionsTest extends AddonTestCase
{
    use RendersAntlers;

    protected string $addonServiceProvider = ServiceProvider::class;

    private const DOCUMENT = '<h1>Eins</h1><h2>Zwei</h2><h3>Drei</h3><h4>Vier</h4><h5>Fünf</h5><h6>Sechs</h6>';

    /**
     * The document under test lives in `body`, and the trailing newline the
     * template leaves behind is noise for every assertion here.
     */
    private function renderPage(string $template, array $context = []): string
    {
        return trim($this->render($template, $context + ['body' => self::DOCUMENT]));
    }

    private function titles(string $params = ''): array
    {
        $out = $this->renderPage('{{ toc :content="body" '.$params.' }}[{{ toc_title }}]{{ if children }}{{ *recursive children* }}{{ /if }}{{ /toc }}');

        preg_match_all('/\[([^\]]+)\]/', $out, $m);

        return $m[1];
    }

    public function test_count_matches_the_list()
    {
        // It used to force the depth to 6 and report six while the list showed
        // three, which is the inconsistency the README had to apologise for.
        $this->assertSame(['Eins', 'Zwei', 'Drei'], $this->titles());
        $this->assertSame('3', $this->renderPage('{{ toc:count :content="body" }}'));
    }

    public function test_count_follows_the_same_parameters_as_the_list()
    {
        $this->assertSame(['Zwei', 'Drei', 'Vier'], $this->titles('from="h2"'));
        $this->assertSame('3', $this->renderPage('{{ toc:count :content="body" from="h2" }}'));

        $this->assertSame(['Eins', 'Zwei', 'Drei', 'Vier', 'Fünf', 'Sechs'], $this->titles('depth="6"'));
        $this->assertSame('6', $this->renderPage('{{ toc:count :content="body" depth="6" }}'));
    }

    public function test_count_respects_exclude()
    {
        $this->assertSame(['Eins', 'Drei'], $this->titles('exclude="Zwei"'));
        $this->assertSame('2', $this->renderPage('{{ toc:count :content="body" exclude="Zwei" }}'));
    }

    public function test_the_to_parameter_sets_an_absolute_level()
    {
        $this->assertSame(['Eins', 'Zwei', 'Drei', 'Vier', 'Fünf'], $this->titles('to="h5"'));
    }

    public function test_to_wins_over_depth()
    {
        $this->assertSame(['Zwei', 'Drei'], $this->titles('from="h2" depth="6" to="h3"'));
    }

    public function test_from_and_depth_still_work_together()
    {
        $this->assertSame(['Zwei', 'Drei', 'Vier'], $this->titles('from="h2" depth="3"'));
    }

    public function test_when_false_still_yields_an_empty_list_and_a_zero_count()
    {
        $this->assertSame([], $this->titles('when="false"'));
        $this->assertSame('0', $this->renderPage('{{ toc:count :content="body" when="false" }}'));
    }

    public function test_a_missing_field_yields_a_zero_count()
    {
        $this->assertSame('0', $this->renderPage('{{ toc:count field="gibt_es_nicht" }}'));
    }
}
