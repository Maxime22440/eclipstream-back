<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserContentWatchlist extends Model
{
    use HasFactory;

    protected $table = 'user_content_watchlist';

    protected $fillable = [
        'user_id', 'content_id',
    ];
}
