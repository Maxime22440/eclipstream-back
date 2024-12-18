<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Genre extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description',
    ];

    // Relation "many-to-many" avec Content via la table pivot content_genres
    public function contents(): BelongsToMany
    {
        return $this->belongsToMany(Content::class, 'content_genres');
    }
}
