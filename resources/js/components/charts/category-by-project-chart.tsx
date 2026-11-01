import { Bar, BarChart, CartesianGrid, XAxis, YAxis } from 'recharts';
import MoneyTooltip from '@/components/charts/money-tooltip';
import {
    ChartContainer,
    ChartLegend,
    ChartLegendContent,
    ChartTooltip,
} from '@/components/ui/chart';
import { useMoney } from '@/hooks/use-money';
import { buildSeries } from '@/lib/chart-series';
import type { CategoryTotal } from '@/types';

type Props = {
    projects: { name: string; categories: CategoryTotal[] }[];
    categories: string[];
};

/**
 * One horizontal stacked bar per project, split into costing types, so the
 * mix of costs can be compared across projects.
 */
export default function CategoryByProjectChart({
    projects,
    categories,
}: Props) {
    const compactMoney = useMoney({ compact: true });
    const { series, keyOf, config } = buildSeries(categories);

    const data = projects.map((project) => {
        const row: Record<string, number | string> = { name: project.name };

        series.forEach((s) => (row[s.key] = 0));
        project.categories.forEach(({ name, amount }) => {
            const key = keyOf(name);
            row[key] = Number(row[key] ?? 0) + amount;
        });

        return row;
    });

    const visible = series.filter((s) =>
        data.some((row) => Number(row[s.key]) > 0),
    );

    if (data.length === 0) {
        return (
            <p className="py-12 text-center text-sm text-muted-foreground">
                No costs to compare.
            </p>
        );
    }

    return (
        <ChartContainer
            config={config}
            className="w-full"
            style={{ height: Math.max(180, data.length * 52 + 72) }}
        >
            <BarChart
                data={data}
                layout="vertical"
                margin={{ top: 4, right: 12, left: 4, bottom: 0 }}
                barCategoryGap={10}
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
                    width={170}
                    tick={{ fontSize: 12 }}
                />
                <ChartTooltip
                    cursor={{ fill: 'var(--muted)', opacity: 0.6 }}
                    content={<MoneyTooltip series={visible} />}
                />
                <ChartLegend content={<ChartLegendContent />} />
                {visible.map((s) => (
                    <Bar
                        key={s.key}
                        dataKey={s.key}
                        name={s.key}
                        stackId="costs"
                        fill={s.color}
                        stroke="var(--card)"
                        strokeWidth={2}
                    />
                ))}
            </BarChart>
        </ChartContainer>
    );
}
