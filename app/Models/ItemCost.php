<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A non-material cost on an item, such as labour or embroidery.
 *
 * @property int $id
 * @property int $item_id
 * @property string $name
 * @property float $amount
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'amount'])]
class ItemCost extends Model
{
    /**
     * Costing types offered even before the user has entered any.
     */
    public const DEFAULT_TYPES = ['Labour', 'Overhead'];

    /**
     * The default costing types plus every type the user has used,
     * without case-insensitive duplicates.
     *
     * @return array<int, string>
     */
    public static function typesFor(User $user): array
    {
        $used = static::query()
            ->whereHas('item.project', fn ($query) => $query->where('user_id', $user->id))
            ->select('name')
            ->distinct()
            ->orderBy('name')
            ->get()
            ->map(fn (ItemCost $cost) => $cost->name);

        return collect(self::DEFAULT_TYPES)
            ->merge($used)
            ->unique(fn (string $name) => mb_strtolower($name))
            ->values()
            ->all();
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'float',
        ];
    }
}
