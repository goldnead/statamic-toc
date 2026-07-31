<?php

namespace Goldnead\StatamicToc\Extractors;

use Goldnead\StatamicToc\Heading;

class BardExtractor implements Extractor
{
    /** @var array<int, Heading> */
    private array $headings = [];

    public function extract(mixed $content): array
    {
        $this->headings = [];

        if (is_array($content)) {
            $this->walk($content);
        }

        return $this->headings;
    }

    /**
     * Bard nests: sets hold nodes, nodes hold nodes. A flat scan of the top
     * level misses every heading inside a two-column set or a replicator.
     */
    private function walk(mixed $items): void
    {
        if (! is_array($items)) {
            return;
        }

        // A list of nodes rather than a single one.
        if (array_key_exists(0, $items)) {
            foreach ($items as $item) {
                $this->walk($item);
            }

            return;
        }

        if (($items['type'] ?? null) === 'heading') {
            $this->collect($items);
        }

        foreach ($items as $key => $value) {
            if (! is_array($value)) {
                continue;
            }

            // A node's own attrs hold no headings. A set's do.
            if ($key === 'attrs' && ($items['type'] ?? null) !== 'set') {
                continue;
            }

            $this->walk($value);
        }
    }

    private function collect(array $node): void
    {
        $level = $node['attrs']['level'] ?? null;

        if (! is_numeric($level)) {
            return;
        }

        $content = $node['content'] ?? [];
        $id = $node['attrs']['id'] ?? null;

        $this->headings[] = new Heading(
            title: is_array($content) ? $this->text($content) : '',
            level: (int) $level,
            explicitId: is_string($id) && trim($id) !== '' ? trim($id) : null,
        );
    }

    /**
     * A heading is a list of inline nodes. Reading only the first one drops
     * everything behind a bold word and skips the heading entirely when it
     * starts with one.
     */
    private function text(array $content): string
    {
        $text = '';

        foreach ($content as $node) {
            if (! is_array($node)) {
                continue;
            }

            if (isset($node['text']) && is_scalar($node['text'])) {
                $text .= $node['text'];
            } elseif (isset($node['content']) && is_array($node['content'])) {
                $text .= $this->text($node['content']);
            }
        }

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }
}
