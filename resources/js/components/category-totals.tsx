import { useMoney } from '@/hooks/use-money';
import { buildSeries } from '@/lib/chart-series';
import type { CategoryTotal } from '@/types';

type Props = {
    totals: CategoryTotal[];
    /** Every costing type in fixed order, so colours match the charts. */
    categories: string[];
};

/**
 * Costing types ranked by amount, with each one's share of the total.
 * Doubles as the table view of the charts' category colours.
 */
export default function CategoryTotals({ totals, categories }: Props) {
    const money = useMoney();
    const { keyOf, series } = buildSeries(categories);
    const grandTotal = totals.reduce((sum, row) => sum + row.amount, 0);

    if (totals.length === 0) {
        return (
            <p className="py-6 text-center text-sm text-muted-foreground">
                No costs in this period.
            </p>
        );
    }

    return (
        <ul className="space-y-3">
            {totals.map((row) => {
                const color = series.find(
                    (s) => s.key === keyOf(row.name),
                )?.color;
                const share = grandTotal > 0 ? row.amount / grandTotal : 0;

                return (
                    <li key={row.name} className="space-y-1.5">
                        <div className="flex items-baseline gap-2 text-sm">
                            <span
                                className="size-2.5 shrink-0 self-center rounded-sm"
                                style={{ backgroundColor: color }}
                                aria-hidden
                            />
                            <span className="flex-1 truncate">{row.name}</span>
                            <span className="font-medium tabular-nums">
                                {money(row.amount)}
                            </span>
                            <span className="w-12 text-right text-xs text-muted-foreground tabular-nums">
                                {Math.round(share * 100)}%
                            </span>
                        </div>
                        <div className="h-1.5 overflow-hidden rounded-full bg-muted">
                            <div
                                className="h-full rounded-full"
                                style={{
                                    width: `${share * 100}%`,
                                    backgroundColor: color,
                                }}
                            />
                        </div>
                    </li>
                );
            })}
        </ul>
    );
}
