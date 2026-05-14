<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Register extends Model
{
    protected $table = 'registers';

    protected $fillable = [
        'title_en',
        'title_pt',
        'content_en',
        'content_pt',
        'image',
        'embed_url',
        'embed_url_en',
        'private',
        'latitude',
        'longitude',
        'address',
        'category',
        'user_id',
        'city_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function images()
    {
        return $this->hasMany(RegisterImage::class, 'register_id')->orderBy('sort_order')->orderBy('id');
    }

    public function title()
    {
        return match (app()->getLocale()) {
            'pt' => $this->title_pt,
            'en' => $this->title_en,
            default => $this->title_pt,
        };
    }

    public function content()
    {
        return match (app()->getLocale()) {
            'pt' => $this->content_pt,
            'en' => $this->content_en,
            default => $this->content_pt,
        };
    }

    public function get_image()
    {
        return $this->image;
    }

    public function get_embed_url()
    {
        return match (app()->getLocale()) {
            'en' => $this->embed_url_en ?? '',
            default => $this->embed_url ?? '',
        };
    }

    public function get_embed_url_en()
    {
        return $this->embed_url_en ?? '';
    }

    /**
     * Normaliza y devuelve una URL usable para plantillas.
     * Soporta rutas antiguas (storage/publications) y nuevas (storage/registers).
     */
    public function image_url()
    {
        // Prefer new multi-image relation when present
        try {
            if ($this->relationLoaded('images') && $this->images && $this->images->count() > 0) {
                $first = $this->images->sortBy(['sort_order', 'id'])->first();
                return $first?->url();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        $img = $this->image ?? null;
        if (!$img) {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $img)) {
            return $img;
        }

        if (Str::startsWith($img, 'storage/') || Str::startsWith($img, '/storage/')) {
            return asset($img);
        }

        if (Str::startsWith($img, 'public/')) {
            $path = preg_replace('#^public/#', 'storage/', $img);
            return asset($path);
        }

        $basename = basename($img);

        // Prefer the newer folder if present, fallback to legacy.
        $registerPath = 'storage/registers/' . $basename;
        $publicationPath = 'storage/publications/' . $basename;

        // We can't reliably check file existence in all environments here,
        // so return the legacy path for backwards compatibility.
        return asset($publicationPath);
    }

    protected static function booted(): void
    {
        static::deleting(function (self $register) {
            // Delete related images (and their files) via model events
            try {
                $register->images()->get()->each(function (RegisterImage $img) {
                    $img->delete();
                });
            } catch (\Throwable $e) {
                // ignore
            }

            // Best-effort cleanup of legacy single-image file if it exists
            $img = $register->image ?? null;
            if (!$img) {
                return;
            }
            if (preg_match('/^https?:\/\//i', $img)) {
                return;
            }

            $path = str_replace('\\', '/', $img);
            $path = ltrim($path, '/');
            if (Str::startsWith($path, 'storage/')) {
                $rel = substr($path, strlen('storage/'));
                try {
                    Storage::disk('public')->delete($rel);
                } catch (\Throwable $e) {
                    // ignore
                }
            }
        });
    }
}
