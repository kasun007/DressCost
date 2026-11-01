import { Head, Link } from '@inertiajs/react';
import { ArrowUpRight } from 'lucide-react';
import CostByYear from '@/components/cost-by-year';
import CostSummaryCards from '@/components/cost-summary-cards';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useMoney } from '@/hooks/use-money';
import { formatDate } from '@/lib/dates';
import { dashboard } from '@/routes';
import { show } from '@/routes/projects';
import { index as reportsIndex } from '@/routes/reports';
import { index as workspacesIndex } from '@/routes/workspaces';
import type { ProjectBreakdown, ReportSummary, YearTotal } from '@/types';

type Props = {
    summary: Pick<
        ReportSummary,
        'projects_count' | 'items_count' | 'total_cost'
    >;
    years: YearTotal[];
    recentProjects: ProjectBreakdown[];
};

export default function Dashboard({ summary, years, recentProjects }: Props) {
    const money = useMoney();

    return (
        <>
            <Head title="Dashboard" />

            <div className="space-y-6 p-4 md:p-6">
                <Heading
                    title="Dashboard"
                    description={`${summary.projects_count} projects and ${summary.items_count} items saved so far.`}
                />

                <CostSummaryCards
                    cost={{
                        total_cost: summary.total_cost,
                        selling_price: null,
                        profit: null,
                    }}
                />

                <div className="grid gap-6 xl:grid-cols-3">
                    <Card className="xl:col-span-2">
                        <CardHeader className="flex flex-row items-start justify-between gap-4">
                            <div className="space-y-1.5">
                                <CardTitle>Cost by year</CardTitle>
                                <CardDescription>
                                    By the year items were saved.
                                </CardDescription>
                            </div>
                            <Button variant="ghost" size="sm" asChild>
                                <Link href={reportsIndex()}>
                                    Full report <ArrowUpRight />
                                </Link>
                            </Button>
                        </CardHeader>
                        <CardContent>
                            <CostByYear years={years} />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-start justify-between gap-4">
                            <div className="space-y-1.5">
                                <CardTitle>Recent projects</CardTitle>
                                <CardDescription>
                                    Most recently updated first.
                                </CardDescription>
                            </div>
                            <Button variant="ghost" size="sm" asChild>
                                <Link href={workspacesIndex()}>All</Link>
                            </Button>
                        </CardHeader>
                        <CardContent>
                            {recentProjects.length === 0 ? (
                                <p className="py-6 text-center text-sm text-muted-foreground">
                                    No projects yet.{' '}
                                    <Link
                                        href={workspacesIndex()}
                                        className="font-medium text-primary underline underline-offset-4"
                                    >
                                        Create your first workspace
                                    </Link>
                                    .
                                </p>
                            ) : (
                                <ul className="-mx-2 divide-y">
                                    {recentProjects.map((project) => (
                                        <li key={project.id}>
                                            <Link
                                                href={show(project.id)}
                                                className="flex items-center justify-between gap-4 rounded-lg px-2 py-3 transition-colors hover:bg-muted"
                                            >
                                                <div className="min-w-0">
                                                    <p className="truncate font-medium">
                                                        {project.name}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {project.items_count}{' '}
                                                        items
                                                        {project.last_saved_on &&
                                                            `, last item saved ${formatDate(project.last_saved_on)}`}
                                                    </p>
                                                </div>
                                                <span className="text-sm font-semibold tabular-nums">
                                                    {money(project.total_cost)}
                                                </span>
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
