<?php

return [
    'vector_dim' => (int) env('RAG_VECTOR_DIM', 768),

    'qdrant' => [
        'url' => env('QDRANT_URL'),
        'api_key' => env('QDRANT_API_KEY'),
        'collection' => env('QDRANT_COLLECTION', 'ads_docs'),
    ],
];
