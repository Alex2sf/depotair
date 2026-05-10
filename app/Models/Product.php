<?php

namespace App\Models;
use App\Traits\LogsActivity;
use App\Enums\ProductType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    protected $fillable = [
        'name',
        'sku',
        'image_url',
        'product_type',
        'unit',
        'price',
        'cogs',
        'description',
        'is_enabled'
    ];

    /**
     * Get the image_url attribute
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value 
                ? (filter_var($value, FILTER_VALIDATE_URL) ? $value : asset('storage/' . $value)) 
                : null,
        );
    }

    protected $casts = [
        'price' => 'integer',
        'cogs' => 'integer',

        'is_enabled' => 'boolean',
        'product_type' => ProductType::class,
    ];

    /**
     * Mendapatkan data inventaris (stok) untuk Product ini.
     */
    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class);
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class)->latest();
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class)
            ->using(OrderProduct::class)
            ->withPivot(...OrderProduct::PIVOT_COLUMNS)
            ->withTimestamps();
    }
}
