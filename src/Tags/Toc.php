<?php

/**
 * The {{ toc }} tag.
 */

namespace Goldnead\StatamicToc\Tags;

use Goldnead\StatamicToc\Options;
use Goldnead\StatamicToc\Parser;
use Statamic\Fields\Value;
use Statamic\Tags\Concerns;
use Statamic\Tags\Tags;

class Toc extends Tags
{
    use Concerns\OutputsItems;

    protected static $handle = 'toc';

    /**
     * The {{ toc }} tag.
     *
     * @return string|array
     */
    public function index()
    {
        if (! $this->enabled()) {
            // An empty array rather than null, so count() and no_results keep
            // working the way templates expect.
            return [];
        }

        $content = $this->content();

        if ($content === null) {
            return [];
        }

        return $this->output(
            (new Parser($content))->options($this->options())->build()
        );
    }

    /**
     * The {{ toc:count }} tag.
     *
     * Counts what the list would show. It used to force the depth to 6 and so
     * reported a different number than the list right underneath it.
     *
     * @return int
     */
    public function count()
    {
        $result = $this->index();

        if (empty($result)) {
            return 0;
        }

        return isset($result[0]) ? $result[0]['total_results'] : $result['total_results'];
    }

    private function options(): Options
    {
        $options = Options::default()
            ->withDepth($this->params->int('depth', 3))
            ->withFrom($this->param('from') ?? 'h1')
            ->withFlat($this->params->bool('is_flat'))
            ->withExclude($this->stringParam('exclude'));

        // `to` is absolute and wins over the relative depth when both are given.
        return $this->params->has('to')
            ? $options->withTo($this->param('to'))
            : $options;
    }

    /**
     * The content to parse: either handed in directly or read from a field in
     * the current context.
     */
    private function content(): mixed
    {
        if ($content = $this->param('content')) {
            return $content;
        }

        $field = $this->context->get($this->params->get('field', 'article'));

        if (! $field) {
            return null;
        }

        return $field instanceof Value ? $field->raw() : $field;
    }

    private function enabled(): bool
    {
        $when = $this->param('when') ?? true;

        return ! in_array($when, [false, 'false', 0, '0'], true);
    }

    /**
     * Statamic 5 and up hand dynamic parameters over as Value objects. The
     * parser wants the raw string or Bard array underneath.
     */
    private function param(string $name): mixed
    {
        $value = $this->params->get($name);

        return $value instanceof Value ? $value->raw() : $value;
    }

    private function stringParam(string $name): ?string
    {
        $value = $this->param($name);

        return $value === null || $value === '' ? null : (string) $value;
    }
}
