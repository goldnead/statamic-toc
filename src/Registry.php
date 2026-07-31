<?php

namespace Goldnead\StatamicToc;

use Goldnead\StatamicToc\Anchors\Slugger;

/**
 * The one place anchors are decided, shared by the tag and the modifier for the
 * length of a request.
 *
 * Keyed by the headings themselves, not by the raw content. The tag usually
 * sees a Bard array while the modifier sees the rendered HTML of the same
 * field: two very different strings describing the same sequence of headings.
 * Hashing the levels and titles is what lets both sides land on the same entry.
 */
class Registry
{
    /** @var array<string, array<int, string|null>> */
    private array $anchors = [];

    public function __construct(private readonly Slugger $slugger = new Slugger) {}

    /**
     * Anchors for these headings, aligned by index. Computed once per document
     * and per request.
     *
     * @param  array<int, Heading>  $headings
     * @return array<int, string|null>
     */
    public function anchorsFor(array $headings): array
    {
        $key = $this->fingerprint($headings);

        return $this->anchors[$key] ??= $this->slugger->assign($headings);
    }

    public function flush(): void
    {
        $this->anchors = [];
    }

    /**
     * @param  array<int, Heading>  $headings
     */
    private function fingerprint(array $headings): string
    {
        return md5(implode("\n", array_map(
            fn (Heading $h) => $h->level.'|'.$h->explicitId.'|'.$h->title,
            $headings
        )));
    }
}
