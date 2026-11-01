import { ChevronRight } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
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
import { buildSeries } from '@/lib/chart-series';
import { formatDate } from '@/lib/dates';
import { toLabel } from '@/lib/labels';
import { cn } from '@/lib/utils';
import type { ReportLevel, ReportNode } from '@/types';

type Props = {
    nodes: ReportNode[];
    /** Every costing type in fixed order, so colours match the charts. */
    categories: string[];
    /** Focus the report on a workspace or project. */
    onFocus: (kind: ReportLevel, id: number) => void;
};

/**
 * Workspaces and projects as an expandable tree with costs rolled up.
 * Workspaces expand to their projects, projects to their items.
 */
export default function ProjectTreeTable({
    nodes,
    categories,
    onFocus,
}: Props) {
    const money = useMoney();
    const keyOf = (node: ReportNode) => `${node.kind}-${node.id}`;
    const [open, setOpen] = useState<Set<string>>(
        () => new Set(nodes.map(keyOf)),
    );
    const total = nodes.reduce((sum, node) => sum + node.total_cost, 0);
    const { keyOf: seriesKeyOf, series } = buildSeries(categories);
    const colorOf = (name: string) =>
        series.find((s) => s.key === seriesKeyOf(name))?.color;

    const toggle = (id: string) =>
        setOpen((current) => {
            const next = new Set(current);

            if (next.has(id)) {
                next.delete(id);
            } else {
                next.add(id);
            }

            return next;
        });

    const share = (amount: number) => (total > 0 ? amount / total : 0);

    const renderNode = (node: ReportNode, depth: number): React.ReactNode[] => {
        const key = keyOf(node);
        const isOpen = open.has(key);
        const canExpand = node.children.length > 0 || node.items.length > 0;
        const largest = node.categories[0];

        const rows: React.ReactNode[] = [
            <TableRow
                key={key}
                className={cn(depth === 0 && 'bg-muted/30 font-medium')}
            >
                <TableCell>
                    <div
                        className="flex items-center gap-1"
                        style={{ paddingLeft: depth * 20 }}
                    >
                        <Button
                            variant="ghost"
                            size="icon"
                            className="size-6"
                            disabled={!canExpand}
                            aria-expanded={isOpen}
                            aria-label={`${isOpen ? 'Collapse' : 'Expand'} ${node.name}`}
                            onClick={() => toggle(key)}
                        >
                            <ChevronRight
                                className={cn(
                                    'size-4 transition-transform',
                                    isOpen && 'rotate-90',
                                    !canExpand && 'opacity-0',
                                )}
                            />
                        </Button>
                        <button
                            type="button"
                            onClick={() => onFocus(node.kind, node.id)}
                            className="truncate text-left underline-offset-4 hover:text-primary hover:underline"
                            title={`Show the report for ${node.name}`}
                        >
                            {node.name}
                        </button>
                    </div>
                </TableCell>
                <TableCell className="text-right tabular-nums">
                    {node.items_count}
                </TableCell>
                <TableCell className="text-right tabular-nums">
                    {money(node.total_cost)}
                </TableCell>
                <TableCell>
                    <ShareBar value={share(node.total_cost)} />
                </TableCell>
                <TableCell className="hidden lg:table-cell">
                    {largest && (
                        <span className="flex items-center gap-2 text-sm">
                            <span
                                className="size-2.5 shrink-0 rounded-sm"
                                style={{
                                    backgroundColor: colorOf(largest.name),
                                }}
                                aria-hidden
                            />
                            {largest.name}
                            <span className="text-muted-foreground tabular-nums">
                                {Math.round(
                                    (largest.amount / node.total_cost) * 100,
                                )}
                                %
                            </span>
                        </span>
                    )}
                </TableCell>
            </TableRow>,
        ];

        if (!isOpen) {
            return rows;
        }

        node.children.forEach((child) =>
            rows.push(...renderNode(child, depth + 1)),
        );

        node.items.forEach((item) =>
            rows.push(
                <TableRow key={`i${item.id}`} className="text-muted-foreground">
                    <TableCell>
                        <div style={{ paddingLeft: (depth + 1) * 20 + 28 }}>
                            <span className="text-foreground">{item.name}</span>
                            <span className="block text-xs">
                                {toLabel(item.type)}, saved{' '}
                                {formatDate(item.saved_on)}
                            </span>
                        </div>
                    </TableCell>
                    <TableCell />
                    <TableCell className="text-right tabular-nums">
                        {money(item.total_cost)}
                    </TableCell>
                    <TableCell>
                        <ShareBar value={share(item.total_cost)} muted />
                    </TableCell>
                    <TableCell className="hidden lg:table-cell" />
                </TableRow>,
            ),
        );

        return rows;
    };

    return (
        <div className="rounded-xl border bg-card">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead className="pl-4">
                            Workspace / project
                        </TableHead>
                        <TableHead className="text-right">Items</TableHead>
                        <TableHead className="text-right">Cost</TableHead>
                        <TableHead className="w-48">
                            Share of this view
                        </TableHead>
                        <TableHead className="hidden lg:table-cell">
                            Largest costing type
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {nodes.flatMap((node) => renderNode(node, 0))}
                </TableBody>
                <TableFooter>
                    <TableRow>
                        <TableCell className="pl-11">Total</TableCell>
                        <TableCell className="text-right tabular-nums">
                            {nodes.reduce((sum, n) => sum + n.items_count, 0)}
                        </TableCell>
                        <TableCell className="text-right tabular-nums">
                            {money(total)}
                        </TableCell>
                        <TableCell />
                        <TableCell className="hidden lg:table-cell" />
                    </TableRow>
                </TableFooter>
            </Table>
        </div>
    );
}

function ShareBar({
    value,
    muted = false,
}: {
    value: number;
    muted?: boolean;
}) {
    return (
        <div className="flex items-center gap-2">
            <div className="h-1.5 flex-1 overflow-hidden rounded-full bg-muted">
                <div
                    className={cn(
                        'h-full rounded-full',
                        muted ? 'bg-primary/40' : 'bg-primary',
                    )}
                    style={{ width: `${value * 100}%` }}
                />
            </div>
            <span className="w-9 text-right text-xs text-muted-foreground tabular-nums">
                {Math.round(value * 100)}%
            </span>
        </div>
    );
}
