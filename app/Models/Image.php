<?php

namespace App\Models;

use App\Models\Story;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Image extends Model
{
    use HasFactory;

    protected $fillable = [
        'stories_id',
        'filename'
    ];

    public function story(): BelongsTo{
        return $this->belongsTo(Story::class, 'stories_id');
    }
}
