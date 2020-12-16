<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Article extends Model
{
    protected $fillable = [
        'url',
    ];

    protected $casts = [
        "published_at"=>"datetime"
    ];
}
