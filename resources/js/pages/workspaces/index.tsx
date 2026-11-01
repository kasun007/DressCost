import { Head } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import Heading from '@/components/heading';
import SummaryCard from '@/components/summary-card';
import { Button } from '@/components/ui/button';
import WorkspaceFormDialog from '@/components/workspace-form-dialog';
import { index, show } from '@/routes/workspaces';
import type { WorkspaceSummary } from '@/types';

export default function WorkspacesIndex({
    workspaces,
}: {
    workspaces: WorkspaceSummary[];
}) {
    return (
        <>
            <Head title="Workspaces" />

            <div className="space-y-6 p-4 md:p-6">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Workspaces"
                        description="A workspace holds projects, and each project holds the items you cost."
                    />
                    <WorkspaceFormDialog
                        trigger={
                            <Button>
                                <Plus /> New workspace
                            </Button>
                        }
                    />
                </div>

                {workspaces.length === 0 ? (
                    <p className="rounded-xl border border-dashed bg-card p-8 text-center text-sm text-muted-foreground">
                        No workspaces yet. Create one, such as 2026 Fashion,
                        then add projects to it.
                    </p>
                ) : (
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {workspaces.map((workspace) => (
                            <SummaryCard
                                key={workspace.id}
                                href={show(workspace.id)}
                                name={workspace.name}
                                description={workspace.description}
                                stats={[
                                    {
                                        label: 'Projects',
                                        value: String(workspace.projects_count),
                                    },
                                    {
                                        label: 'Items',
                                        value: String(workspace.items_count),
                                    },
                                ]}
                            />
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

WorkspacesIndex.layout = {
    breadcrumbs: [{ title: 'Workspaces', href: index() }],
};
