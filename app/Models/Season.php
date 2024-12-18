<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Season extends Model
{
    use HasFactory;

    protected $fillable = [
        'content_id', 'season_number','total_episodes'
    ];

    protected $hidden = [
        'id',
    ];

    // Relation "many-to-one" avec Content (chaque saison appartient à une série)
    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class, 'content_id', 'uuid');
    }

    // Relation "hasMany" avec Episodes
    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class, 'season_id', 'uuid');
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
