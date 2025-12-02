<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class News extends Model {

    /** Mass assignable */
    protected $fillable = [
        'title_en','title_pt','content_en','content_pt','image','embed_url','embed_url_en','private','highlight'
    ];

    public function title() {
        switch(app()->getLocale()) {
            case 'pt': return $this->title_pt; break;
            case 'en': return $this->title_en; break;
            default: return $this->title_pt;
        }
    }

    public function content() {
        switch(app()->getLocale()) {
            case 'pt': return $this->content_pt; break;
            case 'en': return $this->content_en; break;
            default: return $this->content_pt;
        }
    }

    public function get_embed_url() {
        switch(app()->getLocale()) {
            case 'pt': return $this->embed_url; break;
            case 'en': return $this->embed_url_en; break;
            default: return $this->embed_url;
        }
    }
    
    public function get_image(){
        return $this->image;
    }

}
