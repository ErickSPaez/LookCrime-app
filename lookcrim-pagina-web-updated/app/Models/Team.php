<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model {

    protected $fillable = ['content_en','content_pt'];

    public function content() {
        switch(app()->getLocale()) {
            case 'pt': return $this->content_pt; break;
            case 'en': return $this->content_en; break;
            default: return $this->content_pt;
        }
    }

}
