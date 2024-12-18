<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Request extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'content_title', 'content_type',
    ];

    // Relation "many-to-one" avec User
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
