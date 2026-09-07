<?php

return [

    'permission' => 'Manage table of contents settings',

    'groups' => [
        'defaults' => [
            'title' => 'Table of contents',
            'description' => 'What a {{ toc }} works with when the template says nothing else. Every value can still be overridden on the tag; what stands here applies everywhere else.',
        ],
    ],

    'fields' => [
        'field' => [
            'label' => 'Field',
            'description' => 'Which field of the entry the tag reads the text from, as long as no "field" or "content" parameter is given.',
        ],
        'from' => [
            'label' => 'First level',
            'description' => 'The heading level the list starts at. Higher headings above it do not appear.',
        ],
        'depth' => [
            'label' => 'Levels',
            'description' => 'How many levels the list spans, counted from the first one. A last level that is set wins over this.',
        ],
        'to' => [
            'label' => 'Last level',
            'description' => 'The level the list stops at, counted absolutely. When set, it applies instead of the number of levels. Empty means the number decides.',
        ],
        'flat' => [
            'label' => 'Flat list',
            'description' => 'On: every heading sits on one level, side by side. Off: they nest, the way they do in the text.',
        ],
    ],

];
