<?php

namespace Goldnead\StatamicToc;

/**
 * One heading as it was found in the content, before any slug is assigned.
 *
 * Extraction and anchoring are deliberately separate: an extractor reports what
 * the document contains, a slugger decides what to call it. Only that split
 * makes it possible for the tag and the modifier to work from one shared set of
 * headings instead of computing ids twice.
 */
class Heading
{
    public function __construct(
        public readonly string $title,
        public readonly int $level,
        /**
         * An id that was already on the heading, from Bard's anchor button or
         * from hand-written HTML. It wins over any generated slug.
         */
        public readonly ?string $explicitId = null,
    ) {}

    public function hasExplicitId(): bool
    {
        return $this->explicitId !== null && $this->explicitId !== '';
    }

    public function isEmpty(): bool
    {
        return $this->title === '';
    }
}
