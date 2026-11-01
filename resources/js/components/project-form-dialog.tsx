import { Form } from '@inertiajs/react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
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
import { Textarea } from '@/components/ui/textarea';
import type { Option, Project } from '@/types';

type Props = {
    /** The workspace a new project goes into. */
    workspaceId: number;
    /** All workspaces, so a project can be moved when editing. */
    workspaces: Option[];
    project?: Project;
    trigger: ReactNode;
};

/**
 * Create a project in a workspace, or edit one (including moving it to
 * another workspace) when `project` is given.
 */
export default function ProjectFormDialog({
    workspaceId,
    workspaces,
    project,
    trigger,
}: Props) {
    const [open, setOpen] = useState(false);
    const [workspace, setWorkspace] = useState(
        String(project?.workspace_id ?? workspaceId),
    );

    const form = project
        ? ProjectController.update.form(project.id)
        : ProjectController.store.form();

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogTitle>
                    {project ? 'Edit project' : 'New project'}
                </DialogTitle>
                <DialogDescription>
                    A project holds the items you are costing, such as a
                    collection or a customer order.
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
                                    defaultValue={project?.name}
                                    placeholder="e.g. Summer collection"
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="workspace_id">Workspace</Label>
                                <input
                                    type="hidden"
                                    name="workspace_id"
                                    value={workspace}
                                />
                                <Select
                                    value={workspace}
                                    onValueChange={setWorkspace}
                                >
                                    <SelectTrigger
                                        id="workspace_id"
                                        className="w-full"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {workspaces.map((option) => (
                                            <SelectItem
                                                key={option.id}
                                                value={String(option.id)}
                                            >
                                                {option.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.workspace_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="description">
                                    Description (optional)
                                </Label>
                                <Textarea
                                    id="description"
                                    name="description"
                                    defaultValue={project?.description ?? ''}
                                    rows={3}
                                />
                                <InputError message={errors.description} />
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
