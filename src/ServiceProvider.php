<?php

namespace Goldnead\StatamicToc;

use Statamic\Providers\AddonServiceProvider;
use Goldnead\StatamicToc\Tags\Toc as TocTag;
use Goldnead\StatamicToc\Modifiers\Toc as TocModifier;

class ServiceProvider extends AddonServiceProvider
{
    protected $tags = [
        TocTag::class,
    ];

    protected $modifiers = [
        TocModifier::class,
    ];

    protected $viewNamespace = 'statamic-toc';

    public function register()
    {
        parent::register();

        // One registry per request, so the tag and the modifier land on the
        // same anchors for the same document.
        $this->app->singleton(Registry::class);
    }

    public function bootAddon()
    {
        // Registered explicitly instead of relying on the automatic boot, which
        // resolves the addon directory through the manifest and comes up empty
        // in package test suites.
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'statamic-toc');

        // The parent's $publishables maps into public_path(), which is meant for
        // assets. Views belong in the project's view folder.
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/statamic-toc'),
        ], 'statamic-toc-views');
    }
}
