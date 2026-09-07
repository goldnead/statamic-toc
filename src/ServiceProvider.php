<?php

namespace Goldnead\StatamicToc;

use Goldnead\BrandContext\Settings\SettingsRegistry;
use Goldnead\StatamicToc\Modifiers\Toc as TocModifier;
use Goldnead\StatamicToc\Support\Settings;
use Goldnead\StatamicToc\Tags\Toc as TocTag;
use Statamic\Facades\Permission;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $tags = [
        TocTag::class,
    ];

    protected $modifiers = [
        TocModifier::class,
    ];

    /**
     * The parent registers the view namespace off this property. No explicit
     * loadViewsFrom() is needed alongside it, and adding one registers the same
     * namespace twice.
     */
    protected $viewNamespace = 'statamic-toc';

    /**
     * The parent boots config off the addon directory, which is resolved through
     * the manifest and comes up empty in package test suites. Config is merged
     * explicitly in register() with an absolute path instead.
     */
    protected $config = false;

    public function register()
    {
        parent::register();

        // One registry per request, so the tag and the modifier land on the
        // same anchors for the same document.
        $this->app->singleton(Registry::class);

        $this->mergeConfigFrom(__DIR__.'/../config/statamic-toc.php', 'statamic-toc');
    }

    public function boot()
    {
        parent::boot();

        $this->bootSettings();
    }

    /**
     * Die Vorgaben dieses Addons bei der gemeinsamen Einstellungs-Schicht
     * anmelden, wenn es sie auf dieser Installation gibt.
     *
     * `statamic-brand-context` steht bewusst nicht in `require`: es verlangt
     * Statamic 6, dieses Addon läuft auch auf Statamic 5. Ein hartes require
     * hätte Statamic 5 stillschweigend fallen gelassen — ein Käufer bekäme
     * beim nächsten `composer update` einen Auflösungsfehler statt eines
     * Hinweises. Ohne den Nachbarn bleibt alles wie bisher: die Werte stehen
     * in `config/statamic-toc.php`.
     *
     * In `boot()`, nicht in `bootAddon()`: brand-context schiebt die
     * gespeicherten Überschreibungen aus einem `app->booted()`-Callback auf die
     * Config, und `bootAddon()` läuft selbst aus einem solchen Callback. Welcher
     * von beiden zuerst feuert, hängt an der Ladereihenfolge der Pakete.
     */
    protected function bootSettings(): void
    {
        if (! class_exists(SettingsRegistry::class)) {
            return;
        }

        $this->app->make(SettingsRegistry::class)->register(Settings::class);

        // Die Schicht fragt nur, welches Recht zu prüfen ist; angelegt wird es
        // hier. Ohne diese Zeilen ist der Abschnitt nur für Super-Nutzer
        // erreichbar und in der Rechteverwaltung nicht ankreuzbar.
        Permission::extend(function () {
            Permission::register('manage toc settings')
                ->label(__('statamic-toc::settings.permission'));
        });
    }

    public function bootAddon()
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'statamic-toc');

        $this->publishes([
            __DIR__.'/../config/statamic-toc.php' => config_path('statamic-toc.php'),
        ], 'statamic-toc-config');

        // The parent's $publishables maps into public_path(), which is meant for
        // assets. Views belong in the project's view folder.
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/statamic-toc'),
        ], 'statamic-toc-views');
    }
}
