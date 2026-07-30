<?php

namespace Goldnead\StatamicToc\Extractors;

use Goldnead\StatamicToc\Heading;

interface Extractor
{
    /**
     * Every heading in the content, in document order, at every level.
     *
     * Extractors do not filter. Level ranges and exclusions are applied by the
     * caller, because the modifier needs the complete set even when the list
     * only shows part of it.
     *
     * @return array<int, Heading>
     */
    public function extract(mixed $content): array;
}
