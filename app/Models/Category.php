<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'photo',
        'name',
        'slug',
        'description',
        'is_active',
    ];

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}
