<?php

namespace Goldnead\StatamicToc\Tests\Unit;

use Goldnead\StatamicToc\Registry;
use Goldnead\StatamicToc\ServiceProvider;
use Statamic\Testing\AddonTestCase;

/**
 * The registry caches anchors per document. It used to be a plain singleton,
 * and nobody ever called flush(): under Octane or a long-running queue worker
 * the cache outlived the request and only ever grew.
 */
class RegistryScopeTest extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;

    public function test_the_registry_is_dropped_when_the_request_scope_ends(): void
    {
        $first = app(Registry::class);

        // Same request: same instance, so tag and modifier share the anchors.
        $this->assertSame($first, app(Registry::class));

        // What Octane and the queue worker do between requests and jobs.
        app()->forgetScopedInstances();

        $this->assertNotSame($first, app(Registry::class));
    }
}
