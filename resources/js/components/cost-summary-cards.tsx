import { Coins, Receipt, TrendingUp } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Card } from '@/components/ui/card';
import { useMoney } from '@/hooks/use-money';
import { cn } from '@/lib/utils';

type Totals = {
    total_cost: number;
    selling_price: number | null;
    profit: number | null;
};

type Tile = {
    label: string;
    value: number;
    icon: LucideIcon;
    tone: string;
};

/**
 * Total cost, selling value and profit for an item, project or report.
 * Selling value and profit are left out when they are null.
 */
export default function CostSummaryCards({ cost }: { cost: Totals }) {
    const money = useMoney();

    const tiles: Tile[] = [
        {
            label: 'Total cost',
            value: cost.total_cost,
            icon: Receipt,
            tone: 'bg-secondary text-secondary-foreground',
        },
    ];

    if (cost.selling_price !== null) {
        tiles.push({
            label: 'Selling value',
            value: cost.selling_price,
            icon: Coins,
            tone: 'bg-zari-soft text-zari',
        });
    }

    if (cost.profit !== null) {
        tiles.push({
            label: 'Profit',
            value: cost.profit,
            icon: TrendingUp,
            tone: 'bg-positive/10 text-positive',
        });
    }

    return (
        <div className="grid gap-4 sm:grid-cols-3">
            {tiles.map((tile) => (
                <Card
                    key={tile.label}
                    className="flex-row items-center gap-4 px-5 py-4"
                >
                    <span
                        className={cn(
                            'flex size-10 shrink-0 items-center justify-center rounded-lg',
                            tile.tone,
                        )}
                    >
                        <tile.icon className="size-5" aria-hidden />
                    </span>
                    <div className="min-w-0">
                        <p className="text-sm text-muted-foreground">
                            {tile.label}
                        </p>
                        <p className="truncate text-2xl font-semibold tracking-tight tabular-nums">
                            {money(tile.value)}
                        </p>
                    </div>
                </Card>
            ))}
        </div>
    );
}
