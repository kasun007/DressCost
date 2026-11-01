import { Bar, BarChart, CartesianGrid, XAxis, YAxis } from 'recharts';
import MoneyTooltip from '@/components/charts/money-tooltip';
import type { ChartConfig } from '@/components/ui/chart';
import {
    ChartContainer,
    ChartLegend,
    ChartLegendContent,
    ChartTooltip,
} from '@/components/ui/chart';
import { useMoney } from '@/hooks/use-money';
import type { Series } from '@/lib/chart-series';

export type ComparisonRow = {
    name: string;
    a: number;
    b: number;
};

type Props = {
    rows: ComparisonRow[];
    labelA: string;
    labelB: string;
};

/** The two compared projects always take the first two chart slots. */
export const COLOR_A = 'var(--chart-1)';
export const COLOR_B = 'var(--chart-2)';

/**
 * Grouped horizontal bars: one pair per costing type, one bar per project.
 */
export default function ComparisonChart({ rows, labelA, labelB }: Props) {
    const compactMoney = useMoney({ compact: true });

    const series: Series[] = [
        { key: 'a', label: labelA, color: COLOR_A },
        { key: 'b', label: labelB, color: COLOR_B },
    ];
    const config: ChartConfig = Object.fromEntries(
        series.map((s) => [s.key, { label: s.label, color: s.color }]),
    );

    return (
        <ChartContainer
            config={config}
            className="w-full"
            style={{ height: Math.max(220, rows.length * 64 + 72) }}
        >
            <BarChart
                data={rows}
                layout="vertical"
                margin={{ top: 4, right: 12, left: 4, bottom: 0 }}
                barCategoryGap={14}
                barGap={2}
            >
                <CartesianGrid horizontal={false} strokeDasharray="3 3" />
                <XAxis
                    type="number"
                    tickFormatter={compactMoney}
                    tickLine={false}
                    axisLine={false}
                />
                <YAxis
                    type="category"
                    dataKey="name"
                    tickLine={false}
                    axisLine={false}
                    width={120}
                    tick={{ fontSize: 12 }}
                />
                <ChartTooltip
                    cursor={{ fill: 'var(--muted)', opacity: 0.6 }}
                    content={<MoneyTooltip series={series} showTotal={false} />}
                />
                <ChartLegend content={<ChartLegendContent />} />
                {series.map((s) => (
                    <Bar
                        key={s.key}
                        dataKey={s.key}
                        name={s.key}
                        fill={s.color}
                        radius={[0, 4, 4, 0]}
                        maxBarSize={20}
                    />
                ))}
            </BarChart>
        </ChartContainer>
    );
}
