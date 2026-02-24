<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class RegisterImage extends Model
{
    protected $table = 'register_images';

    protected $fillable = [
        'register_id',
        'disk',
        'path',
        'sort_order',
    ];

    public function register()
    {
        return $this->belongsTo(Register::class, 'register_id');
    }

    public function url(): ?string
    {
        $disk = $this->disk ?: 'public';
        $path = $this->path ?: null;
        if (!$path) {
            return null;
        }

        $path = (string) $path;

        // If DB already contains a full URL, use it as-is.
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        // If DB contains a gs:// URL, convert to public HTTPS URL.
        if (preg_match('#^gs://([^/]+)/(.+)$#i', $path, $m)) {
            $bucket = $m[1];
            $objectPath = $m[2];
            $objectPath = str_replace('\\', '/', $objectPath);
            $objectPath = ltrim($objectPath, '/');
            return 'https://storage.googleapis.com/' . $bucket . '/' . $objectPath;
        }

        if ($disk === 'public') {
            $path = str_replace('\\', '/', $path);
            $path = ltrim($path, '/');

            $publicDriver = (string) (config('filesystems.disks.public.driver') ?? 'local');
            if ($publicDriver === 'local') {
                // For local "public" disk, return a relative URL so it works regardless
                // of APP_URL host/port (e.g. artisan serve on 127.0.0.1:8000).
                return '/storage/' . $path;
            }

            // For GCS public disk, prefer Laravel's URL helper but fallback to a deterministic
            // public URL in case the adapter/url config is missing in a given environment.
            try {
                $url = Storage::disk('public')->url($path);
                if (is_string($url) && $url !== '') {
                    return $url;
                }
            } catch (\Throwable $e) {
                // continue to fallback
            }

            $baseUrl = (string) (config('filesystems.disks.public.url') ?? '');
            if ($baseUrl !== '') {
                return rtrim($baseUrl, '/') . '/' . $path;
            }

            $bucket = (string) (config('filesystems.disks.public.bucket') ?? '');
            if ($bucket !== '') {
                return 'https://storage.googleapis.com/' . $bucket . '/' . $path;
            }

            return null;
        }

        try {
            return Storage::disk($disk)->url($path);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected static function booted(): void
    {
        static::deleting(function (self $img) {
            $disk = $img->disk ?: 'public';
            $path = $img->path ?: null;
            if (!$path) {
                return;
            }

            try {
                Storage::disk($disk)->delete($path);
            } catch (\Throwable $e) {
                // ignore
            }
        });
    }
}
