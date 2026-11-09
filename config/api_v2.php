<?php

return [
    'rate_limit' => env('API_V2_RATE_LIMIT', '60,1'),
    'features_per_page' => 500,
    'features_per_page_max' => 5000,
    'layers_per_page' => 100,
    'layers_per_page_max' => 1000,
    'vocabulary_per_page' => 1000,
    'vocabulary_per_page_max' => 10000,
    'sample_max' => 5000,
    'fuzzy_threshold' => 0.3,
    'polygon_max_vertices' => 1000,
    'layers_max' => 100,
    'string_max' => 200,
    'seed_max' => 64,
    'extended_data_max_clauses' => 10,
];
