<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Publications extends Model {

    protected $fillable = [
        'title_en','title_pt','content_en','content_pt','image','embed_url','embed_url_en','private'
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

    public function get_image(){
        return $this->image;
    }

    /**
     * Return the embed url according to current locale or empty string
     * View templates call $publications->get_embed_url(), so provide it.
     */
    public function get_embed_url()
    {
        switch (app()->getLocale()) {
            case 'en':
                return $this->embed_url_en ?? '';
            default:
                return $this->embed_url ?? '';
        }
    }

    // Backwards-compatible getter if some templates call get_embed_url_en()
    public function get_embed_url_en()
    {
        return $this->embed_url_en ?? '';
    }

}
