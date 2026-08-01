<?php

namespace Goldnead\StatamicToc\Tests\Concerns;

use Faker\Factory;
use Faker\Generator;
use Statamic\Fields\Value;
use Statamic\Fieldtypes\Bard;

/**
 * The fixture builders the test suite shares: HTML, Markdown and Bard documents
 * with a known heading structure, plus the two helpers that read the result.
 */
trait CreatesContent
{
    private ?Generator $contentFaker = null;

    /**
     * Counts the entries of a toc tree, children included.
     */
    protected function countChildren(array $children): int
    {
        $count = 0;

        foreach ($children as $child) {
            $count++;

            if (isset($child['children'])) {
                $count += $this->countChildren($child['children']);
            }
        }

        return $count;
    }

    /**
     * A Bard field carrying the given content.
     */
    protected function bard(mixed $content, ?string $handle = null): Value
    {
        return new Value($content, $handle, new Bard);
    }

    /**
     * HTML with $depth levels of headings, optionally with paragraphs between.
     */
    protected function fakeHTMLContent(int $headings = 3, int $depth = 6, bool $addParagraphs = true, bool $hasH1 = true): string
    {
        $this->faker()->seed(1234);
        $content = '';

        if ($hasH1) {
            $content .= '<h1>Heading 1</h1>'.PHP_EOL;
        }

        if (! $addParagraphs) {
            for ($i = 1; $i < $headings; $i++) {
                $content .= '<h'.($i + 1).'>Heading '.($i + 1).'</h'.($i + 1).'>'.PHP_EOL;
            }
        } else {
            for ($i = 1; $i < $depth; $i++) {
                $content .= '<h'.($i + 1).'>Heading '.($i + 1).'</h'.($i + 1).'>'.PHP_EOL;
                $content .= '<p>'.$this->faker()->paragraph(3).' text with #hash.</p>'.PHP_EOL;
            }
        }

        return $content;
    }

    /**
     * The same document as Markdown.
     */
    protected function fakeMarkdownContent(int $headings = 3, int $depth = 6, bool $addParagraphs = true, bool $hasH1 = true): string
    {
        $content = '';

        if ($hasH1) {
            $content .= "# Heading 1\n\n";
        }

        if (! $addParagraphs) {
            for ($i = 1; $i < $headings; $i++) {
                $content .= str_repeat('#', ($i + 1)).' Heading '.($i + 1)."\n";
            }
        } else {
            for ($i = 1; $i < $depth; $i++) {
                $content .= str_repeat('#', ($i + 1)).' Heading '.($i + 1)."\n";
                $content .= '> '.$this->faker()->paragraph(3)."\n";
            }
        }

        return $content;
    }

    /**
     * The same document as a Bard array.
     */
    protected function fakeBardArray(int $headings = 3, int $depth = 6, bool $addParagraphs = true, bool $hasH1 = true): array
    {
        $content = [];

        if ($hasH1) {
            $content[] = $this->bardHeading(1);
        }

        if (! $addParagraphs) {
            for ($i = 1; $i < $headings; $i++) {
                $content[] = $this->bardHeading($i + 1);
            }
        } else {
            for ($i = 1; $i < $depth; $i++) {
                $content[] = $this->bardHeading($i + 1);
                $content[] = [
                    'type' => 'paragraph',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $this->faker()->paragraph(3),
                        ],
                    ],
                ];
            }
        }

        return $content;
    }

    private function bardHeading(int $level): array
    {
        return [
            'type' => 'heading',
            'attrs' => [
                'level' => $level,
            ],
            'content' => [
                [
                    'type' => 'text',
                    'text' => 'Heading '.$level,
                ],
            ],
        ];
    }

    private function faker(): Generator
    {
        return $this->contentFaker ??= Factory::create();
    }
}
