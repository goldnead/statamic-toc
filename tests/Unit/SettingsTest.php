<?php

namespace Goldnead\StatamicToc\Tests\Unit;

use Goldnead\BrandContext\Facades\BrandSettings;
use Goldnead\BrandContext\Settings\SettingsRegistry;
use Goldnead\StatamicToc\ServiceProvider;
use Goldnead\StatamicToc\Support\Settings;
use Goldnead\StatamicToc\Tests\Concerns\RendersAntlers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Statamic\Testing\AddonTestCase;

/**
 * Die Einstellungen dieses Addons, sobald `statamic-brand-context` daneben
 * liegt.
 *
 * Die Grenze, die hier überschritten wird, ist die ganze: von der
 * gespeicherten Überschreibung bis zu dem, was `{{ toc }}` auf der Seite
 * ausgibt. Eine Zusicherung auf `config()` allein bliebe auch dann grün, wenn
 * das Tag seine Vorgabe woanders herholt.
 *
 * Auf Statamic 5 gibt es den Nachbarn nicht, dieser Fall wird dort
 * übersprungen — sichtbar und mit Grund, nicht still.
 */
class SettingsTest extends AddonTestCase
{
    use RefreshDatabase;
    use RendersAntlers;

    protected string $addonServiceProvider = ServiceProvider::class;

    private const DOCUMENT = '<h1>Eins</h1><h2>Zwei</h2><h3>Drei</h3><h4>Vier</h4>';

    protected function setUp(): void
    {
        if (! class_exists(SettingsRegistry::class)) {
            $this->markTestSkipped('statamic-brand-context ist nicht installiert; ohne die Schicht gibt es keinen Einstellungsbildschirm.');
        }

        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/../../vendor/goldnead/statamic-brand-context/database/migrations');
    }

    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            \Goldnead\BrandContext\ServiceProvider::class,
        ]);
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    private function titles(array $context = []): array
    {
        $out = $this->render(
            '{{ toc }}[{{ toc_title }}]{{ if children }}{{ *recursive children* }}{{ /if }}{{ /toc }}',
            $context + ['article' => self::DOCUMENT]
        );

        preg_match_all('/\[([^\]]+)\]/', $out, $m);

        return $m[1];
    }

    public function test_the_addon_announces_itself_to_the_shared_registry()
    {
        // Das Einzige, was keine andere Zusicherung hier fängt: ein erfüllter
        // Vertrag, der nie angemeldet wird, ist ein Bildschirm ohne
        // Inhaltsverzeichnis-Abschnitt.
        $registry = app(SettingsRegistry::class);

        $this->assertTrue($registry->has('toc'));
        $this->assertSame(Settings::class, $registry->provider('toc'));
        // Der Namensraum heißt `toc`, die Config-Wurzel `statamic-toc`.
        // Vertauscht landeten die Überschreibungen auf einer Wurzel, die
        // niemand liest.
        $this->assertSame('statamic-toc', $registry->configPath('toc'));
        $this->assertSame('manage toc settings', $registry->permission('toc'));
    }

    public function test_a_saved_field_is_the_field_the_tag_reads()
    {
        BrandSettings::for('toc')->save(['field' => 'body']);

        $this->assertSame(
            ['Eins'],
            $this->titles(['body' => '<h1>Eins</h1>', 'article' => '<h1>Falsches Feld</h1>'])
        );
    }

    public function test_a_saved_range_reaches_the_rendered_list()
    {
        BrandSettings::for('toc')->save(['from' => 'h2', 'depth' => 2]);

        $this->assertSame(['Zwei', 'Drei'], $this->titles());
    }

    public function test_a_saved_last_level_wins_over_the_number_of_levels()
    {
        // Dieselbe Rangfolge wie in der Config, nur von der anderen Seite: `to`
        // ist absolut, `depth` relativ. Ein Wert, der nur in der Datei
        // gewinnt, aber nicht auf dem Bildschirm, wäre eine Lüge im Formular.
        BrandSettings::for('toc')->save(['depth' => 1, 'to' => 'h3']);

        $this->assertSame(['Eins', 'Zwei', 'Drei'], $this->titles());
    }

    public function test_a_value_set_back_to_the_default_stops_being_stored()
    {
        BrandSettings::for('toc')->save(['field' => 'body']);
        $this->assertSame('body', config('statamic-toc.field'));

        // Nicht „speichert article", sondern speichert nichts. Eine Zeile, die
        // einen Wert auf seine eigene Vorgabe festnagelt, friert ihn gegen
        // jedes künftige Paket-Update ein.
        BrandSettings::for('toc')->save(['field' => 'article']);

        $this->assertSame([], BrandSettings::for('toc')->overrides());
        $this->assertSame('article', config('statamic-toc.field'));
    }
}
