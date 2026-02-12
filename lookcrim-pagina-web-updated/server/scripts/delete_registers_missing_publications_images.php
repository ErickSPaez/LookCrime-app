<?php

declare(strict_types=1);

use App\Models\Register;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$options = getopt('', ['dry-run']);
$dryRun = array_key_exists('dry-run', $options);

function normalizePublicDiskRelativePath(string $value): ?string
{
    $path = trim($value);
    if ($path === '') {
        return null;
    }

    if (filter_var($path, FILTER_VALIDATE_URL)) {
        $parsedPath = parse_url($path, PHP_URL_PATH);
        if (is_string($parsedPath) && $parsedPath !== '') {
            $path = $parsedPath;
        }
    }

    $path = str_replace('\\', '/', $path);
    $path = ltrim($path, '/');

    if (str_starts_with($path, 'storage/')) {
        $path = substr($path, strlen('storage/'));
    }

    $pos = strpos($path, 'publications/');
    if ($pos === false) {
        return null;
    }

    return substr($path, $pos);
}

$disk = Storage::disk('public');

$rows = Register::query()
    ->where('image', 'like', '%publications%')
    ->get(['id', 'image']);

$total = $rows->count();
$toDelete = [];
foreach ($rows as $r) {
    $rel = normalizePublicDiskRelativePath((string) ($r->image ?? ''));
    if ($rel === null) {
        continue;
    }
    if (!$disk->exists($rel)) {
        $toDelete[] = ['id' => $r->id, 'image' => $r->image, 'expected_rel' => $rel];
    }
}

echo 'dryRun=' . ($dryRun ? 'yes' : 'no') . PHP_EOL;
echo "found_publications_refs={$total}" . PHP_EOL;
echo 'missing_files=' . count($toDelete) . PHP_EOL;

if (count($toDelete) > 0) {
    echo PHP_EOL . "Will delete these registers (missing image files):" . PHP_EOL;
    foreach ($toDelete as $m) {
        echo "- id={$m['id']} image={$m['image']} expected_rel={$m['expected_rel']}" . PHP_EOL;
    }
}

if ($dryRun) {
    exit(0);
}

DB::beginTransaction();
try {
    $ids = array_map(fn($m) => (int) $m['id'], $toDelete);
    $deleted = 0;
    if (count($ids) > 0) {
        $deleted = Register::query()->whereIn('id', $ids)->delete();
    }
    DB::commit();
    echo PHP_EOL . "deleted={$deleted}" . PHP_EOL;
} catch (Throwable $e) {
    DB::rollBack();
    fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
