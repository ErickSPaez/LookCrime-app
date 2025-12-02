<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterSection extends Model {

    protected $table = 'newsletter_section';
    public $timestamps = false;

    protected $fillable = ['newsletter_id','seq','content','image'];

}
