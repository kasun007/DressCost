<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * A project within a workspace, holding the items being costed.
 *
 * @property int $id
 * @property int $user_id
 * @property int $workspace_id
 * @property string $name
 * @property string|null $description
 * @property string|null $share_token secret for the read-only share link; null when not shared
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Workspace $workspace
 * @property-read Collection<int, Item> $items
 */
#[Fillable(['workspace_id', 'name', 'description'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return HasMany<Item, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    /**
     * Create a new share link, replacing (and disabling) any previous one.
     */
    public function share(): void
    {
        $this->forceFill(['share_token' => Str::random(48)])->save();
    }

    /**
     * Disable the share link.
     */
    public function stopSharing(): void
    {
        $this->forceFill(['share_token' => null])->save();
    }

    /**
     * Delete the project with its items. Items are removed explicitly first;
     * see Workspace::deleteWithContents() for why.
     */
    public function deleteWithItems(): void
    {
        DB::transaction(function () {
            $this->items()->delete();
            $this->delete();
        });
    }
}
