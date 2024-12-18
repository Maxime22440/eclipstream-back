<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Saga extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description',
    ];

    protected $hidden = [
        'id',
    ];

    // Relation "one-to-many" avec Content (une saga contient plusieurs films)
    public function contents(): HasMany
    {
        return $this->hasMany(Content::class);
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
