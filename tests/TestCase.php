<?php

namespace Goldnead\StatamicToc\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Statamic\Extend\Manifest;
use Statamic\Fields\Value;
use Statamic\Fieldtypes\Bard;
use Statamic\Statamic;

class TestCase extends OrchestraTestCase
{
  protected function setUp(): void
  {
    //require_once(__DIR__ . '/ExceptionHandler.php');
    parent::setUp();
    $this->faker = \Faker\Factory::create();
  }

  protected function getEnvironmentSetUp($app)
  {
    parent::getEnvironmentSetUp($app);

    $app->make(Manifest::class)->manifest = [
      'goldnead/statamic-toc' => [
        'id' => 'goldnead/statamic-toc',
        'namespace' => 'Goldnead\\StatamicToc\\',
      ],
    ];
  }

  protected function getPackageProviders($app)
  {
    return [
      \Statamic\Providers\StatamicServiceProvider::class,
      \Goldnead\StatamicToc\ServiceProvider::class,
    ];
  }

  protected function getPackageAliases($app)
  {
    return [
      'Statamic' => Statamic::class,
    ];
  }

  protected function resolveApplicationConfiguration($app)
  {
    parent::resolveApplicationConfiguration($app);

    $configs = [
      'assets', 'cp', 'forms', 'static_caching',
      'sites', 'stache', 'system', 'users',
    ];

    foreach ($configs as $config) {
      $app['config']->set("statamic.$config", require(__DIR__ . "/../vendor/statamic/cms/config/{$config}.php"));
    }

    $app['config']->set('statamic.users.repository', 'file');
  }

  /** 
   * Helper
   * Counts the number of children of a toc-tree
   */
  protected function countChildren($children)
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
   * Helper
   * Returns a Bard fieldtype with the given content
   */
  protected function bard($content, $handle = null)
  {
    return new Value($content, $handle, new Bard());
  }

  /** 
   * Helper
   * Returns fake HTML content for testing
   */
  protected function fakeHTMLContent($headings = 3, $depth = 6, $addParagraphs = true, $hasH1 = true)
  {
    $content = '';
    if ($hasH1) {
      $content .= '<h1>Heading 1</h1>\n';
    }
    if (!$addParagraphs) {
      for ($i = 1; $i < $headings; $i++) {
        $content .= '<h' . ($i + 1) . '>Heading ' . ($i + 1) . '</h' . ($i + 1) . '>\n';
      }
    } else {
      for ($i = 1; $i < $depth; $i++) {
        $content .= '<h' . ($i + 1) . '>Heading ' . ($i + 1) . '</h' . ($i + 1) . '>\n';
        $content .= '<p>' . $this->faker->paragraph(3) . ' text with #hash.</p>\n';
      }
    }
    return $content;
  }

  /** 
   * Helper
   * Returns fake Markdown content for testing
   */
  protected function fakeMarkdownContent($headings = 3, $depth = 6, $addParagraphs = true, $hasH1 = true)
  {
    $content = "";
    if ($hasH1) {
      $content .= "# Heading 1\n\n";
    }
    if (!$addParagraphs) {
      for ($i = 1; $i < $headings; $i++) {
        $content .= str_repeat('#', ($i + 1)) + ' Heading ' . ($i + 1) . "\n";
      }
    } else {
      for ($i = 1; $i < $depth; $i++) {
        $content .= str_repeat('#', ($i + 1)) . ' Heading ' . ($i + 1) . "\n";
        $content .= '> ' . $this->faker->paragraph(3) . "\n";
      }
    }
    return $content;
  }

  /**
   * Helper
   * Returns a fake Bard array
   */
  protected function fakeBardArray($headings = 3, $depth = 6, $addParagraphs = true, $hasH1 = true)
  {
    $content = [];
    if ($hasH1) {
      $content = [
        [
          "type" => "heading",
          "attrs" => [
            "level" => 1,
          ],
          "content" => [
            [
              "type" => "text",
              "text" => "Heading 1",
            ],
          ],
        ],
      ];
    }
    if (!$addParagraphs) {
      for ($i = 1; $i < $headings; $i++) {
        $content[] = [
          "type" => "heading",
          "attrs" => [
            "level" => $i + 1,
          ],
          "content" => [
            [
              "type" => "text",
              "text" => "Heading " . ($i + 1),
            ],
          ],
        ];
      }
    } else {
      for ($i = 1; $i < $depth; $i++) {
        $content[] = [
          "type" => "heading",
          "attrs" => [
            "level" => $i + 1,
          ],
          "content" => [
            [
              "type" => "text",
              "text" => "Heading " . ($i + 1),
            ],
          ],
        ];
        $content[] = [
          "type" => "paragraph",
          "content" => [
            [
              "type" => "text",
              "text" => $this->faker->paragraph(3),
            ],
          ],
        ];
      }
    }
    return $content;
  }
}
