<?php
// One-off script to insert a sample register for local testing.
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Register;

$data = [
    'title_pt' => 'Registro de teste',
    'title_en' => 'Test Register',
    'content_pt' => 'Conteúdo de teste em português.',
    'content_en' => 'Test content in English.',
    'image' => '',
    'embed_url' => '',
    'embed_url_en' => '',
    'private' => 0,
];

$register = Register::create($data);
echo "CREATED_REGISTER_ID=" . $register->id . PHP_EOL;
