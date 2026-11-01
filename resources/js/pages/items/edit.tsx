import { Head } from '@inertiajs/react';
import ItemController from '@/actions/App/Http/Controllers/ItemController';
import Heading from '@/components/heading';
import ItemForm from '@/components/item-form';
import { show } from '@/routes/projects';
import {
    index as workspacesIndex,
    show as showWorkspace,
} from '@/routes/workspaces';
import type { ItemFormData, Material, Option, Project } from '@/types';

type Props = {
    project: Pick<Project, 'id' | 'name'>;
    workspace: Option;
    materials: Material[];
    types: string[];
    costTypes: string[];
    item: ItemFormData & { id: number };
};

export default function ItemEdit({
    project,
    materials,
    types,
    costTypes,
    item,
}: Props) {
    const { id, ...initial } = item;

    return (
        <>
            <Head title={`Edit ${item.name}`} />

            <div className="space-y-6 p-4">
                <Heading
                    title={`Edit ${item.name}`}
                    description={`Part of ${project.name}.`}
                />

                <ItemForm
                    materials={materials}
                    types={types}
                    costTypes={costTypes}
                    initial={initial}
                    submitTo={ItemController.update({
                        project: project.id,
                        item: id,
                    })}
                    cancelHref={show.url(project.id)}
                />
            </div>
        </>
    );
}

ItemEdit.layout = ({ project, workspace, item }: Props) => ({
    breadcrumbs: [
        { title: 'Workspaces', href: workspacesIndex() },
        { title: workspace.name, href: showWorkspace(workspace.id) },
        { title: project.name, href: show(project.id) },
        {
            title: item.name,
            href: ItemController.edit({ project: project.id, item: item.id }),
        },
    ],
});
