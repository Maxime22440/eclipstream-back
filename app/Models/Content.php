<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Content extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'description', 'release_date', 'imdb_rating', 'duration',
        'type', 'saga_id', 'country', 'poster_path', 'thumbnail_path', 'video_link',
        'stream_link', 'is_uploaded',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'id'
    ];

    // Relation "many-to-one" avec Saga (un contenu peut appartenir à une saga)
    public function saga(): BelongsTo
    {
        return $this->belongsTo(Saga::class);
    }

    // Relation "one-to-many" avec ViewingHistories (contenu dans l'historique de visionnage)
    public function viewingHistories(): HasMany
    {
        return $this->hasMany(ViewingHistory::class);
    }

    // Relation "many-to-many" avec Users via la table user_content_watchlist
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_content_watchlist');
    }

    // Relation "many-to-many" avec Categories via la table pivot content_categories
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'content_categories');
    }

    // Relation "many-to-many" avec Genres via la table pivot content_genres
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'content_genres');
    }

    // Relation "one-to-many" avec Season (pour les séries uniquement)
    public function seasons(): HasMany
    {
        return $this->hasMany(Season::class, 'content_id', 'uuid');
    }

    // Relation "one-to-many" avec Likes (contenu liké par des utilisateurs)
    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    // Relation "many-to-many" avec Actors via la table pivot content_actors
    public function actors(): BelongsToMany
    {
        return $this->belongsToMany(Actor::class, 'content_actors', 'content_id', 'actor_id');
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
