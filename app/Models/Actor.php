<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Actor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'birth_date',
    ];

    protected $hidden = [
        'id',
    ];

    // Relation many-to-many avec le modèle Content
    public function contents(): BelongsToMany
    {
        return $this->belongsToMany(Content::class, 'content_actors', 'actor_id', 'content_id');
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
