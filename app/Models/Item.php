<?php

namespace App\Models;

use App\Enums\ItemType;
use Database\Factories\ItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $project_id
 * @property string $name
 * @property ItemType $type
 * @property float $profit_margin
 * @property int $quantity_small
 * @property int $quantity_medium
 * @property int $quantity_large
 * @property int $quantity_xl
 * @property Carbon $created_at when the item was saved; dates its costs
 * @property Carbon|null $updated_at
 * @property-read Project $project
 * @property-read Collection<int, Material> $materials
 * @property-read Collection<int, ItemCost> $costs
 */
#[Fillable(['name', 'type', 'profit_margin', 'quantity_small', 'quantity_medium', 'quantity_large', 'quantity_xl'])]
class Item extends Model
{
    /** @use HasFactory<ItemFactory> */
    use HasFactory;

    /**
     * The sizes an item is made in: size key => label. Every size costs the
     * same per piece; each has its own quantity column, e.g. quantity_small.
     */
    public const SIZES = [
        'small' => 'Small',
        'medium' => 'Medium',
        'large' => 'Large',
        'xl' => 'XL',
    ];

    /**
     * The model's default attribute values.
     *
     * @var array<string, int>
     */
    protected $attributes = [
        'quantity_small' => 0,
        'quantity_medium' => 0,
        'quantity_large' => 0,
        'quantity_xl' => 0,
    ];

    /**
     * How many pieces to make in each size, e.g. ['small' => 10, ...].
     *
     * @return array<string, int>
     */
    public function quantities(): array
    {
        $quantities = [];

        foreach (array_keys(self::SIZES) as $size) {
            $quantities[$size] = (int) $this->getAttribute("quantity_{$size}");
        }

        return $quantities;
    }

    /**
     * The total number of pieces to make, across all sizes.
     */
    public function quantity(): int
    {
        return array_sum($this->quantities());
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsToMany<Material, $this, ItemMaterial>
     */
    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(Material::class)
            ->using(ItemMaterial::class)
            ->withPivot('quantity')
            ->withTimestamps();
    }

    /**
     * Non-material costs such as labour or embroidery.
     *
     * @return HasMany<ItemCost, $this>
     */
    public function costs(): HasMany
    {
        return $this->hasMany(ItemCost::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ItemType::class,
            'profit_margin' => 'float',
            'quantity_small' => 'integer',
            'quantity_medium' => 'integer',
            'quantity_large' => 'integer',
            'quantity_xl' => 'integer',
        ];
    }
}
