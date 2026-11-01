import { Head, Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { Pencil, Plus } from 'lucide-react';
import ItemController from '@/actions/App/Http/Controllers/ItemController';
import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
import ConfirmDeleteButton from '@/components/confirm-delete-button';
import CostSummaryCards from '@/components/cost-summary-cards';
import Heading from '@/components/heading';
import ItemCostPanel from '@/components/item-cost-panel';
import ProjectFormDialog from '@/components/project-form-dialog';
import ShareProjectDialog from '@/components/share-project-dialog';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useMoney } from '@/hooks/use-money';
import { formatDate } from '@/lib/dates';
import { toLabel } from '@/lib/labels';
import { cn } from '@/lib/utils';
import { show } from '@/routes/projects';
import {
    index as workspacesIndex,
    show as showWorkspace,
} from '@/routes/workspaces';
import type { ItemDetail, Option, Project } from '@/types';

type Props = {
    project: Project;
    workspace: Option;
    items: ItemDetail[];
    /** The project's total cost; selling price and profit are shown per item. */
    totalCost: number;
    workspaceOptions: Option[];
    /** The read-only share link, or null when not shared. */
    shareUrl: string | null;
};

export default function ProjectShow({
    project,
    workspace,
    items,
    totalCost,
    workspaceOptions,
    shareUrl,
}: Props) {
    const money = useMoney();

    const addItemButton = (
        <Button asChild>
            <Link href={ItemController.create(project.id)}>
                <Plus /> Add item
            </Link>
        </Button>
    );

    return (
        <>
            <Head title={project.name} />

            <div className="space-y-6 p-4 md:p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={project.name}
                        description={project.description ?? undefined}
                    />
                    <div className="flex gap-2">
                        <ShareProjectDialog
                            projectId={project.id}
                            shareUrl={shareUrl}
                        />
                        <ProjectFormDialog
                            project={project}
                            workspaceId={workspace.id}
                            workspaces={workspaceOptions}
                            trigger={
                                <Button variant="outline">
                                    <Pencil /> Edit
                                </Button>
                            }
                        />
                        <ConfirmDeleteButton
                            form={ProjectController.destroy.form(project.id)}
                            title={`Delete ${project.name}?`}
                            description="The project and all of its items will be permanently deleted."
                            label="Delete"
                        />
                    </div>
                </div>

                <CostSummaryCards
                    cost={{
                        total_cost: totalCost,
                        selling_price: null,
                        profit: null,
                    }}
                />

                {items.length === 0 && (
                    <div className="rounded-xl border border-dashed bg-card p-8 text-center">
                        <p className="text-sm text-muted-foreground">
                            No items yet. Add a dress, or start from an item you
                            have saved before.
                        </p>
                        <div className="mt-4">{addItemButton}</div>
                    </div>
                )}

                {items.length > 0 && (
                    <section className="space-y-3">
                        <div className="flex items-center justify-between gap-4">
                            <div>
                                <h3 className="text-lg font-semibold">Items</h3>
                                <p className="text-sm text-muted-foreground">
                                    Select an item to see its full cost
                                    breakdown.
                                </p>
                            </div>
                            {addItemButton}
                        </div>

                        {
                            <Accordion
                                type="multiple"
                                className="overflow-hidden rounded-xl border bg-card"
                            >
                                {items.map((item) => (
                                    <AccordionItem
                                        key={item.id}
                                        value={String(item.id)}
                                        className="data-[state=open]:bg-muted/40"
                                    >
                                        <AccordionTrigger className="items-center px-4 hover:no-underline md:px-5">
                                            <div className="flex min-w-0 flex-1 flex-wrap items-center gap-x-3 gap-y-1">
                                                <span className="font-semibold">
                                                    {item.name}
                                                </span>
                                                <Badge variant="secondary">
                                                    {toLabel(item.type)}
                                                </Badge>
                                                <span className="text-xs font-normal text-muted-foreground">
                                                    Saved{' '}
                                                    {formatDate(item.saved_on)}
                                                </span>
                                            </div>
                                            <RowFigure label="Pieces">
                                                {item.quantity}
                                            </RowFigure>
                                            <RowFigure
                                                label="Total cost"
                                                className="hidden sm:block"
                                            >
                                                {money(
                                                    item.all_pieces.total_cost,
                                                )}
                                            </RowFigure>
                                            <RowFigure
                                                label="Profit"
                                                className="hidden text-positive md:block"
                                            >
                                                {money(item.all_pieces.profit)}
                                            </RowFigure>
                                            <RowFigure
                                                label="Selling value"
                                                className="w-32 font-semibold"
                                            >
                                                {money(
                                                    item.all_pieces
                                                        .selling_price,
                                                )}
                                            </RowFigure>
                                        </AccordionTrigger>
                                        <AccordionContent className="px-4 md:px-5">
                                            <ItemCostPanel
                                                projectId={project.id}
                                                item={item}
                                            />
                                        </AccordionContent>
                                    </AccordionItem>
                                ))}
                            </Accordion>
                        }
                    </section>
                )}
            </div>
        </>
    );
}

/** A labelled figure in an item row, e.g. "Total cost" over its amount. */
function RowFigure({
    label,
    className,
    children,
}: {
    label: string;
    className?: string;
    children: ReactNode;
}) {
    return (
        <div className={cn('text-right', className)}>
            <p className="text-xs font-normal text-muted-foreground">{label}</p>
            <p className="tabular-nums">{children}</p>
        </div>
    );
}

ProjectShow.layout = ({ project, workspace }: Props) => ({
    breadcrumbs: [
        { title: 'Workspaces', href: workspacesIndex() },
        { title: workspace.name, href: showWorkspace(workspace.id) },
        { title: project.name, href: show(project.id) },
    ],
});
