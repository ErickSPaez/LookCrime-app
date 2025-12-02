<?php
// One-off script to insert a sample publication for local testing.
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Publications;

$data = [
    'title_pt' => 'Publicação de teste',
    'title_en' => 'Test Publication',
    'content_pt' => 'Conteúdo de teste em português.',
    'content_en' => 'Test content in English.',
    'image' => '',
    'embed_url' => '',
    'embed_url_en' => '',
    'private' => 0,
];

$pub = Publications::create($data);
echo "CREATED_PUBLICATION_ID=" . $pub->id . PHP_EOL;
