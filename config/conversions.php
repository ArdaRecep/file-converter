<?php

return [
    'categories' => [
        'document' => [
            'accept' => ['docx', 'xlsx', 'pptx'],
            'targets' => ['pdf', 'html', 'txt'],
        ],
        'image' => [
            'accept' => ['jpg', 'jpeg', 'png', 'webp'],
            // aynı formata dönüşüm yok: UI tarafında filtreleyeceğiz ama backend de kontrol eder
            'targets' => ['jpg', 'png', 'webp'],
        ],
    ],
];