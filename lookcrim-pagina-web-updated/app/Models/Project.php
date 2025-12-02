<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model {

    protected $fillable = ['content_pt','content_en'];

    public function content() {
        switch(app()->getLocale()) {
            case 'pt': return $this->content_pt; break;
            case 'en': return $this->content_en; break;
            default: return $this->content_pt;
        }
    }
    protected $table = 'project';
    public $timestamps = false;
}
