<?php

namespace App\Models;

use Database\Factories\HelpArticleCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HelpArticleCategory extends Model
{
    /** @use HasFactory<HelpArticleCategoryFactory> */
    use HasFactory;

    protected $fillable = [

        'category_name',
        'slug',
        'sort_order',
        'is_active',

    ];

    protected function casts(): array
    {

        return [

            'created_at' => 'date:Y-m-d',
            'updated_at' => 'date:Y-m-d',

        ];
    }
}
