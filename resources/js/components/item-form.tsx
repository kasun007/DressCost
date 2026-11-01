import { Link, useForm } from '@inertiajs/react';
import { Plus, X } from 'lucide-react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useMoney } from '@/hooks/use-money';
import { toLabel } from '@/lib/labels';
import { SIZES, totalPieces } from '@/lib/sizes';
import { index as materialsIndex } from '@/routes/materials';
import type {
    ItemCostRow,
    ItemFormData,
    ItemMaterialRow,
    Material,
    SavedItem,
} from '@/types';
import type { RouteDefinition } from '@/wayfinder';

type Props = {
    materials: Material[];
    types: string[];
    costTypes: string[];
    /** Saved items a new item can start from (only when adding). */
    savedItems?: SavedItem[];
    initial: ItemFormData;
    submitTo: RouteDefinition<'post'> | RouteDefinition<'put'>;
    cancelHref: string;
};

const emptyMaterialRow: ItemMaterialRow = { material_id: '', quantity: '' };
const emptyCostRow: ItemCostRow = { name: '', amount: '' };

const updateAt = <T,>(rows: T[], index: number, changes: Partial<T>): T[] =>
    rows.map((row, i) => (i === index ? { ...row, ...changes } : row));

const removeAt = <T,>(rows: T[], index: number): T[] =>
    rows.filter((_, i) => i !== index);

const toNumberOrEmpty = (value: string): number | '' =>
    value === '' ? '' : Number(value);

/**
 * Shared form for adding and editing an item, with a live cost estimate.
 */
export default function ItemForm({
    materials,
    types,
    costTypes,
    savedItems = [],
    initial,
    submitTo,
    cancelHref,
}: Props) {
    const money = useMoney();
    const { data, setData, submit, processing, errors } =
        useForm<ItemFormData>(initial);

    const priceOf = (id: number | '') =>
        materials.find((material) => material.id === id)?.unit_price ?? 0;

    const lineCost = (row: ItemMaterialRow) =>
        priceOf(row.material_id) * Number(row.quantity);

    const materialCost = data.materials.reduce(
        (sum, row) => sum + lineCost(row),
        0,
    );
    const otherCost = data.costs.reduce(
        (sum, row) => sum + Number(row.amount),
        0,
    );
    const totalCost = materialCost + otherCost;
    const sellingPrice = totalCost * (1 + Number(data.profit_margin) / 100);
    const pieces = totalPieces(data);

    const chosenIds = new Set(data.materials.map((row) => row.material_id));

    const updateMaterial = (index: number, changes: Partial<ItemMaterialRow>) =>
        setData('materials', updateAt(data.materials, index, changes));

    const updateCost = (index: number, changes: Partial<ItemCostRow>) =>
        setData('costs', updateAt(data.costs, index, changes));

    const fieldError = (path: string) =>
        (errors as Record<string, string | undefined>)[path];

    const startFrom = (id: string) => {
        const saved = savedItems.find((item) => String(item.id) === id);

        if (!saved) {
            return;
        }

        setData({
            ...data,
            name: saved.name,
            type: saved.type,
            profit_margin: saved.profit_margin,
            quantity_small: saved.quantity_small,
            quantity_medium: saved.quantity_medium,
            quantity_large: saved.quantity_large,
            quantity_xl: saved.quantity_xl,
            materials: saved.materials.map((row) => ({ ...row })),
            costs: saved.costs.map((row) => ({ ...row })),
        });
    };

    const handleSubmit = (event: FormEvent) => {
        event.preventDefault();
        submit(submitTo, { preserveScroll: true });
    };

    return (
        <form onSubmit={handleSubmit} className="grid gap-6 lg:grid-cols-3">
            <div className="space-y-6 lg:col-span-2">
                {savedItems.length > 0 && (
                    <Card className="border-dashed bg-secondary/40">
                        <CardHeader>
                            <CardTitle>Start from a saved item</CardTitle>
                            <p className="text-sm text-muted-foreground">
                                Optional. Copies a saved item's name, type,
                                quantities, materials, other costs and margin.
                                You can change anything before saving.
                            </p>
                        </CardHeader>
                        <CardContent>
                            <Select onValueChange={startFrom}>
                                <SelectTrigger
                                    aria-label="Saved item"
                                    className="w-full bg-card"
                                >
                                    <SelectValue placeholder="Choose a saved item" />
                                </SelectTrigger>
                                <SelectContent>
                                    {savedItems.map((saved) => (
                                        <SelectItem
                                            key={saved.id}
                                            value={String(saved.id)}
                                        >
                                            {saved.name}
                                            <span className="text-muted-foreground">
                                                {' '}
                                                ({saved.project})
                                            </span>
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>Details</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 sm:grid-cols-3">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) =>
                                    setData('name', e.target.value)
                                }
                                placeholder="e.g. A-line midi dress"
                                required
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="type">Type</Label>
                            <Select
                                value={data.type}
                                onValueChange={(value) =>
                                    setData('type', value)
                                }
                            >
                                <SelectTrigger id="type">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {types.map((type) => (
                                        <SelectItem key={type} value={type}>
                                            {toLabel(type)}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.type} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="profit_margin">
                                Profit margin (%)
                            </Label>
                            <Input
                                id="profit_margin"
                                type="number"
                                min="0"
                                step="0.01"
                                value={data.profit_margin}
                                onChange={(e) =>
                                    setData(
                                        'profit_margin',
                                        toNumberOrEmpty(e.target.value),
                                    )
                                }
                                required
                            />
                            <InputError message={errors.profit_margin} />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Quantities to make</CardTitle>
                        <p className="text-sm text-muted-foreground">
                            How many pieces of each size. Every size costs the
                            same per piece.
                        </p>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        <div className="grid grid-cols-2 gap-4 sm:grid-cols-5">
                            {SIZES.map((size) => {
                                const field = `quantity_${size.key}` as const;

                                return (
                                    <div key={size.key} className="grid gap-2">
                                        <Label htmlFor={field}>
                                            {size.label}
                                        </Label>
                                        <Input
                                            id={field}
                                            type="number"
                                            min="0"
                                            step="1"
                                            value={data[field]}
                                            onChange={(e) =>
                                                setData(
                                                    field,
                                                    toNumberOrEmpty(
                                                        e.target.value,
                                                    ),
                                                )
                                            }
                                        />
                                        <InputError message={errors[field]} />
                                    </div>
                                );
                            })}
                            <div className="grid content-start gap-2">
                                <span className="text-sm font-medium">
                                    Total pieces
                                </span>
                                <span className="flex h-9 items-center text-lg font-semibold tabular-nums">
                                    {pieces}
                                </span>
                            </div>
                        </div>
                        <InputError message={fieldError('quantity')} />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <CardTitle>Materials</CardTitle>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            disabled={materials.length === 0}
                            onClick={() =>
                                setData('materials', [
                                    ...data.materials,
                                    emptyMaterialRow,
                                ])
                            }
                        >
                            <Plus /> Add material
                        </Button>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {materials.length === 0 && (
                            <p className="text-sm text-muted-foreground">
                                You have no materials yet.{' '}
                                <Link
                                    href={materialsIndex()}
                                    className="underline underline-offset-4"
                                >
                                    Add some materials
                                </Link>{' '}
                                first.
                            </p>
                        )}

                        {materials.length > 0 &&
                            data.materials.length === 0 && (
                                <p className="text-sm text-muted-foreground">
                                    No materials added to this item yet.
                                </p>
                            )}

                        {data.materials.map((row, index) => {
                            const material = materials.find(
                                (m) => m.id === row.material_id,
                            );

                            return (
                                <div
                                    key={index}
                                    className="grid grid-cols-[1fr_8rem_auto] items-start gap-2 sm:grid-cols-[1fr_8rem_8rem_auto]"
                                >
                                    <div className="grid gap-1">
                                        <Select
                                            value={String(row.material_id)}
                                            onValueChange={(value) =>
                                                updateMaterial(index, {
                                                    material_id: Number(value),
                                                })
                                            }
                                        >
                                            <SelectTrigger
                                                aria-label="Material"
                                                className="w-full"
                                            >
                                                <SelectValue placeholder="Choose a material" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {materials.map((m) => (
                                                    <SelectItem
                                                        key={m.id}
                                                        value={String(m.id)}
                                                        disabled={
                                                            chosenIds.has(
                                                                m.id,
                                                            ) &&
                                                            m.id !==
                                                                row.material_id
                                                        }
                                                    >
                                                        {m.name} (
                                                        {money(m.unit_price)} /{' '}
                                                        {m.unit})
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={fieldError(
                                                `materials.${index}.material_id`,
                                            )}
                                        />
                                    </div>

                                    <div className="grid gap-1">
                                        <Input
                                            type="number"
                                            min="0"
                                            step="0.001"
                                            aria-label="Quantity"
                                            placeholder={
                                                material
                                                    ? `Qty (${material.unit})`
                                                    : 'Qty'
                                            }
                                            value={row.quantity}
                                            onChange={(e) =>
                                                updateMaterial(index, {
                                                    quantity: toNumberOrEmpty(
                                                        e.target.value,
                                                    ),
                                                })
                                            }
                                        />
                                        <InputError
                                            message={fieldError(
                                                `materials.${index}.quantity`,
                                            )}
                                        />
                                    </div>

                                    <p className="hidden h-9 items-center justify-end text-sm tabular-nums sm:flex">
                                        {money(lineCost(row))}
                                    </p>

                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        aria-label="Remove material"
                                        onClick={() =>
                                            setData(
                                                'materials',
                                                removeAt(data.materials, index),
                                            )
                                        }
                                    >
                                        <X />
                                    </Button>
                                </div>
                            );
                        })}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <CardTitle>Other costs</CardTitle>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() =>
                                setData('costs', [...data.costs, emptyCostRow])
                            }
                        >
                            <Plus /> Add cost
                        </Button>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <datalist id="cost-types">
                            {costTypes.map((type) => (
                                <option key={type} value={type} />
                            ))}
                        </datalist>

                        {data.costs.length === 0 && (
                            <p className="text-sm text-muted-foreground">
                                No other costs yet. Add labour, overhead or any
                                custom cost such as embroidery.
                            </p>
                        )}

                        {data.costs.map((row, index) => (
                            <div
                                key={index}
                                className="grid grid-cols-[1fr_8rem_auto] items-start gap-2"
                            >
                                <div className="grid gap-1">
                                    <Input
                                        list="cost-types"
                                        aria-label="Costing type"
                                        placeholder="Choose or type a costing type"
                                        value={row.name}
                                        onChange={(e) =>
                                            updateCost(index, {
                                                name: e.target.value,
                                            })
                                        }
                                    />
                                    <InputError
                                        message={fieldError(
                                            `costs.${index}.name`,
                                        )}
                                    />
                                </div>

                                <div className="grid gap-1">
                                    <Input
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        aria-label="Amount"
                                        placeholder="Amount"
                                        value={row.amount}
                                        onChange={(e) =>
                                            updateCost(index, {
                                                amount: toNumberOrEmpty(
                                                    e.target.value,
                                                ),
                                            })
                                        }
                                    />
                                    <InputError
                                        message={fieldError(
                                            `costs.${index}.amount`,
                                        )}
                                    />
                                </div>

                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    aria-label="Remove cost"
                                    onClick={() =>
                                        setData(
                                            'costs',
                                            removeAt(data.costs, index),
                                        )
                                    }
                                >
                                    <X />
                                </Button>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>

            <Card className="h-fit lg:sticky lg:top-4">
                <CardHeader>
                    <CardTitle>Cost estimate</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    <dl className="space-y-2 text-sm">
                        {[
                            ['Materials', materialCost] as const,
                            ...data.costs.map(
                                (row) =>
                                    [
                                        row.name || 'Unnamed cost',
                                        Number(row.amount),
                                    ] as const,
                            ),
                        ].map(([label, amount], index) => (
                            <div key={index} className="flex justify-between">
                                <dt className="text-muted-foreground">
                                    {label}
                                </dt>
                                <dd className="tabular-nums">
                                    {money(Number(amount))}
                                </dd>
                            </div>
                        ))}
                        <div className="flex justify-between border-t pt-2 font-medium">
                            <dt>Cost per piece</dt>
                            <dd className="tabular-nums">{money(totalCost)}</dd>
                        </div>
                        <div className="flex justify-between font-medium">
                            <dt>Selling price per piece</dt>
                            <dd className="tabular-nums">
                                {money(sellingPrice)}
                            </dd>
                        </div>
                    </dl>

                    <dl className="space-y-2 rounded-lg bg-secondary p-3 text-sm text-secondary-foreground">
                        <div className="flex justify-between">
                            <dt>For {pieces} pieces</dt>
                        </div>
                        <div className="flex justify-between font-medium">
                            <dt>Total cost</dt>
                            <dd className="tabular-nums">
                                {money(totalCost * pieces)}
                            </dd>
                        </div>
                        <div className="flex justify-between text-positive">
                            <dt>Profit</dt>
                            <dd className="tabular-nums">
                                {money((sellingPrice - totalCost) * pieces)}
                            </dd>
                        </div>
                        <div className="flex justify-between text-base font-semibold">
                            <dt>Selling value</dt>
                            <dd className="tabular-nums">
                                {money(sellingPrice * pieces)}
                            </dd>
                        </div>
                    </dl>

                    <div className="flex gap-2">
                        <Button type="submit" disabled={processing}>
                            Save item
                        </Button>
                        <Button variant="secondary" asChild>
                            <Link href={cancelHref}>Cancel</Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </form>
    );
}
