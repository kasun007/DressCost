import type { SizeKey, SizeQuantities } from '@/lib/sizes';

/** Saved quantity per size, as stored on an item. */
type SizeQuantitiesData = { [K in keyof SizeQuantities]: number };

export type Cost = {
    material_cost: number;
    other_cost: number;
    total_cost: number;
    selling_price: number;
    profit: number;
};

export type Material = {
    id: number;
    name: string;
    unit: string;
    unit_price: number;
    items_count?: number;
};

export type Workspace = {
    id: number;
    name: string;
    description: string | null;
};

/** Workspaces show counts only; costs are shown on projects and items. */
export type WorkspaceSummary = Workspace & {
    projects_count: number;
    items_count: number;
};

/** Projects show their total cost; selling price and profit are per item. */
export type ProjectSummary = {
    id: number;
    name: string;
    description?: string | null;
    items_count: number;
    total_cost: number;
};

export type Project = {
    id: number;
    workspace_id: number;
    name: string;
    description: string | null;
};

/** A previously saved item whose costing a new item can start from. */
export type SavedItem = SizeQuantitiesData & {
    id: number;
    name: string;
    type: string;
    profit_margin: number;
    project: string;
    materials: { material_id: number; quantity: number }[];
    costs: { name: string; amount: number }[];
};

export type ItemMaterialLine = {
    id: number;
    name: string;
    unit: string;
    unit_price: number;
    quantity: number;
    cost: number;
};

export type ItemCostLine = {
    id: number;
    name: string;
    amount: number;
};

export type ItemDetail = {
    id: number;
    name: string;
    type: string;
    /** When the item was saved; this dates its costs. */
    saved_on: string;
    profit_margin: number;
    /** Pieces to make per size, e.g. { small: 10, medium: 20, ... }. */
    quantities: Record<SizeKey, number>;
    /** Total pieces across all sizes. */
    quantity: number;
    materials: ItemMaterialLine[];
    costs: ItemCostLine[];
    /** Cost of one piece (every size costs the same). */
    cost: Cost;
    /** Cost of all the pieces. */
    all_pieces: Cost;
};

export type ItemMaterialRow = {
    material_id: number | '';
    quantity: number | '';
};

export type ItemCostRow = {
    name: string;
    amount: number | '';
};

export type ItemFormData = SizeQuantities & {
    name: string;
    type: string;
    profit_margin: number | '';
    materials: ItemMaterialRow[];
    costs: ItemCostRow[];
};

export type Option = {
    id: number;
    name: string;
};

/** A project with its full name ("2026 Fashion › Summer collection") and its own name. */
export type ProjectOption = Option & {
    short_name: string;
    workspace_id: number;
};

export type ReportFilters = {
    workspace_id: number | null;
    project_id: number | null;
    cost_type: string | null;
    from: string | null;
    to: string | null;
};

/** Selling price and profit are null when the report is filtered by costing type. */
export type CostTotals = {
    items_count: number;
    total_cost: number;
    selling_price: number | null;
    profit: number | null;
};

export type ReportSummary = CostTotals & {
    projects_count: number;
};

export type CategoryTotal = {
    name: string;
    amount: number;
};

export type ProjectBreakdown = CostTotals & {
    id: number;
    name: string;
    last_saved_on: string | null;
    categories: CategoryTotal[];
    items: {
        id: number;
        name: string;
        type: string;
        saved_on: string;
        total_cost: number;
    }[];
};

/** Costs of the items saved in one year. */
export type YearTotal = {
    year: string;
    items_count: number;
    total_cost: number;
};

export type MaterialUsage = {
    id: number;
    name: string;
    unit: string;
    unit_price: number;
    quantity: number;
    cost: number;
};

/** One side of a project comparison, including the project's sub-projects. */
export type ComparisonSide = {
    id: number;
    /** Short display name, e.g. the item's name. */
    name: string;
    /** Where it lives, e.g. "2026 Fashion › Summer collection, saved 9 Oct 2026". */
    detail: string | null;
    items_count: number;
    total_cost: number;
    categories: CategoryTotal[];
};

export type ReportItemRow = {
    id: number;
    name: string;
    type: string;
    saved_on: string;
    total_cost: number;
};

export type ReportLevel = 'workspace' | 'project';

/** A workspace (with its projects) or a project (with its items), costs rolled up. */
export type ReportNode = {
    kind: ReportLevel;
    id: number;
    name: string;
    items_count: number;
    total_cost: number;
    categories: CategoryTotal[];
    children: ReportNode[];
    items: ReportItemRow[];
};

export type ReportPathStep = {
    kind: ReportLevel;
    id: number;
    name: string;
};

/** The workspace or project a report is focused on, with its path. */
export type ReportScope = {
    kind: ReportLevel;
    name: string;
    path: ReportPathStep[];
};

export type CompareKind = 'workspace' | 'project' | 'item';
