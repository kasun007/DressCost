<?php

namespace Database\Seeders;

use App\Enums\MaterialUnit;
use App\Models\Material;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * A workspace with one project whose items were saved across 2024, 2025 and
 * 2026, for trying out reports over several years. Prices rise each year.
 *
 * Run for every account with: php artisan db:seed --class=MultiYearDemoSeeder
 * Re-running replaces the "Multi-year test" workspace and nothing else.
 */
class MultiYearDemoSeeder extends Seeder
{
    public const WORKSPACE = 'Multi-year test';

    public const PROJECT = 'Staff uniform orders';

    /**
     * Materials the items use, created if the account doesn't have them.
     *
     * @var array<string, array{0: MaterialUnit, 1: int|float}>
     */
    private const MATERIALS = [
        'Cotton poplin' => [MaterialUnit::Metre, 650],
        'Linen blend' => [MaterialUnit::Metre, 1450],
        'Satin lining' => [MaterialUnit::Metre, 520],
        'Fusible interfacing' => [MaterialUnit::Metre, 300],
        'Invisible zip' => [MaterialUnit::Piece, 120],
        'Pearl buttons' => [MaterialUnit::Piece, 35],
        'Thread spool' => [MaterialUnit::Roll, 180],
    ];

    /**
     * The same three garments each year, with labour and overhead rising.
     * Each: [saved at, name, type, margin %, materials (name => qty), costs (type => LKR), pieces [S, M, L, XL]].
     *
     * @var array<int, array{0: string, 1: string, 2: string, 3: int|float, 4: array<string, int|float>, 5: array<string, int|float>, 6: array{int, int, int, int}}>
     */
    private const ITEMS = [
        ['2024-02-15 10:00', 'Staff shirt dress', 'dress', 30, ['Cotton poplin' => 2.6, 'Pearl buttons' => 8, 'Thread spool' => 1], ['Labour' => 2200, 'Overhead' => 400], [10, 25, 20, 8]],
        ['2024-06-10 10:00', 'Uniform blouse', 'top', 30, ['Cotton poplin' => 1.6, 'Pearl buttons' => 6, 'Fusible interfacing' => 0.3], ['Labour' => 1500, 'Overhead' => 300], [12, 25, 18, 6]],
        ['2024-10-05 10:00', 'Uniform A-line skirt', 'skirt', 30, ['Linen blend' => 1.8, 'Satin lining' => 1.0, 'Invisible zip' => 1], ['Labour' => 1600, 'Overhead' => 300], [10, 20, 15, 5]],
        ['2025-02-12 10:00', 'Staff shirt dress', 'dress', 32, ['Cotton poplin' => 2.6, 'Pearl buttons' => 8, 'Thread spool' => 1], ['Labour' => 2500, 'Overhead' => 450, 'Transport' => 300], [10, 25, 20, 8]],
        ['2025-06-08 10:00', 'Uniform blouse', 'top', 32, ['Cotton poplin' => 1.6, 'Pearl buttons' => 6, 'Fusible interfacing' => 0.3], ['Labour' => 1700, 'Overhead' => 350], [12, 25, 18, 6]],
        ['2025-10-03 10:00', 'Uniform A-line skirt', 'skirt', 32, ['Linen blend' => 1.8, 'Satin lining' => 1.0, 'Invisible zip' => 1], ['Labour' => 1800, 'Overhead' => 350, 'Pattern making' => 600], [10, 20, 15, 5]],
        ['2026-02-14 10:00', 'Staff shirt dress', 'dress', 35, ['Cotton poplin' => 2.6, 'Pearl buttons' => 8, 'Thread spool' => 1], ['Labour' => 2800, 'Overhead' => 500, 'Transport' => 350, 'Embroidery' => 900], [10, 25, 20, 8]],
        ['2026-06-09 10:00', 'Uniform blouse', 'top', 35, ['Cotton poplin' => 1.6, 'Pearl buttons' => 6, 'Fusible interfacing' => 0.3], ['Labour' => 1900, 'Overhead' => 400, 'Embroidery' => 700], [12, 25, 18, 6]],
        ['2026-10-02 10:00', 'Uniform A-line skirt', 'skirt', 35, ['Linen blend' => 1.8, 'Satin lining' => 1.0, 'Invisible zip' => 1], ['Labour' => 2000, 'Overhead' => 400], [10, 20, 15, 5]],
    ];

    /**
     * Add the multi-year workspace to every account.
     */
    public function run(): void
    {
        User::all()->each(fn (User $user) => $this->seedFor($user));
    }

    /**
     * Replace the user's "Multi-year test" workspace with fresh data.
     */
    public function seedFor(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->workspaces()->where('name', self::WORKSPACE)->get()->each->deleteWithContents();

            $materials = collect(self::MATERIALS)->map(
                fn (array $material, string $name) => $user->materials()->firstOrCreate(
                    ['name' => $name],
                    ['unit' => $material[0], 'unit_price' => $material[1]],
                ),
            );

            $workspace = $user->workspaces()->create([
                'name' => self::WORKSPACE,
                'description' => 'Uniform orders repeated over three years, for comparing costs by year.',
            ]);

            $project = $user->projects()->create([
                'workspace_id' => $workspace->id,
                'name' => self::PROJECT,
                'description' => 'The same three garments ordered every year from 2024 to 2026.',
            ]);

            foreach (self::ITEMS as [$savedAt, $name, $type, $margin, $itemMaterials, $costs, $sizes]) {
                $date = Carbon::parse($savedAt);

                // Items are dated by when they were saved, so set that explicitly.
                $item = $project->items()->make([
                    'name' => $name,
                    'type' => $type,
                    'profit_margin' => $margin,
                    'quantity_small' => $sizes[0],
                    'quantity_medium' => $sizes[1],
                    'quantity_large' => $sizes[2],
                    'quantity_xl' => $sizes[3],
                ]);
                $item->created_at = $date;
                $item->updated_at = $date;
                $item->save();

                $item->materials()->sync(collect($itemMaterials)->mapWithKeys(
                    fn (int|float $quantity, string $material) => [
                        $this->material($materials, $material)->id => ['quantity' => $quantity],
                    ],
                )->all());

                $item->costs()->createMany(collect($costs)->map(
                    fn (int|float $amount, string $costType) => ['name' => $costType, 'amount' => $amount],
                )->values()->all());
            }
        });
    }

    /**
     * @param  Collection<string, Material>  $materials
     */
    private function material(Collection $materials, string $name): Material
    {
        return $materials->get($name) ?? throw new \InvalidArgumentException("Unknown material [{$name}].");
    }
}
