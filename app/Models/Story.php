<?php

namespace App\Models;

use App\Models\Image;
use App\Models\Users;
use App\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Story extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'images_cover',
        'category_id',
        'user_id',
    ];

    public function user(): BelongsTo{
        return $this->belongsTo(Users::class);
    }

    public function category(): BelongsTo{
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany{
        return $this->hasMany(Image::class);
    }
}
