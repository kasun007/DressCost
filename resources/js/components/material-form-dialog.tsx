import { Form } from '@inertiajs/react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import MaterialController from '@/actions/App/Http/Controllers/MaterialController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { toLabel } from '@/lib/labels';
import type { Material } from '@/types';

type Props = {
    units: string[];
    material?: Material;
    trigger: ReactNode;
};

/**
 * Add a new material, or edit an existing one when `material` is given.
 */
export default function MaterialFormDialog({
    units,
    material,
    trigger,
}: Props) {
    const [open, setOpen] = useState(false);

    const form = material
        ? MaterialController.update.form(material.id)
        : MaterialController.store.form();

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogTitle>
                    {material ? 'Edit material' : 'Add material'}
                </DialogTitle>
                <DialogDescription>
                    The price you pay for one unit of this material.
                </DialogDescription>

                <Form
                    {...form}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={material?.name}
                                    placeholder="e.g. Cotton poplin"
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="unit">Unit</Label>
                                    <Select
                                        name="unit"
                                        defaultValue={
                                            material?.unit ?? units[0]
                                        }
                                    >
                                        <SelectTrigger id="unit">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {units.map((unit) => (
                                                <SelectItem
                                                    key={unit}
                                                    value={unit}
                                                >
                                                    {toLabel(unit)}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.unit} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="unit_price">
                                        Price per unit
                                    </Label>
                                    <Input
                                        id="unit_price"
                                        name="unit_price"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        defaultValue={material?.unit_price}
                                        required
                                    />
                                    <InputError message={errors.unit_price} />
                                </div>
                            </div>

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button variant="secondary">Cancel</Button>
                                </DialogClose>
                                <Button type="submit" disabled={processing}>
                                    Save
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
