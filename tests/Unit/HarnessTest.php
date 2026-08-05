<?php

namespace Goldnead\StatamicToc\Tests\Unit;

use Goldnead\StatamicToc\ServiceProvider;
use Goldnead\StatamicToc\Tests\Concerns\RendersAntlers;
use Statamic\Facades\Antlers;
use Statamic\Testing\AddonTestCase;

/**
 * The test harness itself, pinned down.
 *
 * Until this suite moved to Statamic's AddonTestCase it ran against a
 * hand-rolled base class whose manifest fixture had no 'autoload' and no
 * 'provider' key. AddonServiceProvider::boot() bailed out on that, so the view
 * namespace was never registered, {{ toc }} rendered an empty string and
 * {{ | toc }} threw ModifierNotFoundException. A tag test written on that base
 * class asserts against '' and looks green.
 *
 * These four assertions are the guard: if the harness ever stops booting the
 * addon, this fails first and by name, instead of the next tag test failing for
 * a reason nobody can see.
 */
class HarnessTest extends AddonTestCase
{
    use RendersAntlers;

    protected string $addonServiceProvider = ServiceProvider::class;

    public function test_the_addon_boots_and_registers_its_views()
    {
        $this->assertTrue(
            view()->exists('statamic-toc::starter-kit'),
            'The view namespace is registered in boot(). If this is false the addon did not boot.'
        );
    }

    public function test_the_tag_is_registered()
    {
        $this->assertSame(
            '[A]',
            $this->render('{{ toc :content="body" }}[{{ toc_title }}]{{ /toc }}', ['body' => '<h1>A</h1>']),
            'An unregistered tag renders an empty string without raising anything.'
        );
    }

    public function test_the_modifier_is_registered()
    {
        $this->assertSame(
            '<h1 id="a">A</h1>',
            $this->render('{{ body | toc }}', ['body' => '<h1>A</h1>']),
        );
    }

    public function test_an_untrusted_template_skips_every_tag()
    {
        // The other half of the trap, and the reason render() exists: a booted
        // addon still renders nothing when the template is not marked trusted.
        $this->assertSame(
            '',
            (string) Antlers::parse('{{ toc :content="body" }}[{{ toc_title }}]{{ /toc }}', ['body' => '<h1>A</h1>']),
            'Antlers skips tags in untrusted templates. Tests must go through render().'
        );
    }
}
