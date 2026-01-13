<?php

declare(strict_types=1);

use App\Models\Register;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$options = getopt('', ['dry-run', 'move']);
$dryRun = array_key_exists('dry-run', $options);
$doMove = array_key_exists('move', $options);

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
$updated = 0;
$copied = 0;
$moved = 0;
$missingFiles = [];
$skipped = 0;
$errors = [];

DB::beginTransaction();
try {
    foreach ($rows as $r) {
        $oldValue = (string) ($r->image ?? '');
        $rel = normalizePublicDiskRelativePath($oldValue);
        if ($rel === null) {
            $skipped++;
            continue;
        }

        $newRel = str_replace('publications/', 'registers/', $rel);
        $newDbValue = 'storage/' . $newRel;

        if (!$disk->exists($rel)) {
            $missingFiles[] = ['id' => $r->id, 'image' => $oldValue, 'expected_rel' => $rel];
            continue;
        }

        if (!$disk->exists($newRel)) {
            if (!$dryRun) {
                $dir = dirname($newRel);
                if ($dir !== '.' && !$disk->exists($dir)) {
                    $disk->makeDirectory($dir);
                }

                if ($doMove) {
                    $disk->move($rel, $newRel);
                    $moved++;
                } else {
                    $disk->copy($rel, $newRel);
                    $copied++;
                }
            }
        }

        if (!$dryRun) {
            $r->image = $newDbValue;
            $r->save();
        }
        $updated++;
    }

    if ($dryRun) {
        DB::rollBack();
    } else {
        DB::commit();
    }
} catch (Throwable $e) {
    DB::rollBack();
    $errors[] = $e->getMessage();
}

echo "dryRun=" . ($dryRun ? 'yes' : 'no') . PHP_EOL;
echo "mode=" . ($doMove ? 'move' : 'copy') . PHP_EOL;
echo "found={$total}" . PHP_EOL;
echo "updated={$updated}" . PHP_EOL;
echo "copied={$copied}" . PHP_EOL;
echo "moved={$moved}" . PHP_EOL;
echo "skipped={$skipped}" . PHP_EOL;
echo "missing_files=" . count($missingFiles) . PHP_EOL;

if (count($missingFiles) > 0) {
    echo PHP_EOL . "Missing file examples:" . PHP_EOL;
    foreach (array_slice($missingFiles, 0, 10) as $m) {
        echo "- id={$m['id']} image={$m['image']} expected_rel={$m['expected_rel']}" . PHP_EOL;
    }
}

if (count($errors) > 0) {
    echo PHP_EOL . "Errors:" . PHP_EOL;
    foreach ($errors as $err) {
        echo "- {$err}" . PHP_EOL;
    }
    exit(1);
}
