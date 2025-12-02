<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class NewsletterSubscriber extends Authenticatable {
    use Notifiable;

    protected $table = 'newsletter_subscribers';
    public $timestamps = false;

    protected $fillable = ['email', 'remember_token', 'confirmed', 'created_at'];

}
