<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = [
        'name',
        'guard_name',
        'name_en',
        'name_pt',
    ];

    public function nameLocalized(): string
    {
        $loc = app()->getLocale();
        if ($loc === 'pt') {
            return $this->name_pt ?: $this->name;
        }
        return $this->name_en ?: $this->name;
    }
}
