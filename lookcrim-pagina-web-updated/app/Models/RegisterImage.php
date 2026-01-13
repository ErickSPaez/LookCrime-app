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

        // For local "public" disk, return a relative URL so it works regardless
        // of APP_URL host/port (e.g. artisan serve on 127.0.0.1:8000).
        if ($disk === 'public') {
            $path = str_replace('\\', '/', $path);
            $path = ltrim($path, '/');
            return '/storage/' . $path;
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
