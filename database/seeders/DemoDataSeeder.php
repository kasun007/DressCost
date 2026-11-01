<?php

namespace Database\Seeders;

use App\Enums\MaterialUnit;
use App\Models\Material;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Realistic sample data for a small dressmaking studio, spread over a year.
 *
 * Log in as demo@dresscost.test / password. Re-running the seeder resets
 * that account's data and leaves every other account untouched.
 * seedFor() loads the same data into any account.
 *
 * @phpstan-type DemoItem array{0: string, 1: string, 2: int|float, 3: array<string, int|float>, 4: array<string, int|float>, 5: array{int, int, int, int}}
 */
class DemoDataSeeder extends Seeder
{
    public const EMAIL = 'demo@dresscost.test';

    /**
     * Name => [unit, price per unit in LKR].
     *
     * @var array<string, array{0: MaterialUnit, 1: int|float}>
     */
    private const MATERIALS = [
        'Cotton poplin' => [MaterialUnit::Metre, 650],
        'Linen blend' => [MaterialUnit::Metre, 1450],
        'Silk satin' => [MaterialUnit::Metre, 3200],
        'Chiffon' => [MaterialUnit::Metre, 980],
        'Batik cotton' => [MaterialUnit::Metre, 1100],
        'Satin lining' => [MaterialUnit::Metre, 520],
        'Lace trim' => [MaterialUnit::Metre, 420],
        'Fusible interfacing' => [MaterialUnit::Metre, 300],
        'Invisible zip' => [MaterialUnit::Piece, 120],
        'Pearl buttons' => [MaterialUnit::Piece, 35],
        'Sequin pack' => [MaterialUnit::Piece, 450],
        'Thread spool' => [MaterialUnit::Roll, 180],
        'Elastic' => [MaterialUnit::Roll, 250],
    ];

    /**
     * Workspaces hold projects, which hold items.
     * Each item: [name, type, margin %, materials (name => qty), costs (type => LKR), pieces [S, M, L, XL]].
     *
     * @var array<int, array{name: string, description: string, projects: array<int, array{name: string, description: string, items: array<int, DemoItem>}>}>
     */
    private const WORKSPACES = [
        [
            'name' => '2025 Fashion',
            'description' => 'Ready-to-wear collections for the 2025 season.',
            'projects' => [
                [
                    'name' => 'Summer linen line',
                    'description' => 'Breathable linen pieces for the hot season, sold through the studio and two boutiques.',
                    'items' => [
                        ['Linen shirt dress', 'dress', 35, ['Linen blend' => 2.8, 'Pearl buttons' => 10, 'Fusible interfacing' => 0.5, 'Thread spool' => 1], ['Labour' => 3200, 'Overhead' => 600, 'Pattern making' => 1500], [8, 15, 12, 5]],
                        ['Wide-leg linen trousers', 'trousers', 30, ['Linen blend' => 2.2, 'Invisible zip' => 1, 'Elastic' => 1, 'Thread spool' => 1], ['Labour' => 2400, 'Overhead' => 450], [6, 12, 10, 4]],
                        ['Linen wrap top', 'top', 30, ['Linen blend' => 1.6, 'Thread spool' => 1], ['Labour' => 1800, 'Overhead' => 350, 'Transport' => 300], [10, 18, 12, 6]],
                        ['Linen midi skirt', 'skirt', 32, ['Linen blend' => 2.0, 'Satin lining' => 1.2, 'Invisible zip' => 1], ['Labour' => 2000, 'Overhead' => 400], [6, 12, 10, 4]],
                    ],
                ],
            ],
        ],
        [
            'name' => '2026 Fashion',
            'description' => 'Ready-to-wear collections for the 2026 season.',
            'projects' => [
                [
                    'name' => 'Avurudu festive collection',
                    'description' => 'Batik and embroidered outfits for the Sinhala and Tamil New Year.',
                    'items' => [
                        ['Batik A-line frock', 'dress', 40, ['Batik cotton' => 3.0, 'Lace trim' => 2, 'Invisible zip' => 1, 'Thread spool' => 1], ['Labour' => 2600, 'Overhead' => 450, 'Dyeing' => 1200], [10, 20, 15, 5]],
                        ["Kids' batik frock", 'dress', 40, ['Batik cotton' => 1.5, 'Lace trim' => 1.5, 'Pearl buttons' => 4], ['Labour' => 1500, 'Overhead' => 300, 'Dyeing' => 700], [15, 15, 0, 0]],
                        ['Batik sarong skirt', 'skirt', 35, ['Batik cotton' => 2.2, 'Elastic' => 1], ['Labour' => 1200, 'Overhead' => 250, 'Dyeing' => 900], [8, 14, 10, 4]],
                        ['Embroidered kurta top', 'top', 38, ['Cotton poplin' => 2.0, 'Thread spool' => 2], ['Labour' => 2200, 'Overhead' => 400, 'Embroidery' => 2500], [6, 12, 10, 4]],
                        ['Festive chiffon maxi dress', 'dress', 40, ['Chiffon' => 4.0, 'Satin lining' => 3.0, 'Invisible zip' => 1], ['Labour' => 3500, 'Overhead' => 600, 'Embroidery' => 1800, 'Transport' => 400], [4, 8, 6, 2]],
                    ],
                ],
                [
                    'name' => "Vesak charity kids' wear",
                    'description' => "White outfits for a children's home, priced close to cost.",
                    'items' => [
                        ["Girls' white frock (age 4)", 'dress', 5, ['Cotton poplin' => 1.4, 'Lace trim' => 1, 'Pearl buttons' => 3], ['Labour' => 900, 'Overhead' => 200], [20, 0, 0, 0]],
                        ["Girls' white frock (age 8)", 'dress', 5, ['Cotton poplin' => 1.9, 'Lace trim' => 1.2, 'Pearl buttons' => 4], ['Labour' => 1100, 'Overhead' => 220], [0, 20, 0, 0]],
                        ["Boys' white shirt", 'top', 5, ['Cotton poplin' => 1.3, 'Pearl buttons' => 6, 'Fusible interfacing' => 0.3], ['Labour' => 950, 'Overhead' => 200, 'Transport' => 500], [10, 10, 0, 0]],
                    ],
                ],
                [
                    'name' => 'Office wear capsule',
                    'description' => 'Mix-and-match workwear made to order for corporate clients.',
                    'items' => [
                        ['Tailored sheath dress', 'dress', 35, ['Linen blend' => 2.4, 'Satin lining' => 1.8, 'Invisible zip' => 1, 'Fusible interfacing' => 0.6], ['Labour' => 3800, 'Overhead' => 650, 'Pattern making' => 1800], [5, 10, 8, 3]],
                        ['Pleated midi skirt', 'skirt', 32, ['Cotton poplin' => 2.5, 'Invisible zip' => 1, 'Fusible interfacing' => 0.3], ['Labour' => 2400, 'Overhead' => 450], [5, 10, 8, 3]],
                        ['Silk button-down blouse', 'top', 38, ['Silk satin' => 1.8, 'Pearl buttons' => 8], ['Labour' => 2800, 'Overhead' => 500], [4, 8, 6, 2]],
                        ['Cigarette trousers', 'trousers', 32, ['Linen blend' => 2.1, 'Invisible zip' => 1, 'Fusible interfacing' => 0.4], ['Labour' => 2600, 'Overhead' => 450, 'Pattern making' => 1200], [4, 8, 6, 2]],
                    ],
                ],
                [
                    'name' => 'Summer collection',
                    'description' => 'Light resort wear for December holidays.',
                    'items' => [
                        ['Resort maxi dress', 'dress', 38, ['Batik cotton' => 3.5, 'Elastic' => 1, 'Thread spool' => 1], ['Labour' => 2800, 'Overhead' => 450, 'Dyeing' => 1100], [6, 12, 10, 4]],
                        ['Linen co-ord set', 'top', 35, ['Linen blend' => 3.2, 'Invisible zip' => 1, 'Fusible interfacing' => 0.4], ['Labour' => 3400, 'Overhead' => 550, 'Pattern making' => 1500], [5, 10, 8, 3]],
                        ['Cotton kaftan', 'dress', 35, ['Cotton poplin' => 2.8, 'Lace trim' => 1.5], ['Labour' => 1600, 'Overhead' => 300, 'Embroidery' => 900], [6, 12, 10, 6]],
                    ],
                ],
                [
                    'name' => 'Christmas party edit',
                    'description' => 'Evening and party wear for the December season.',
                    'items' => [
                        ['Sequinned slip dress', 'dress', 45, ['Silk satin' => 2.8, 'Satin lining' => 2.0, 'Sequin pack' => 2, 'Invisible zip' => 1], ['Labour' => 4200, 'Overhead' => 700, 'Beading' => 3500], [3, 6, 5, 2]],
                        ['Chiffon party dress', 'dress', 40, ['Chiffon' => 3.5, 'Satin lining' => 2.5, 'Lace trim' => 3], ['Labour' => 3600, 'Overhead' => 600, 'Embroidery' => 2000], [4, 8, 6, 2]],
                        ["Kids' party frock", 'dress', 38, ['Silk satin' => 1.3, 'Lace trim' => 1.5, 'Pearl buttons' => 4], ['Labour' => 1800, 'Overhead' => 300, 'Transport' => 350], [8, 8, 0, 0]],
                    ],
                ],
            ],
        ],
        [
            'name' => 'Bespoke orders',
            'description' => 'One-off orders made for individual customers.',
            'projects' => [
                [
                    'name' => 'Perera wedding party',
                    'description' => 'Bridal gown, bridesmaids and family outfits for a July wedding in Kandy.',
                    'items' => [
                        ['Bridal gown', 'dress', 45, ['Silk satin' => 6.0, 'Satin lining' => 4.0, 'Lace trim' => 8, 'Sequin pack' => 3, 'Invisible zip' => 1, 'Fusible interfacing' => 1.5], ['Labour' => 18000, 'Overhead' => 2500, 'Pattern making' => 4000, 'Embroidery' => 9500, 'Beading' => 12000], [0, 1, 0, 0]],
                        ['Bridesmaid dress (Nadeesha)', 'dress', 35, ['Chiffon' => 4.5, 'Satin lining' => 2.5, 'Invisible zip' => 1], ['Labour' => 4800, 'Overhead' => 700, 'Beading' => 2200], [0, 1, 0, 0]],
                        ['Bridesmaid dress (Ishara)', 'dress', 35, ['Chiffon' => 4.2, 'Satin lining' => 2.4, 'Invisible zip' => 1], ['Labour' => 4800, 'Overhead' => 700, 'Beading' => 2200], [1, 0, 0, 0]],
                        ['Flower girl dress', 'dress', 35, ['Silk satin' => 1.5, 'Lace trim' => 2, 'Pearl buttons' => 6], ['Labour' => 2500, 'Overhead' => 400, 'Embroidery' => 1200], [1, 0, 0, 0]],

                        ['Saree blouse (mother of the bride)', 'top', 40, ['Silk satin' => 1.2, 'Satin lining' => 1.0, 'Sequin pack' => 1], ['Labour' => 3500, 'Overhead' => 500, 'Beading' => 3000, 'Transport' => 600], [0, 0, 1, 0]],
                    ],
                ],
            ],
        ],
    ];

    /**
     * Seed the demo account.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => self::EMAIL],
            ['name' => 'Demo Studio', 'password' => 'password', 'email_verified_at' => now()],
        );

        $this->seedFor($user);
    }

    /**
     * Replace a user's workspaces, projects, items and materials with the demo data.
     */
    public function seedFor(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->workspaces->each->deleteWithContents();
            $user->materials()->delete();

            $materials = collect(self::MATERIALS)->map(
                fn (array $material, string $name) => $user->materials()->create([
                    'name' => $name,
                    'unit' => $material[0],
                    'unit_price' => $material[1],
                ]),
            );

            foreach (self::WORKSPACES as $workspaceData) {
                $workspace = $user->workspaces()->create([
                    'name' => $workspaceData['name'],
                    'description' => $workspaceData['description'],
                ]);

                foreach ($workspaceData['projects'] as $projectData) {
                    $this->createProject($user, $workspace, $materials, $projectData);
                }
            }
        });
    }

    /**
     * Create a project in a workspace, with its items.
     *
     * @param  Collection<string, Material>  $materials
     * @param  array{name: string, description: string, items: array<int, DemoItem>}  $data
     */
    private function createProject(User $user, Workspace $workspace, Collection $materials, array $data): void
    {
        $project = $user->projects()->create([
            'workspace_id' => $workspace->id,
            'name' => $data['name'],
            'description' => $data['description'],
        ]);

        foreach ($data['items'] as [$name, $type, $margin, $itemMaterials, $costs, $sizes]) {
            $item = $project->items()->create([
                'name' => $name,
                'type' => $type,
                'profit_margin' => $margin,
                'quantity_small' => $sizes[0],
                'quantity_medium' => $sizes[1],
                'quantity_large' => $sizes[2],
                'quantity_xl' => $sizes[3],
            ]);

            $item->materials()->sync(collect($itemMaterials)->mapWithKeys(
                fn (int|float $quantity, string $material) => [
                    $this->material($materials, $material)->id => ['quantity' => $quantity],
                ],
            )->all());

            $item->costs()->createMany(collect($costs)->map(
                fn (int|float $amount, string $type) => ['name' => $type, 'amount' => $amount],
            )->values()->all());
        }

    }

    /**
     * @param  Collection<string, Material>  $materials
     */
    private function material(Collection $materials, string $name): Material
    {
        return $materials->get($name) ?? throw new \InvalidArgumentException("Unknown demo material [{$name}].");
    }
}
