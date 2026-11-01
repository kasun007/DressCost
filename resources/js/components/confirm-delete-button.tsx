import { Form } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
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
import type { RouteFormDefinition } from '@/wayfinder';

type Props = {
    form: RouteFormDefinition<'post'>;
    title: string;
    description: string;
    label?: string;
};

/**
 * A delete button that asks for confirmation before submitting.
 */
export default function ConfirmDeleteButton({
    form,
    title,
    description,
    label,
}: Props) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button
                    variant={label ? 'destructive' : 'ghost'}
                    size={label ? 'default' : 'icon'}
                    aria-label={label ?? 'Delete'}
                >
                    <Trash2 />
                    {label}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>{title}</DialogTitle>
                <DialogDescription>{description}</DialogDescription>

                <Form {...form} options={{ preserveScroll: true }}>
                    {({ processing }) => (
                        <DialogFooter className="gap-2">
                            <DialogClose asChild>
                                <Button variant="secondary">Cancel</Button>
                            </DialogClose>

                            <Button
                                type="submit"
                                variant="destructive"
                                disabled={processing}
                            >
                                Delete
                            </Button>
                        </DialogFooter>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
