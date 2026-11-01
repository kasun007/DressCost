import { Form } from '@inertiajs/react';
import { Check, Copy, Link2, Share2 } from 'lucide-react';
import ProjectShareController from '@/actions/App/Http/Controllers/ProjectShareController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { useClipboard } from '@/hooks/use-clipboard';

type Props = {
    projectId: number;
    /** The current share link, or null when the project isn't shared. */
    shareUrl: string | null;
};

/**
 * Create, copy or stop a project's read-only share link.
 */
export default function ShareProjectDialog({ projectId, shareUrl }: Props) {
    const [copied, copy] = useClipboard();

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button variant="outline">
                    <Share2 /> {shareUrl ? 'Shared' : 'Share'}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Share this project</DialogTitle>
                <DialogDescription>
                    Anyone with the link can see this project's full costing
                    (items, sizes, materials, costs, selling prices and profit)
                    without logging in. They can't change anything.
                </DialogDescription>

                {shareUrl ? (
                    <div className="space-y-4">
                        <div className="flex gap-2">
                            <Input
                                readOnly
                                value={shareUrl}
                                aria-label="Share link"
                                onFocus={(e) => e.currentTarget.select()}
                            />
                            <Button
                                type="button"
                                onClick={() => copy(shareUrl)}
                                aria-label="Copy link"
                            >
                                {copied === shareUrl ? <Check /> : <Copy />}
                                {copied === shareUrl ? 'Copied' : 'Copy'}
                            </Button>
                        </div>

                        <div className="flex flex-wrap justify-between gap-2">
                            <Form
                                {...ProjectShareController.store.form(
                                    projectId,
                                )}
                                options={{ preserveScroll: true }}
                            >
                                {({ processing }) => (
                                    <Button
                                        type="submit"
                                        variant="ghost"
                                        disabled={processing}
                                    >
                                        New link (old one stops working)
                                    </Button>
                                )}
                            </Form>
                            <Form
                                {...ProjectShareController.destroy.form(
                                    projectId,
                                )}
                                options={{ preserveScroll: true }}
                            >
                                {({ processing }) => (
                                    <Button
                                        type="submit"
                                        variant="destructive"
                                        disabled={processing}
                                    >
                                        Stop sharing
                                    </Button>
                                )}
                            </Form>
                        </div>
                    </div>
                ) : (
                    <Form
                        {...ProjectShareController.store.form(projectId)}
                        options={{ preserveScroll: true }}
                    >
                        {({ processing }) => (
                            <Button type="submit" disabled={processing}>
                                <Link2 /> Create share link
                            </Button>
                        )}
                    </Form>
                )}
            </DialogContent>
        </Dialog>
    );
}
