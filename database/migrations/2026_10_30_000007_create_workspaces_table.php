<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces the project tree with two fixed levels: workspace → projects → items.
 *
 * Existing data is converted: each top-level project becomes a workspace, and
 * every project that holds items (or has nothing below it) moves into its
 * top-level project's workspace, named by its path below it, e.g.
 * "October term › Summer collection". Empty grouping projects are removed.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('workspace_id')->nullable()->after('user_id')
                ->constrained()->cascadeOnDelete();
        });

        $this->convertTreesToWorkspaces();

        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('workspace_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations. Projects keep their workspace's projects, but
     * the original nesting can't be restored.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('user_id')
                ->constrained('projects')->cascadeOnDelete();
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('workspace_id');
        });

        Schema::dropIfExists('workspaces');
    }

    private function convertTreesToWorkspaces(): void
    {
        $projects = DB::table('projects')->get()->keyBy('id');
        $childIds = $projects->groupBy('parent_id')->map->pluck('id');
        $withItems = DB::table('items')->distinct()->pluck('project_id')->flip();
        $groupsToDelete = [];

        foreach ($projects->whereNull('parent_id') as $root) {
            $workspaceId = DB::table('workspaces')->insertGetId([
                'user_id' => $root->user_id,
                'name' => $root->name,
                'description' => $root->description,
                'created_at' => $root->created_at,
                'updated_at' => $root->updated_at,
            ]);

            // Walk the tree below the root, remembering each project's path.
            $stack = [[$root->id, []]];

            while ($stack !== []) {
                [$id, $path] = array_pop($stack);
                $children = $childIds->get($id, collect());
                $keep = $children->isEmpty() || $withItems->has($id);

                if ($keep) {
                    DB::table('projects')->where('id', $id)->update([
                        'workspace_id' => $workspaceId,
                        'name' => $path === [] ? $projects[$id]->name : implode(' › ', $path),
                    ]);
                } else {
                    $groupsToDelete[] = $id;
                }

                foreach ($children as $childId) {
                    $stack[] = [$childId, [...$path, $projects[$childId]->name]];
                }
            }
        }

        // Detach everything first so deleting a group doesn't cascade to its children.
        DB::table('projects')->update(['parent_id' => null]);
        DB::table('projects')->whereIn('id', $groupsToDelete)->delete();
    }
};
