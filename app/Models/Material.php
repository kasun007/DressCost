<?php

namespace App\Models;

use App\Enums\MaterialUnit;
use Database\Factories\MaterialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property MaterialUnit $unit
 * @property float $unit_price
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ItemMaterial $pivot
 */
#[Fillable(['name', 'unit', 'unit_price'])]
class Material extends Model
{
    /** @use HasFactory<MaterialFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<Item, $this, ItemMaterial>
     */
    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class)
            ->using(ItemMaterial::class)
            ->withPivot('quantity')
            ->withTimestamps();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit' => MaterialUnit::class,
            'unit_price' => 'float',
        ];
    }
}
