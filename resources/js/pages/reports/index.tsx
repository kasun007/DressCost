import { Head, Link } from '@inertiajs/react';
import { ChevronRight, GitCompareArrows } from 'lucide-react';
import type { ReactNode } from 'react';
import CategoryByProjectChart from '@/components/charts/category-by-project-chart';
import CategoryTotals from '@/components/category-totals';
import CostByYear from '@/components/cost-by-year';
import Heading from '@/components/heading';
import ProjectTreeTable from '@/components/project-tree-table';
import ReportFilters from '@/components/report-filters';
import ReportHighlights from '@/components/report-highlights';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useMoney } from '@/hooks/use-money';
import { visitReport } from '@/lib/report-visit';
import { cn } from '@/lib/utils';
import { compare, index } from '@/routes/reports';
import type {
    CategoryTotal,
    MaterialUsage,
    Option,
    ProjectOption,
    ReportFilters as Filters,
    ReportLevel,
    ReportNode,
    ReportScope,
    ReportSummary,
    YearTotal,
} from '@/types';

type Props = {
    filters: Filters;
    options: {
        workspaces: Option[];
        projects: ProjectOption[];
        categories: string[];
    };
    summary: ReportSummary;
    categories: CategoryTotal[];
    scope: ReportScope | null;
    tree: ReportNode[];
    years: YearTotal[];
    materials: MaterialUsage[];
};

export default function ReportsIndex({
    filters,
    options,
    summary,
    categories,
    scope,
    tree,
    years,
    materials,
}: Props) {
    const focus = (kind: ReportLevel | null, id: number | null) =>
        visitReport({
            ...filters,
            workspace_id: kind === 'workspace' ? id : null,
            project_id: kind === 'project' ? id : null,
        });

    // The level the report splits by: workspaces, a workspace's projects, or a project's items.
    const splitLabel = !scope
        ? 'workspaces'
        : scope.kind === 'workspace'
          ? `the projects in ${scope.name}`
          : `the items in ${scope.name}`;

    return (
        <>
            <Head title="Reports" />

            <div className="space-y-8 p-4 md:p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Reports"
                        description="Where your costs go, what they're spent on and how they change over time."
                    />
                    <Button variant="outline" asChild>
                        <Link href={compare()}>
                            <GitCompareArrows /> Compare
                        </Link>
                    </Button>
                </div>

                <div className="space-y-3">
                    <ReportFilters
                        filters={filters}
                        workspaces={options.workspaces}
                        projects={options.projects}
                        categories={options.categories}
                    />
                    <ScopePath scope={scope} onFocus={focus} />
                    {filters.cost_type && (
                        <p className="text-sm text-muted-foreground">
                            Only {filters.cost_type} costs are counted.
                        </p>
                    )}
                </div>

                {summary.items_count === 0 ? (
                    <p className="rounded-xl border border-dashed bg-card p-8 text-center text-sm text-muted-foreground">
                        No items match these filters. Try a wider date range or
                        clear the filters.
                    </p>
                ) : (
                    <>
                        <Section
                            title="At a glance"
                            description="What stands out in this view."
                        >
                            <ReportHighlights
                                summary={summary}
                                categories={categories}
                                nodes={tree}
                            />
                        </Section>

                        <Section
                            title="Where the money goes"
                            description={`Each workspace adds up its projects, and each project its items. Click a name to focus the report on it; use the arrow to expand it here.`}
                        >
                            <ProjectTreeTable
                                nodes={tree}
                                categories={options.categories}
                                onFocus={focus}
                            />
                        </Section>

                        <Section
                            title="What the money is spent on"
                            description={`Costing types across ${splitLabel}.`}
                        >
                            <div
                                className={cn(
                                    'grid gap-6',
                                    tree.length > 1 &&
                                        'xl:grid-cols-[22rem_1fr]',
                                )}
                            >
                                <Card>
                                    <CardHeader>
                                        <CardTitle>
                                            Share by costing type
                                        </CardTitle>
                                        <CardDescription>
                                            Largest first.
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <CategoryTotals
                                            totals={categories}
                                            categories={options.categories}
                                        />
                                    </CardContent>
                                </Card>

                                {tree.length > 1 && (
                                    <Card>
                                        <CardHeader>
                                            <CardTitle>
                                                Costing types side by side
                                            </CardTitle>
                                            <CardDescription>
                                                How each of {splitLabel} splits
                                                its cost.
                                            </CardDescription>
                                        </CardHeader>
                                        <CardContent>
                                            <CategoryByProjectChart
                                                projects={tree}
                                                categories={options.categories}
                                            />
                                        </CardContent>
                                    </Card>
                                )}
                            </div>
                        </Section>

                        <Section
                            title="Cost by year"
                            description="Items and their costs by the year they were saved, with the change from the year before."
                        >
                            <CostByYear years={years} />
                        </Section>

                        {materials.length > 0 && (
                            <Section
                                title="Materials used"
                                description="How much of each material these items use, most expensive first."
                            >
                                <MaterialsTable materials={materials} />
                            </Section>
                        )}
                    </>
                )}
            </div>
        </>
    );
}

function Section({
    title,
    description,
    children,
}: {
    title: string;
    description: string;
    children: ReactNode;
}) {
    return (
        <section className="space-y-3">
            <div>
                <h3 className="text-lg font-semibold">{title}</h3>
                <p className="text-sm text-muted-foreground">{description}</p>
            </div>
            {children}
        </section>
    );
}

/**
 * What the report is focused on, e.g. "All workspaces › 2026 Fashion ›
 * Summer collection". Each step goes back up.
 */
function ScopePath({
    scope,
    onFocus,
}: {
    scope: ReportScope | null;
    onFocus: (kind: ReportLevel | null, id: number | null) => void;
}) {
    const steps: {
        kind: ReportLevel | null;
        id: number | null;
        name: string;
    }[] = [
        { kind: null, id: null, name: 'All workspaces' },
        ...(scope?.path ?? []),
    ];

    return (
        <nav
            aria-label="Report focus"
            className="flex flex-wrap items-center gap-1 text-sm"
        >
            <span className="mr-1 text-muted-foreground">Showing</span>
            {steps.map((step, i) => {
                const isCurrent = i === steps.length - 1;

                return (
                    <span
                        key={`${step.kind}-${step.id}`}
                        className="flex items-center gap-1"
                    >
                        {i > 0 && (
                            <ChevronRight
                                className="size-3.5 text-muted-foreground"
                                aria-hidden
                            />
                        )}
                        {isCurrent ? (
                            <span
                                className="rounded-md bg-secondary px-2 py-0.5 font-semibold text-secondary-foreground"
                                aria-current="page"
                            >
                                {step.name}
                            </span>
                        ) : (
                            <button
                                type="button"
                                onClick={() => onFocus(step.kind, step.id)}
                                className="rounded-md px-2 py-0.5 text-primary underline-offset-4 hover:underline"
                            >
                                {step.name}
                            </button>
                        )}
                    </span>
                );
            })}
        </nav>
    );
}

function MaterialsTable({ materials }: { materials: MaterialUsage[] }) {
    const money = useMoney();

    return (
        <div className="rounded-xl border bg-card">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead className="pl-4">Material</TableHead>
                        <TableHead className="text-right">
                            Quantity used
                        </TableHead>
                        <TableHead className="text-right">
                            Price per unit
                        </TableHead>
                        <TableHead className="pr-4 text-right">
                            Total spend
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {materials.map((material) => (
                        <TableRow key={material.id}>
                            <TableCell className="pl-4 font-medium">
                                {material.name}
                            </TableCell>
                            <TableCell className="text-right tabular-nums">
                                {material.quantity} {material.unit}
                            </TableCell>
                            <TableCell className="text-right tabular-nums">
                                {money(material.unit_price)}
                            </TableCell>
                            <TableCell className="pr-4 text-right tabular-nums">
                                {money(material.cost)}
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}

ReportsIndex.layout = {
    breadcrumbs: [{ title: 'Reports', href: index() }],
};
