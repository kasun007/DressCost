import { Head } from '@inertiajs/react';
import ItemController from '@/actions/App/Http/Controllers/ItemController';
import Heading from '@/components/heading';
import ItemForm from '@/components/item-form';
import { show } from '@/routes/projects';
import {
    index as workspacesIndex,
    show as showWorkspace,
} from '@/routes/workspaces';
import type { Material, Option, Project, SavedItem } from '@/types';

type Props = {
    project: Pick<Project, 'id' | 'name'>;
    workspace: Option;
    materials: Material[];
    types: string[];
    costTypes: string[];
    savedItems: SavedItem[];
};

export default function ItemCreate({
    project,
    materials,
    types,
    costTypes,
    savedItems,
}: Props) {
    return (
        <>
            <Head title="Add item" />

            <div className="space-y-6 p-4">
                <Heading
                    title="Add item"
                    description={`Work out the cost of a new item in ${project.name}.`}
                />

                <ItemForm
                    materials={materials}
                    types={types}
                    costTypes={costTypes}
                    savedItems={savedItems}
                    initial={{
                        name: '',
                        type: types[0],
                        profit_margin: 0,
                        quantity_small: 0,
                        quantity_medium: 0,
                        quantity_large: 0,
                        quantity_xl: 0,
                        materials: [],
                        costs: [
                            { name: 'Labour', amount: '' },
                            { name: 'Overhead', amount: '' },
                        ],
                    }}
                    submitTo={ItemController.store(project.id)}
                    cancelHref={show.url(project.id)}
                />
            </div>
        </>
    );
}

ItemCreate.layout = ({ project, workspace }: Props) => ({
    breadcrumbs: [
        { title: 'Workspaces', href: workspacesIndex() },
        { title: workspace.name, href: showWorkspace(workspace.id) },
        { title: project.name, href: show(project.id) },
        { title: 'Add item', href: ItemController.create(project.id) },
    ],
});
