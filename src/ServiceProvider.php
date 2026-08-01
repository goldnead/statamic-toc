<?php

namespace Goldnead\StatamicToc;

use Goldnead\StatamicToc\Modifiers\Toc as TocModifier;
use Goldnead\StatamicToc\Tags\Toc as TocTag;
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

    public function bootAddon()
    {
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
