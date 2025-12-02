<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsersRegister extends Model {
    protected $table = 'users_registers';

    protected $fillable = ['name','email','password','verified','remember_token','created_at'];
    public $timestamps = false;
}
