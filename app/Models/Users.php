<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Users extends Model
{
    use HasFactory, HasApiTokens, HasFactory;

    protected $fillable = [
        'name',
        'avatar',
        'username',
        'email',
        'password',
        'about'
    ];
}
