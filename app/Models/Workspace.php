<?php

namespace App\Models;

use Database\Factories\WorkspaceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The top level: a workspace (e.g. "2026 Fashion") holds projects, which hold items.
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Project> $projects
 */
#[Fillable(['name', 'description'])]
class Workspace extends Model
{
    /** @use HasFactory<WorkspaceFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * @return HasManyThrough<Item, Project, $this>
     */
    public function items(): HasManyThrough
    {
        return $this->hasManyThrough(Item::class, Project::class);
    }

    /**
     * Delete the workspace with its projects and their items.
     *
     * The rows are removed explicitly, items first, because MySQL does not
     * reliably carry a cascade more than one level deep (workspace → project
     * → item → item lines) and would leave orphaned items behind.
     */
    public function deleteWithContents(): void
    {
        DB::transaction(function () {
            $projectIds = $this->projects()->pluck('id');

            Item::whereIn('project_id', $projectIds)->delete();
            Project::whereIn('id', $projectIds)->delete();
            $this->delete();
        });
    }
}
