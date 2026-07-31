<?php

namespace Goldnead\StatamicToc\Anchors;

use Goldnead\StatamicToc\Heading;
use Illuminate\Support\Str;

/**
 * Assigns one anchor per heading. The only place in the addon that decides what
 * a heading is called.
 */
class Slugger
{
    /**
     * Anchors for a whole document, aligned by index with the headings passed
     * in. Headings without a usable title get null, so the injector leaves them
     * alone rather than writing id="".
     *
     * @param  array<int, Heading>  $headings
     * @return array<int, string|null>
     */
    public function assign(array $headings): array
    {
        $taken = [];
        $anchors = [];

        // Ids that were already in the document are claimed first, so a
        // generated slug never collides with one of them, no matter where in
        // the document it sits.
        foreach ($headings as $heading) {
            if ($heading->hasExplicitId()) {
                $taken[$heading->explicitId] = true;
            }
        }

        foreach ($headings as $heading) {
            if ($heading->hasExplicitId()) {
                $anchors[] = $heading->explicitId;

                continue;
            }

            if ($heading->isEmpty()) {
                $anchors[] = null;

                continue;
            }

            $anchors[] = $this->unique(Str::slug($heading->title), $taken);
        }

        return $anchors;
    }

    /**
     * Keeps counting until the slug is free. Checks the suffixed candidate
     * against the same list, so an existing "atem-2" in the document cannot be
     * handed out twice.
     */
    private function unique(string $slug, array &$taken): string
    {
        if ($slug === '') {
            $slug = 'section';
        }

        $candidate = $slug;
        $count = 2;

        while (isset($taken[$candidate])) {
            $candidate = $slug.'-'.$count;
            $count++;
        }

        $taken[$candidate] = true;

        return $candidate;
    }
}
