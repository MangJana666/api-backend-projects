<?php

namespace App\Models;

use App\Models\Story;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function bookmarks(): HasMany{
        return $this->hasMany(Bookmark::class);
    }

    public function bookmarkedStories(): HasMany{
        return $this->belongsToMany(Story::class, 'bookmarks');
    }
}
