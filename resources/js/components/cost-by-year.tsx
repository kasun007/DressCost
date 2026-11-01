import {
    Table,
    TableBody,
    TableCell,
    TableFooter,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useMoney } from '@/hooks/use-money';
import { cn } from '@/lib/utils';
import type { YearTotal } from '@/types';

/**
 * Items and total cost per year the items were saved, with the change from
 * the year before, so rising or falling costs are easy to spot.
 */
export default function CostByYear({ years }: { years: YearTotal[] }) {
    const money = useMoney();

    if (years.length === 0) {
        return (
            <p className="py-6 text-center text-sm text-muted-foreground">
                No costs yet.
            </p>
        );
    }

    const total = years.reduce((sum, year) => sum + year.total_cost, 0);
    const items = years.reduce((sum, year) => sum + year.items_count, 0);

    return (
        <div className="rounded-lg border bg-card">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead className="pl-4">Year</TableHead>
                        <TableHead className="text-right">Items</TableHead>
                        <TableHead className="text-right">Total cost</TableHead>
                        <TableHead className="pr-4 text-right">
                            Change from previous year
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {years.map((year, i) => {
                        const previous = years[i - 1];
                        const change = previous
                            ? year.total_cost - previous.total_cost
                            : null;
                        const percent =
                            previous &&
                            previous.total_cost > 0 &&
                            change !== null
                                ? Math.round(
                                      (change / previous.total_cost) * 100,
                                  )
                                : null;

                        return (
                            <TableRow key={year.year}>
                                <TableCell className="pl-4 font-medium">
                                    {year.year}
                                </TableCell>
                                <TableCell className="text-right tabular-nums">
                                    {year.items_count}
                                </TableCell>
                                <TableCell className="text-right tabular-nums">
                                    {money(year.total_cost)}
                                </TableCell>
                                <TableCell
                                    className={cn(
                                        'pr-4 text-right tabular-nums',
                                        (change === null || change === 0) &&
                                            'text-muted-foreground',
                                    )}
                                >
                                    {change === null
                                        ? 'First year'
                                        : `${change > 0 ? '+' : change < 0 ? '−' : ''}${money(Math.abs(change))}${percent !== null ? ` (${percent > 0 ? '+' : ''}${percent}%)` : ''}`}
                                </TableCell>
                            </TableRow>
                        );
                    })}
                </TableBody>
                <TableFooter>
                    <TableRow>
                        <TableCell className="pl-4">All years</TableCell>
                        <TableCell className="text-right tabular-nums">
                            {items}
                        </TableCell>
                        <TableCell className="text-right tabular-nums">
                            {money(total)}
                        </TableCell>
                        <TableCell />
                    </TableRow>
                </TableFooter>
            </Table>
        </div>
    );
}
