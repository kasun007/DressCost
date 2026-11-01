import { Head } from '@inertiajs/react';
import { Pencil, Plus } from 'lucide-react';
import MaterialController from '@/actions/App/Http/Controllers/MaterialController';
import ConfirmDeleteButton from '@/components/confirm-delete-button';
import Heading from '@/components/heading';
import MaterialFormDialog from '@/components/material-form-dialog';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useMoney } from '@/hooks/use-money';
import { toLabel } from '@/lib/labels';
import { index } from '@/routes/materials';
import type { Material } from '@/types';

type Props = {
    materials: Material[];
    units: string[];
};

export default function MaterialsIndex({ materials, units }: Props) {
    const money = useMoney();

    return (
        <>
            <Head title="Materials" />

            <div className="space-y-6 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Materials"
                        description="Fabrics, trims and anything else you buy, with their unit prices."
                    />
                    <MaterialFormDialog
                        units={units}
                        trigger={
                            <Button>
                                <Plus /> Add material
                            </Button>
                        }
                    />
                </div>

                {materials.length === 0 ? (
                    <p className="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">
                        No materials yet. Add the fabrics and trims you use to
                        start costing items.
                    </p>
                ) : (
                    <div className="rounded-xl border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Unit</TableHead>
                                    <TableHead className="text-right">
                                        Price per unit
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Used in
                                    </TableHead>
                                    <TableHead className="w-24" />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {materials.map((material) => (
                                    <TableRow key={material.id}>
                                        <TableCell className="font-medium">
                                            {material.name}
                                        </TableCell>
                                        <TableCell>
                                            {toLabel(material.unit)}
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums">
                                            {money(material.unit_price)}
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums">
                                            {material.items_count} items
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex justify-end">
                                                <MaterialFormDialog
                                                    units={units}
                                                    material={material}
                                                    trigger={
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            aria-label="Edit"
                                                        >
                                                            <Pencil />
                                                        </Button>
                                                    }
                                                />
                                                <ConfirmDeleteButton
                                                    form={MaterialController.destroy.form(
                                                        material.id,
                                                    )}
                                                    title={`Delete ${material.name}?`}
                                                    description="This material will be removed from your list. Materials used by an item can't be deleted."
                                                />
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}
            </div>
        </>
    );
}

MaterialsIndex.layout = {
    breadcrumbs: [{ title: 'Materials', href: index() }],
};
