import { Form } from '@inertiajs/react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import WorkspaceController from '@/actions/App/Http/Controllers/WorkspaceController';
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
import { Textarea } from '@/components/ui/textarea';
import type { Workspace } from '@/types';

type Props = {
    workspace?: Workspace;
    trigger: ReactNode;
};

/**
 * Create a workspace, or edit one when `workspace` is given.
 */
export default function WorkspaceFormDialog({ workspace, trigger }: Props) {
    const [open, setOpen] = useState(false);

    const form = workspace
        ? WorkspaceController.update.form(workspace.id)
        : WorkspaceController.store.form();

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogTitle>
                    {workspace ? 'Edit workspace' : 'New workspace'}
                </DialogTitle>
                <DialogDescription>
                    A workspace groups related projects, such as a season (2026
                    Fashion) or a line of business (Bespoke orders).
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
                                <Label htmlFor="workspace-name">Name</Label>
                                <Input
                                    id="workspace-name"
                                    name="name"
                                    defaultValue={workspace?.name}
                                    placeholder="e.g. 2026 Fashion"
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="workspace-description">
                                    Description (optional)
                                </Label>
                                <Textarea
                                    id="workspace-description"
                                    name="description"
                                    defaultValue={workspace?.description ?? ''}
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
