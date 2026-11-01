import { Head } from '@inertiajs/react';
import { Pencil, Plus } from 'lucide-react';
import WorkspaceController from '@/actions/App/Http/Controllers/WorkspaceController';
import ConfirmDeleteButton from '@/components/confirm-delete-button';
import Heading from '@/components/heading';
import ProjectFormDialog from '@/components/project-form-dialog';
import SummaryCard from '@/components/summary-card';
import { Button } from '@/components/ui/button';
import WorkspaceFormDialog from '@/components/workspace-form-dialog';
import { useMoney } from '@/hooks/use-money';
import { show as showProject } from '@/routes/projects';
import { index, show } from '@/routes/workspaces';
import type { ProjectSummary, Workspace } from '@/types';

type Props = {
    workspace: Workspace;
    projects: ProjectSummary[];
};

export default function WorkspaceShow({ workspace, projects }: Props) {
    const money = useMoney();

    const newProjectButton = (
        <ProjectFormDialog
            workspaceId={workspace.id}
            workspaces={[{ id: workspace.id, name: workspace.name }]}
            trigger={
                <Button>
                    <Plus /> New project
                </Button>
            }
        />
    );

    return (
        <>
            <Head title={workspace.name} />

            <div className="space-y-6 p-4 md:p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={workspace.name}
                        description={workspace.description ?? undefined}
                    />
                    <div className="flex gap-2">
                        <WorkspaceFormDialog
                            workspace={workspace}
                            trigger={
                                <Button variant="outline">
                                    <Pencil /> Edit
                                </Button>
                            }
                        />
                        <ConfirmDeleteButton
                            form={WorkspaceController.destroy.form(
                                workspace.id,
                            )}
                            title={`Delete ${workspace.name}?`}
                            description="The workspace, all of its projects and all of their items will be permanently deleted."
                            label="Delete"
                        />
                    </div>
                </div>

                <section className="space-y-3">
                    <div className="flex items-center justify-between gap-4">
                        <div>
                            <h3 className="text-lg font-semibold">Projects</h3>
                            <p className="text-sm text-muted-foreground">
                                Open a project to add items and their costing.
                            </p>
                        </div>
                        {projects.length > 0 && newProjectButton}
                    </div>

                    {projects.length === 0 ? (
                        <div className="rounded-xl border border-dashed bg-card p-8 text-center">
                            <p className="text-sm text-muted-foreground">
                                No projects yet. Add one, such as Summer
                                collection, to start costing items.
                            </p>
                            <div className="mt-4">{newProjectButton}</div>
                        </div>
                    ) : (
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            {projects.map((project) => (
                                <SummaryCard
                                    key={project.id}
                                    href={showProject(project.id)}
                                    name={project.name}
                                    description={project.description}
                                    stats={[
                                        {
                                            label: 'Items',
                                            value: String(project.items_count),
                                        },
                                        {
                                            label: 'Total cost',
                                            value: money(project.total_cost),
                                        },
                                    ]}
                                />
                            ))}
                        </div>
                    )}
                </section>
            </div>
        </>
    );
}

WorkspaceShow.layout = ({ workspace }: Props) => ({
    breadcrumbs: [
        { title: 'Workspaces', href: index() },
        { title: workspace.name, href: show(workspace.id) },
    ],
});
