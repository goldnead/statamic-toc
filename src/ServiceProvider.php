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

    public function boot()
    {
        parent::boot();
    }

    public function register()
    {
        parent::register();
    }
}
