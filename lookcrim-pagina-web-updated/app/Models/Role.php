<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['slug','name_en','name_pt','permissions'];

    protected $casts = [
        'permissions' => 'array',
    ];

    public function nameLocalized(): string
    {
        $loc = app()->getLocale();
        if ($loc === 'pt') return $this->name_pt;
        return $this->name_en;
    }
}
