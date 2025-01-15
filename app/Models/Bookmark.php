<?php

namespace App\Models;

use App\Models\Story;
use Illuminate\Foundation\Auth\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Bookmark extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'story_id'];

    public function user(): BelongsTo{
        return $this->belongsTo(User::class);
    }

    public function story(): BelongsTo{
        return $this->belongsTo(Story::class);
    }
}
