<?php

namespace Goldnead\StatamicToc;

/**
 * What the list shows. Immutable: every change hands back a new instance, so a
 * parser cannot drift into a half-applied state.
 *
 * `from` and `to` are absolute heading levels. Depth is the relative way of
 * saying the same thing and is kept because that is what existing templates
 * use, but it is derived from the range rather than stored next to it.
 * Recomputing both from each other in both directions is what made `from()`
 * after `depth()` silently widen the range.
 */
final class Options
{
    private function __construct(
        public readonly int $from,
        public readonly int $to,
        public readonly bool $flat,
        public readonly ?string $exclude,
    ) {}

    public static function default(): self
    {
        return new self(from: 1, to: 3, flat: false, exclude: null);
    }

    /**
     * A level given as either 2 or "h2", clamped to what HTML has.
     */
    public static function level(string|int|null $level, int $fallback): int
    {
        if ($level === null || $level === '') {
            return $fallback;
        }

        $number = is_string($level) ? (int) ltrim(trim($level), 'hH') : (int) $level;

        return $number < 1 ? $fallback : min(6, $number);
    }

    /**
     * How many levels the list spans, counted from `from`.
     */
    public function depth(): int
    {
        return $this->to - $this->from + 1;
    }

    public function withFrom(string|int $level): self
    {
        $from = self::level($level, $this->from);

        // The range keeps its width when the starting point moves.
        return new self($from, min(6, $from + $this->depth() - 1), $this->flat, $this->exclude);
    }

    public function withTo(string|int $level): self
    {
        return new self($this->from, max($this->from, self::level($level, $this->to)), $this->flat, $this->exclude);
    }

    public function withDepth(int $depth): self
    {
        return new self($this->from, min(6, $this->from + max(1, $depth) - 1), $this->flat, $this->exclude);
    }

    public function withFlat(bool $flat = true): self
    {
        return new self($this->from, $this->to, $flat, $this->exclude);
    }

    public function withExclude(?string $exclude): self
    {
        return new self($this->from, $this->to, $this->flat, $exclude === '' ? null : $exclude);
    }

    public function covers(int $level): bool
    {
        return $level >= $this->from && $level <= $this->to;
    }
}
