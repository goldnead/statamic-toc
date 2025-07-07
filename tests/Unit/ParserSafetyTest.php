<?php

namespace Tests\Unit;

use Goldnead\StatamicToc\Parser;
use Goldnead\StatamicToc\Tests\TestCase;

class ParserSafetyTest extends TestCase
{
    /** @test */
    public function test_handles_malformed_bard_content_safely()
    {
        // Test content with missing attrs
        $malformedContent1 = [
            [
                'type' => 'heading',
                // Missing 'attrs' completely
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'Test Heading'
                    ]
                ]
            ]
        ];

        $parser = new Parser($malformedContent1);
        $result = $parser->build();
        
        // Should not throw an error and return empty result
        $this->assertIsArray($result);
        $this->assertEquals(0, $result['total_results']);
    }

    /** @test */
    public function test_handles_missing_content_array_safely()
    {
        // Test content with missing content array
        $malformedContent2 = [
            [
                'type' => 'heading',
                'attrs' => [
                    'level' => 2
                ]
                // Missing 'content' completely
            ]
        ];

        $parser = new Parser($malformedContent2);
        $result = $parser->build();
        
        // Should not throw an error and return empty result
        $this->assertIsArray($result);
        $this->assertEquals(0, $result['total_results']);
    }

    /** @test */
    public function test_handles_empty_content_array_safely()
    {
        // Test content with empty content array
        $malformedContent3 = [
            [
                'type' => 'heading',
                'attrs' => [
                    'level' => 2
                ],
                'content' => [] // Empty array
            ]
        ];

        $parser = new Parser($malformedContent3);
        $result = $parser->build();
        
        // Should not throw an error and return empty result
        $this->assertIsArray($result);
        $this->assertEquals(0, $result['total_results']);
    }

    /** @test */
    public function test_handles_malformed_content_item_safely()
    {
        // Test content with malformed content item
        $malformedContent4 = [
            [
                'type' => 'heading',
                'attrs' => [
                    'level' => 2
                ],
                'content' => [
                    [
                        'type' => 'text'
                        // Missing 'text' property
                    ]
                ]
            ]
        ];

        $parser = new Parser($malformedContent4);
        $result = $parser->build();
        
        // Should not throw an error and return empty result
        $this->assertIsArray($result);
        $this->assertEquals(0, $result['total_results']);
    }

    /** @test */
    public function test_handles_missing_type_property_safely()
    {
        // Test content with missing type in content item
        $malformedContent5 = [
            [
                'type' => 'heading',
                'attrs' => [
                    'level' => 2
                ],
                'content' => [
                    [
                        // Missing 'type' property
                        'text' => 'Test Heading'
                    ]
                ]
            ]
        ];

        $parser = new Parser($malformedContent5);
        $result = $parser->build();
        
        // Should not throw an error and return empty result
        $this->assertIsArray($result);
        $this->assertEquals(0, $result['total_results']);
    }

    /** @test */
    public function test_handles_non_text_content_type_safely()
    {
        // Test content with non-text content type
        $malformedContent6 = [
            [
                'type' => 'heading',
                'attrs' => [
                    'level' => 2
                ],
                'content' => [
                    [
                        'type' => 'image', // Not 'text'
                        'text' => 'Test Heading'
                    ]
                ]
            ]
        ];

        $parser = new Parser($malformedContent6);
        $result = $parser->build();
        
        // Should not throw an error and return empty result
        $this->assertIsArray($result);
        $this->assertEquals(0, $result['total_results']);
    }

    /** @test */
    public function test_handles_mixed_valid_and_invalid_content_safely()
    {
        // Test content with mix of valid and invalid items
        $mixedContent = [
            [
                'type' => 'heading',
                'attrs' => [
                    'level' => 1
                ],
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'Valid Heading'
                    ]
                ]
            ],
            [
                'type' => 'heading',
                // Missing attrs - should be skipped
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'Invalid Heading'
                    ]
                ]
            ],
            [
                'type' => 'heading',
                'attrs' => [
                    'level' => 2
                ],
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'Another Valid Heading'
                    ]
                ]
            ]
        ];

        $parser = new Parser($mixedContent);
        $result = $parser->build();
        
        // Should process only valid headings
        $this->assertIsArray($result);
        $this->assertEquals(2, $result[0]['total_results']);
        $this->assertEquals('Valid Heading', $result[0]['toc_title']);
    }
}