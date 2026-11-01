import { Link } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
import ItemController from '@/actions/App/Http/Controllers/ItemController';
import ConfirmDeleteButton from '@/components/confirm-delete-button';
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
import { SIZES } from '@/lib/sizes';
import { cn } from '@/lib/utils';
import type { ItemDetail } from '@/types';

type Props = {
    item: ItemDetail;
    /** Needed for the edit and delete buttons; leave out for a read-only view. */
    projectId?: number;
};

/**
 * Every costing detail of one item: sizes and quantities, material lines,
 * other cost lines, and how they add up per piece and for all pieces.
 */
export default function ItemCostPanel({ projectId, item }: Props) {
    const money = useMoney();

    const summary: [string, keyof ItemDetail['cost'], string?][] = [
        ['Materials', 'material_cost'],
        ['Other costs', 'other_cost'],
        ['Total cost', 'total_cost', 'font-semibold'],
        [`Profit (${item.profit_margin}% margin)`, 'profit', 'text-positive'],
        ['Selling price', 'selling_price', 'text-base font-semibold'],
    ];

    return (
        <div className="space-y-4">
            <section className="flex flex-wrap items-center gap-2 text-sm">
                <span className="font-semibold">Quantities to make:</span>
                {SIZES.map((size) => (
                    <span
                        key={size.key}
                        className={cn(
                            'rounded-md border px-2.5 py-1 tabular-nums',
                            item.quantities[size.key] === 0 &&
                                'text-muted-foreground',
                        )}
                    >
                        {size.label}{' '}
                        <span className="font-semibold">
                            {item.quantities[size.key]}
                        </span>
                    </span>
                ))}
                <span className="rounded-md bg-secondary px-2.5 py-1 font-semibold text-secondary-foreground tabular-nums">
                    {item.quantity} pieces in total
                </span>
            </section>

            <div className="grid items-start gap-4 lg:grid-cols-2 xl:grid-cols-[1fr_1fr_26rem]">
                <section className="rounded-lg border bg-background/60">
                    <h4 className="border-b px-4 py-2.5 text-sm font-semibold">
                        Materials
                    </h4>
                    {item.materials.length === 0 ? (
                        <p className="px-4 py-3 text-muted-foreground">
                            No materials.
                        </p>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-4">
                                        Material
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Quantity
                                    </TableHead>
                                    <TableHead className="pr-4 text-right">
                                        Cost
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {item.materials.map((material) => (
                                    <TableRow key={material.id}>
                                        <TableCell className="pl-4">
                                            {material.name}
                                            <span className="block text-xs text-muted-foreground">
                                                {money(material.unit_price)} per{' '}
                                                {material.unit}
                                            </span>
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums">
                                            {material.quantity} {material.unit}
                                        </TableCell>
                                        <TableCell className="pr-4 text-right tabular-nums">
                                            {money(material.cost)}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                            <TableFooter>
                                <TableRow>
                                    <TableCell className="pl-4" colSpan={2}>
                                        Materials total
                                    </TableCell>
                                    <TableCell className="pr-4 text-right tabular-nums">
                                        {money(item.cost.material_cost)}
                                    </TableCell>
                                </TableRow>
                            </TableFooter>
                        </Table>
                    )}
                </section>

                <section className="rounded-lg border bg-background/60">
                    <h4 className="border-b px-4 py-2.5 text-sm font-semibold">
                        Other costs
                    </h4>
                    {item.costs.length === 0 ? (
                        <p className="px-4 py-3 text-muted-foreground">
                            No other costs.
                        </p>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-4">
                                        Costing type
                                    </TableHead>
                                    <TableHead className="pr-4 text-right">
                                        Amount
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {item.costs.map((cost) => (
                                    <TableRow key={cost.id}>
                                        <TableCell className="pl-4">
                                            {cost.name}
                                        </TableCell>
                                        <TableCell className="pr-4 text-right tabular-nums">
                                            {money(cost.amount)}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                            <TableFooter>
                                <TableRow>
                                    <TableCell className="pl-4">
                                        Other costs total
                                    </TableCell>
                                    <TableCell className="pr-4 text-right tabular-nums">
                                        {money(item.cost.other_cost)}
                                    </TableCell>
                                </TableRow>
                            </TableFooter>
                        </Table>
                    )}
                </section>

                <section className="space-y-4 rounded-lg bg-secondary p-4 text-secondary-foreground lg:col-span-2 xl:col-span-1">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-xs text-muted-foreground">
                                <th className="pb-2 text-left font-normal" />
                                <th className="pb-2 text-right font-normal">
                                    Per piece
                                </th>
                                <th className="pb-2 text-right font-normal">
                                    All {item.quantity}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {summary.map(([label, key, className]) => (
                                <tr key={key} className={className}>
                                    <th className="py-1 pr-2 text-left font-[inherit] whitespace-nowrap">
                                        {label}
                                    </th>
                                    <td className="py-1 text-right tabular-nums">
                                        {money(item.cost[key])}
                                    </td>
                                    <td className="py-1 pl-2 text-right tabular-nums">
                                        {money(item.all_pieces[key])}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>

                    {projectId !== undefined && (
                        <div className="flex gap-2">
                            <Button size="sm" asChild>
                                <Link
                                    href={ItemController.edit({
                                        project: projectId,
                                        item: item.id,
                                    })}
                                >
                                    <Pencil /> Edit item
                                </Link>
                            </Button>
                            <ConfirmDeleteButton
                                form={ItemController.destroy.form({
                                    project: projectId,
                                    item: item.id,
                                })}
                                title={`Delete ${item.name}?`}
                                description="This item and its cost breakdown will be permanently deleted."
                            />
                        </div>
                    )}
                </section>
            </div>
        </div>
    );
}
