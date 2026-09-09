<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class ProductType extends Model
{
    protected $table = 'product_types';

    protected $fillable = [
        'name',
        'code',
        'color',
        'icon',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'product_type', 'code');
    }
}
