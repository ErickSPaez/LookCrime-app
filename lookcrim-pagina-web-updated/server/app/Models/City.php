<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class City extends Model
{
    use HasFactory;

    protected $table = 'cities';

    protected $fillable = [
        'name',
        'slug',
        'center_lat',
        'center_lng',
        'radius_m',
    ];

    protected $casts = [
        'center_lat' => 'float',
        'center_lng' => 'float',
        'radius_m' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $city) {
            if (empty($city->slug) && !empty($city->name)) {
                $city->slug = Str::slug($city->name);
            }
        });
    }
}
