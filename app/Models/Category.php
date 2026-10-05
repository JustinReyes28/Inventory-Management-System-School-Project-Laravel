<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $category_name
 */
class Category extends Model
{
    protected $table = 'categories';

    protected $fillable = [
        'category_name',
    ];

    /**
     * The Item model's global scope intentionally excludes archived items.
     *
     * @return HasMany<Item, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'category_id');
    }
}
