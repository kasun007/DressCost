import { Briefcase, ChartColumn, LayoutGrid, Scissors } from 'lucide-react';
import { dashboard } from '@/routes';
import { index as materialsIndex } from '@/routes/materials';
import { index as reportsIndex } from '@/routes/reports';
import { index as workspacesIndex } from '@/routes/workspaces';
import type { NavItem } from '@/types';

/**
 * The app's main navigation, shared by the sidebar and header layouts.
 */
export const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Workspaces',
        href: workspacesIndex(),
        icon: Briefcase,
    },
    {
        title: 'Materials',
        href: materialsIndex(),
        icon: Scissors,
    },
    {
        title: 'Reports',
        href: reportsIndex(),
        icon: ChartColumn,
    },
];
