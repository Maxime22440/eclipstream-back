<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Like extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'content_id',
    ];

    // Relation "many-to-one" avec User
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Relation "many-to-one" avec Content
    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }
}
