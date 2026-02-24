<?php

namespace App\Providers;

use App\Support\LegacyHTML;
use App\Support\LegacyForm;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use League\Flysystem\Filesystem as FlysystemFilesystem;
use League\Flysystem\GoogleCloudStorage\GoogleCloudStorageAdapter;
use League\Flysystem\GoogleCloudStorage\UniformBucketLevelAccessVisibility;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // If APP_URL is https://..., force https URL generation. This prevents
        // mixed-content / insecure form submit warnings when running behind
        // a TLS-terminating proxy (Cloud Run).
        if (Str::startsWith((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // Register legacy facades for quick compatibility with old Blade templates
        if (!class_exists('HTML')) {
            class_alias(LegacyHTML::class, 'HTML');
        }
        if (!class_exists('Form')) {
            class_alias(LegacyForm::class, 'Form');
        }

        // Google Cloud Storage (Flysystem) driver: `gcs`
        Storage::extend('gcs', function ($app, array $config) {
            $clientConfig = array_filter([
                'projectId' => $config['project_id'] ?? null,
                'keyFilePath' => $config['key_file_path'] ?? null,
                'keyFile' => $config['key_file'] ?? null,
            ], fn ($v) => $v !== null && $v !== '');

            $storageClient = new StorageClient($clientConfig);
            $bucket = $storageClient->bucket($config['bucket']);

            $prefix = $config['path_prefix'] ?? '';
            if ($prefix === null) {
                $prefix = '';
            }
            $prefix = (string) $prefix;

            $adapter = new GoogleCloudStorageAdapter(
                $bucket,
                $prefix,
                new UniformBucketLevelAccessVisibility()
            );

            $flysystem = new FlysystemFilesystem($adapter);

            return new FilesystemAdapter($flysystem, $adapter, $config);
        });
    }
}
