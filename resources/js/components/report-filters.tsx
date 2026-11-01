import { X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { visitReport } from '@/lib/report-visit';
import type { Option, ProjectOption, ReportFilters as Filters } from '@/types';

type Props = {
    filters: Filters;
    workspaces: Option[];
    projects: ProjectOption[];
    categories: string[];
};

const ALL = 'all';

/**
 * Workspace, project, costing type and date range filters. Every change
 * reloads the report and is kept in the URL, so it can be bookmarked.
 */
export default function ReportFilters({
    filters,
    workspaces,
    projects,
    categories,
}: Props) {
    const apply = (changes: Partial<Filters>) =>
        visitReport({ ...filters, ...changes });

    const isFiltered = Object.values(filters).some((value) => value !== null);

    // The project list follows the chosen workspace.
    const workspaceId =
        filters.workspace_id ??
        projects.find((p) => p.id === filters.project_id)?.workspace_id ??
        null;
    const projectChoices = workspaceId
        ? projects.filter((p) => p.workspace_id === workspaceId)
        : projects;

    return (
        <div className="grid gap-4 rounded-xl border bg-card p-4 sm:grid-cols-2 xl:grid-cols-[1fr_1fr_1fr_9.5rem_9.5rem_auto] xl:items-end">
            <div className="grid gap-2">
                <Label htmlFor="filter-workspace">Workspace</Label>
                <Select
                    value={workspaceId ? String(workspaceId) : ALL}
                    onValueChange={(value) =>
                        apply({
                            workspace_id: value === ALL ? null : Number(value),
                            project_id: null,
                        })
                    }
                >
                    <SelectTrigger id="filter-workspace" className="w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={ALL}>All workspaces</SelectItem>
                        {workspaces.map((workspace) => (
                            <SelectItem
                                key={workspace.id}
                                value={String(workspace.id)}
                            >
                                {workspace.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="filter-project">Project</Label>
                <Select
                    value={
                        filters.project_id ? String(filters.project_id) : ALL
                    }
                    onValueChange={(value) =>
                        apply({
                            project_id: value === ALL ? null : Number(value),
                            workspace_id: value === ALL ? workspaceId : null,
                        })
                    }
                >
                    <SelectTrigger id="filter-project" className="w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={ALL}>All projects</SelectItem>
                        {projectChoices.map((project) => (
                            <SelectItem
                                key={project.id}
                                value={String(project.id)}
                            >
                                {project.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="filter-type">Costing type</Label>
                <Select
                    value={filters.cost_type ?? ALL}
                    onValueChange={(value) =>
                        apply({ cost_type: value === ALL ? null : value })
                    }
                >
                    <SelectTrigger id="filter-type" className="w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={ALL}>All costing types</SelectItem>
                        {categories.map((category) => (
                            <SelectItem key={category} value={category}>
                                {category}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="filter-from">From</Label>
                <Input
                    id="filter-from"
                    type="date"
                    value={filters.from ?? ''}
                    max={filters.to ?? undefined}
                    onChange={(e) => apply({ from: e.target.value || null })}
                />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="filter-to">To</Label>
                <Input
                    id="filter-to"
                    type="date"
                    value={filters.to ?? ''}
                    min={filters.from ?? undefined}
                    onChange={(e) => apply({ to: e.target.value || null })}
                />
            </div>

            <Button
                variant="ghost"
                disabled={!isFiltered}
                onClick={() =>
                    apply({
                        workspace_id: null,
                        project_id: null,
                        cost_type: null,
                        from: null,
                        to: null,
                    })
                }
            >
                <X /> Clear filters
            </Button>
        </div>
    );
}
