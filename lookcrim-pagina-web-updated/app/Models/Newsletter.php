<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Newsletter extends Model {

    public function sections() {
        return $this->hasMany(NewsletterSection::class)->orderBy('seq');
    }

    public function nextSeq() {
        if($this->sections->last()) {
            return $this->sections->last()->seq+1;
        } else {
            return 1;
        }
    }

    protected $table = 'newsletter';
    protected $fillable = ['subject','content','image','sent'];

}
