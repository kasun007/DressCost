# DressCost — Business Requirements

## Purpose

Work out what it costs to make a dress (or any other garment) and what to sell it for. Costs are organised as workspaces → projects → items, so a season, a collection or an order can be priced as a whole.

## Users & roles

- A single owner uses the app. There are no staff roles.
- Every record belongs to the user who created it. A user never sees another user's data.

## Core concepts

- **Workspace**: the top level, e.g. "2026 Fashion" or "Bespoke orders". A workspace holds any number of projects.
- **Project**: a group of items inside a workspace, e.g. "Summer collection" or "Perera wedding party". A project can be moved to another workspace.
- **Item**: something made within a project. It has a type (dress, top, skirt, trousers, accessory or other), a list of materials with the quantity of each, a list of other costs, a profit margin, and **how many pieces to make in each size: Small, Medium, Large and XL** (at least one piece in total).
- **Material**: anything bought to make an item, such as a fabric, lining, zip or button. It has a name, a unit (metre, yard, piece, roll or kg) and a price per unit.
- **Cost line**: a non-material cost on an item. It has a costing type and an amount (e.g. Labour 1,500, Hand embroidery 800). The costing type is picked from existing types or typed in as a new one.

## Business rules

- **Every size costs the same per piece.** An item's costing (materials, other costs, selling price, profit) is per piece; **all pieces** = per piece × total pieces across sizes.
- **Material cost** = sum of (quantity × material unit price) across the item's materials.
- **Other costs** = sum of the item's cost line amounts.
- **Total cost** = material cost + other costs.
- **Selling price** = total cost × (1 + profit margin % / 100).
- **Profit** = selling price − total cost.
- All money is rounded to 2 decimal places.
- Costs always use a material's current unit price, so changing a price updates every item that uses that material.
- A material that is used by an item cannot be deleted. Remove it from those items first.
- Deleting a workspace deletes its projects and their items. Deleting a project deletes its items. Deleting an account deletes all of its data.
- **What each level shows**: a workspace shows only its number of projects and items (no money). A project shows its **total cost** (the sum of every item's total cost for all its pieces). Only an **item** shows **total cost, selling price and profit**, per piece and for all its pieces. Workspace totals, reports and Compare count every piece; material usage multiplies each material by the number of pieces. The dashboard shows total cost, cost by year and the most recently updated projects.
- An item lists each material at most once.
- An item lists each costing type at most once (ignoring upper/lower case).
- The costing types offered are Labour and Overhead plus every type the user has used before. A new type typed on an item is offered from then on. There is no separate page for managing types.
- A new item starts with empty Labour and Overhead lines, which can be filled in or removed.
- **Reusing saved items**: when adding an item, the user can start from any item saved before (one per name, ignoring case, using its most recently costed version). This copies its name, type, materials, other costs and margin into the form; it is dated when saved. The new item is an independent copy.
- An item is **dated by when it was saved**. Cost by year and the date filters use that date; editing an item later doesn't change it.
- On a project page, opening an item shows all its costing details: each material line, each other cost, and how they add up to the selling price.
- **Costing categories** in reports are "Materials" (all of an item's material cost) plus each costing type, such as Labour.
- **Reports** look at one level at a time: all workspaces (split by workspace), one workspace (split by its projects), or one project (split by its items). A path ("All workspaces › 2026 Fashion › Summer collection") shows the focus, and each step goes back up. There is no cost, selling value or profit summary. The report shows:
    - **At a glance**: items costed (and in how many projects), the biggest costing type, and the most costly workspace or project at this level
    - **Where the money goes**: workspaces and projects with costs rolled up, each one's share and its largest costing type. Workspaces expand to projects and projects to items, and clicking a name focuses the report on it
    - **What the money is spent on**: each costing type's total and share, and the costing types of each workspace or project side by side
    - **Cost by year**: the number of items and total cost for each year items were saved, with the change from the previous year (amount and %), and an all-years total. It follows the workspace, project, costing type and date filters
    - **Materials used**
- **Report filters**: workspace, project (listed within the chosen workspace), costing type and date range (from/to, inclusive). They can be combined and are kept in the URL.
- **Compare** (Reports → Compare): compare two **workspaces**, two **projects** or two **items**. Each side shows its total cost (plus item count and average cost per item for workspaces and projects); which costs more and by how much; and a chart and table by costing type with the difference (second minus first). The first two of the chosen kind are compared by default.
- **Demo data**: `php artisan db:seed` creates the account `demo@dresscost.test` (password `password`) with 13 materials and 3 workspaces (2025 Fashion, 2026 Fashion and Bespoke orders) holding 7 projects and 27 items spread over a year. Re-seeding resets only that account. `DemoDataSeeder::seedFor($user)` loads the same data into any account.

## Sharing

- A project's owner can **create a share link**. Anyone who has the link can open a read-only page with the project's full costing (items, sizes and quantities, materials, other costs, totals, selling prices and profit) **without logging in**. They cannot change anything.
- Creating a new link disables the previous one. **Stop sharing** disables the link immediately; it then shows "not found".
- Links are long random codes, are rate-limited and are marked so search engines don't index them. The shared page never shows the project's internal id.

## Out of scope (for now)

- Customers, orders and payments
- Multiple users or staff roles sharing data
- Price history or snapshots of past material prices
- Stock or inventory tracking
- Multiple currencies (one currency, set by `APP_CURRENCY`)
