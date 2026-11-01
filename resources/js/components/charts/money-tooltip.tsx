import type { TooltipContentProps } from 'recharts';
import { useMoney } from '@/hooks/use-money';
import type { Series } from '@/lib/chart-series';

type Props = Partial<TooltipContentProps<number, string>> & {
    series: Series[];
    formatLabel?: (label: string) => string;
    /** Off when the series are alternatives (compare), not parts of a whole. */
    showTotal?: boolean;
};

/**
 * Chart tooltip that lists each series' amount, largest first, with a total.
 */
export default function MoneyTooltip({
    active,
    payload,
    label,
    series,
    formatLabel = (value) => value,
    showTotal = true,
}: Props) {
    const money = useMoney();

    if (!active || !payload?.length) {
        return null;
    }

    const rows = payload
        .map((entry) => ({
            series: series.find((s) => s.key === entry.dataKey),
            value: Number(entry.value ?? 0),
        }))
        .filter((row) => row.series && row.value > 0)
        .sort((a, b) => b.value - a.value);

    const total = rows.reduce((sum, row) => sum + row.value, 0);

    return (
        <div className="grid min-w-48 gap-1.5 rounded-lg border bg-popover px-3 py-2 text-xs shadow-lg">
            <p className="font-semibold text-foreground">
                {formatLabel(String(label))}
            </p>
            {rows.length === 0 && (
                <p className="text-muted-foreground">No costs</p>
            )}
            {rows.map((row) => (
                <div key={row.series!.key} className="flex items-center gap-2">
                    <span
                        className="size-2.5 shrink-0 rounded-sm"
                        style={{ backgroundColor: row.series!.color }}
                    />
                    <span className="flex-1 text-muted-foreground">
                        {row.series!.label}
                    </span>
                    <span className="font-medium text-foreground tabular-nums">
                        {money(row.value)}
                    </span>
                </div>
            ))}
            {showTotal && rows.length > 1 && (
                <div className="flex justify-between border-t pt-1.5 font-semibold text-foreground">
                    <span>Total</span>
                    <span className="tabular-nums">{money(total)}</span>
                </div>
            )}
        </div>
    );
}
