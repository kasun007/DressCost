import { Head, router } from '@inertiajs/react';
import { ArrowLeftRight } from 'lucide-react';
import ComparisonChart, {
    COLOR_A,
    COLOR_B,
} from '@/components/charts/comparison-chart';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
import { compare, index } from '@/routes/reports';
import type { CompareKind, ComparisonSide, Option } from '@/types';

const KINDS: {
    value: CompareKind;
    label: string;
    singular: string;
    plural: string;
}[] = [
    {
        value: 'workspace',
        label: 'Workspaces',
        singular: 'workspace',
        plural: 'workspaces',
    },
    {
        value: 'project',
        label: 'Projects',
        singular: 'project',
        plural: 'projects',
    },
    { value: 'item', label: 'Items', singular: 'item', plural: 'items' },
];

type Props = {
    kind: CompareKind;
    options: Option[];
    categories: string[];
    a: ComparisonSide | null;
    b: ComparisonSide | null;
};

export default function ReportsCompare({
    kind,
    options,
    categories,
    a,
    b,
}: Props) {
    const money = useMoney();
    const words = KINDS.find((k) => k.value === kind) ?? KINDS[1];

    const choose = (side: 'a' | 'b', id: string) =>
        router.get(
            compare.url(),
            { kind, a: a?.id, b: b?.id, [side]: id },
            { preserveScroll: true, replace: true },
        );

    const swap = () =>
        router.get(
            compare.url(),
            { kind, a: b?.id, b: a?.id },
            { preserveScroll: true, replace: true },
        );

    const amountIn = (side: ComparisonSide | null, name: string) =>
        side?.categories.find((c) => c.name === name)?.amount ?? 0;

    const rows = categories
        .map((name) => ({ name, a: amountIn(a, name), b: amountIn(b, name) }))
        .filter((row) => row.a > 0 || row.b > 0);

    return (
        <>
            <Head title="Compare" />

            <div className="space-y-6 p-4 md:p-6">
                <Heading
                    title="Compare"
                    description="Put two workspaces, projects or items side by side, broken down by costing type."
                />

                <ToggleGroup
                    type="single"
                    variant="outline"
                    value={kind}
                    onValueChange={(value) =>
                        value &&
                        router.get(
                            compare.url(),
                            { kind: value },
                            { preserveScroll: true, replace: true },
                        )
                    }
                    aria-label="What to compare"
                >
                    {KINDS.map((k) => (
                        <ToggleGroupItem
                            key={k.value}
                            value={k.value}
                            className="px-4"
                        >
                            {k.label}
                        </ToggleGroupItem>
                    ))}
                </ToggleGroup>

                <div className="grid items-end gap-4 rounded-xl border bg-card p-4 md:grid-cols-[1fr_auto_1fr]">
                    <ProjectPicker
                        id="compare-a"
                        label={`First ${words.singular}`}
                        color={COLOR_A}
                        value={a?.id}
                        projects={options}
                        onChange={(id) => choose('a', id)}
                    />
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={swap}
                        disabled={!a || !b}
                        aria-label="Swap sides"
                        className="justify-self-center"
                    >
                        <ArrowLeftRight />
                    </Button>
                    <ProjectPicker
                        id="compare-b"
                        label={`Second ${words.singular}`}
                        color={COLOR_B}
                        value={b?.id}
                        projects={options}
                        onChange={(id) => choose('b', id)}
                    />
                </div>

                {!a || !b ? (
                    <p className="rounded-xl border border-dashed bg-card p-8 text-center text-sm text-muted-foreground">
                        {options.length < 2
                            ? `Create at least two ${words.plural} to compare them.`
                            : `Choose two ${words.plural} to compare.`}
                    </p>
                ) : (
                    <>
                        <div className="grid gap-4 md:grid-cols-2">
                            <SideSummary
                                side={a}
                                color={COLOR_A}
                                showItemStats={kind !== 'item'}
                            />
                            <SideSummary
                                side={b}
                                color={COLOR_B}
                                showItemStats={kind !== 'item'}
                            />
                        </div>

                        <Difference a={a} b={b} />

                        <Card>
                            <CardHeader>
                                <CardTitle>By costing type</CardTitle>
                                <CardDescription>
                                    Where the two {words.plural} spend
                                    differently.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {rows.length === 0 ? (
                                    <p className="py-8 text-center text-sm text-muted-foreground">
                                        Neither project has any costs yet.
                                    </p>
                                ) : (
                                    <ComparisonChart
                                        rows={rows}
                                        labelA={a.name}
                                        labelB={b.name}
                                    />
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Cost table</CardTitle>
                                <CardDescription>
                                    Difference is the second project minus the
                                    first.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Costing type</TableHead>
                                            <TableHead className="text-right">
                                                {a.name}
                                            </TableHead>
                                            <TableHead className="text-right">
                                                {b.name}
                                            </TableHead>
                                            <TableHead className="text-right">
                                                Difference
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {rows.map((row) => (
                                            <TableRow key={row.name}>
                                                <TableCell className="font-medium">
                                                    {row.name}
                                                </TableCell>
                                                <TableCell className="text-right tabular-nums">
                                                    {money(row.a)}
                                                </TableCell>
                                                <TableCell className="text-right tabular-nums">
                                                    {money(row.b)}
                                                </TableCell>
                                                <TableCell className="text-right tabular-nums">
                                                    <SignedMoney
                                                        amount={row.b - row.a}
                                                    />
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                    <TableFooter>
                                        <TableRow>
                                            <TableCell>Total</TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                {money(a.total_cost)}
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                {money(b.total_cost)}
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                <SignedMoney
                                                    amount={
                                                        b.total_cost -
                                                        a.total_cost
                                                    }
                                                />
                                            </TableCell>
                                        </TableRow>
                                    </TableFooter>
                                </Table>
                            </CardContent>
                        </Card>
                    </>
                )}
            </div>
        </>
    );
}

function ProjectPicker({
    id,
    label,
    color,
    value,
    projects,
    onChange,
}: {
    id: string;
    label: string;
    color: string;
    value?: number;
    projects: Option[];
    onChange: (id: string) => void;
}) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={id} className="flex items-center gap-2">
                <span
                    className="size-2.5 rounded-sm"
                    style={{ backgroundColor: color }}
                    aria-hidden
                />
                {label}
            </Label>
            <Select
                value={value ? String(value) : undefined}
                onValueChange={onChange}
            >
                <SelectTrigger id={id} className="w-full">
                    <SelectValue placeholder="Choose one" />
                </SelectTrigger>
                <SelectContent>
                    {projects.map((project) => (
                        <SelectItem key={project.id} value={String(project.id)}>
                            {project.name}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}

function SideSummary({
    side,
    color,
    showItemStats,
}: {
    side: ComparisonSide;
    color: string;
    showItemStats: boolean;
}) {
    const money = useMoney();
    const average =
        side.items_count > 0 ? side.total_cost / side.items_count : 0;

    const stats = [
        { label: 'Total cost', value: money(side.total_cost) },
        ...(showItemStats
            ? [
                  { label: 'Items', value: String(side.items_count) },
                  { label: 'Average per item', value: money(average) },
              ]
            : []),
    ];

    return (
        <Card
            className="gap-4 border-t-4 py-5"
            style={{ borderTopColor: color }}
        >
            <CardHeader className="px-5">
                <CardTitle className="truncate">{side.name}</CardTitle>
                {side.detail && (
                    <p className="truncate text-sm text-muted-foreground">
                        {side.detail}
                    </p>
                )}
            </CardHeader>
            <CardContent className="px-5">
                <dl className="grid grid-cols-3 gap-3">
                    {stats.map((stat) => (
                        <div key={stat.label}>
                            <dt className="text-sm text-muted-foreground">
                                {stat.label}
                            </dt>
                            <dd className="text-lg font-semibold tabular-nums">
                                {stat.value}
                            </dd>
                        </div>
                    ))}
                </dl>
            </CardContent>
        </Card>
    );
}

function Difference({ a, b }: { a: ComparisonSide; b: ComparisonSide }) {
    const money = useMoney();
    const diff = b.total_cost - a.total_cost;

    if (a.id === b.id) {
        return (
            <p className="text-sm text-muted-foreground">
                The same project is chosen on both sides.
            </p>
        );
    }

    if (diff === 0) {
        return (
            <p className="rounded-xl bg-secondary px-4 py-3 text-sm text-secondary-foreground">
                Both cost the same.
            </p>
        );
    }

    const [higher, lower] = diff > 0 ? [b, a] : [a, b];
    const percent =
        lower.total_cost > 0
            ? ` (${Math.round((Math.abs(diff) / lower.total_cost) * 100)}% more)`
            : '';

    return (
        <p className="rounded-xl bg-secondary px-4 py-3 text-sm text-secondary-foreground">
            <span className="font-semibold">{higher.name}</span> costs{' '}
            <span className="font-semibold tabular-nums">
                {money(Math.abs(diff))}
            </span>{' '}
            more than <span className="font-semibold">{lower.name}</span>
            {percent}.
        </p>
    );
}

function SignedMoney({ amount }: { amount: number }) {
    const money = useMoney();

    return (
        <span className={cn(amount === 0 && 'text-muted-foreground')}>
            {amount > 0 ? '+' : amount < 0 ? '−' : ''}
            {money(Math.abs(amount))}
        </span>
    );
}

ReportsCompare.layout = {
    breadcrumbs: [
        { title: 'Reports', href: index() },
        { title: 'Compare', href: compare() },
    ],
};
