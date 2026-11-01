# DressCost — Frontend Design

## Look & feel

- **Indigo and zari**, from hand-loom textiles: indigo dye with gold thread. All tokens live in `resources/css/app.css` (`:root` and `.dark`).
    - Deep indigo sidebar framing a light lavender-grey canvas (`--background`), with white cards.
    - Indigo `--primary` for actions. Zari gold (`--zari`, `--sidebar-primary`) is used sparingly: the logo tile, the active nav "thread" marker, and the selling-value chip.
    - `--positive` green for profit only.
- One typeface (Instrument Sans). Money uses `tabular-nums` and goes through `useMoney()`, in the `APP_CURRENCY` currency.
- The summary tiles (`cost-summary-cards`) tell total cost, selling value and profit apart with tinted icon chips: indigo, gold and green.

## Charts

- Recharts via the shadcn `chart` component.
- Colours come from 8 validated categorical slots (`--chart-1` to `--chart-8`), with separate light and dark versions. `lib/chart-series.ts` assigns them in the fixed order of the full project or costing-type list, so a colour always follows the same entity when filters change. Past 8 names, the tail folds into a grey "Other".
- Costs over time use stacked monthly columns; projects are compared with horizontal stacked bars. Segments have a 2px card-coloured gap.
- Every chart has a legend and a money tooltip. `category-totals` and the breakdown tables act as the table view, which matters because three light-mode slots are below 3:1 contrast.

## Sign-in pages

- `/` goes straight to the login page (or the dashboard when logged in); there is no marketing front page.
- Login, register, forgot/reset password and two-factor pages share `layouts/auth/auth-brand-layout.tsx`: an indigo panel with a woven twill texture, a gold stitched seam, the DressCost mark and a one-line promise, beside the form. On phones the panel shrinks to a header with the logo.

## Navigation

The sidebar and header share one list, `mainNavItems` in `resources/js/lib/navigation.ts`: Dashboard, Workspaces, Materials, Reports.

## Pages & layouts

| Page                         | Purpose                                                                                                                                                        |
| ---------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `dashboard`                  | Total cost, cost by year, and the 5 most recently updated projects                                                                                             |
| `workspaces/index`           | Workspace cards with project and item counts (no money). New workspace in a dialog                                                                             |
| `workspaces/show`            | The workspace's project cards (items and total cost). New project in a dialog                                                                                  |
| `projects/show`              | Total cost tile and an accordion of items showing cost, profit and selling price; opening one shows its full breakdown                                         |
| `items/create`, `items/edit` | Full-page item form with a live cost estimate. Adding an item can start from a saved item                                                                      |
| `materials/index`            | Materials table. Add and edit in a dialog                                                                                                                      |
| `reports/index`              | Filters and focus path, At a glance, Where the money goes (workspace/project table), What the money is spent on, Cost by year, Materials used                  |
| `reports/compare`            | Workspaces / Projects / Items toggle, two pickers (with swap), side-by-side totals, the difference, a grouped bar chart and a difference table by costing type |

Every page uses `AppLayout` (assigned in `app.tsx`). Breadcrumbs are set with `Page.layout`, as a function of the page props when they need record names.

## Components

- `cost-summary-cards`: the total cost / selling value / profit cards
- `confirm-delete-button`: a delete button behind a confirmation dialog
- `material-form-dialog`, `project-form-dialog`: one dialog each for create and edit
- `item-form`: shared by item create and edit

## Responsive behaviour

- Card grids go from 1 to 2 to 3 columns (`md`, `xl`).
- Tables scroll horizontally on small screens.
- On the item form, the cost estimate sits beside the form and stays in view from `lg` up, and moves below the form on smaller screens.

## References

- Pricing rules: `docs/requirements.md`
