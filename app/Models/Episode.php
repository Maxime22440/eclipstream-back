<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Episode extends Model
{
    use HasFactory;

    protected $fillable = [
        'season_id', 'episode_number', 'title', 'description',
        'release_date', 'imdb_rating', 'video_link', 'stream_link',
        'is_uploaded'
    ];

    protected $hidden = [
        'id',
    ];

    // Relation "many-to-one" avec Season
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class, 'season_id', 'uuid');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }
}
