# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Install dependencies
composer install

# Start dev server
php artisan serve

# Run all migrations (fresh DB)
php artisan migrate:fresh --seed

# Run migrations only
php artisan migrate

# Run all tests
php artisan test

# Run a single test class or method
php artisan test --filter ExampleTest
php artisan test --filter "ExampleTest::test_it_does_something"

# Code style (Laravel Pint)
./vendor/bin/pint

# Artisan tinker (REPL)
php artisan tinker

# Clear all caches
php artisan optimize:clear
```

## Architecture

### What this is

A **pure JSON REST API** — no Blade views. It backs a separate frontend SPA. All routes live under `auth:sanctum` middleware (Bearer token). Login/logout at `POST /api/login` and `POST /api/logout` issue and revoke Sanctum tokens.

The system manages two overlapping business verticals from a single codebase:
- **Gold/jewelry shop** — live gold rates, karat/weight pricing, making charges, buybacks, scrap, day-end physical counts
- **Liquor bar/restaurant** — table-based orders, draft bills, open-bottle tracking, bottle deposits

### Multi-branch scoping

Every resource has a `branch_id`. This pattern appears in every controller index query:

```php
->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
```

`isAdmin()` returns `true` for both `admin` and `owner` roles. Staff roles (`manager`, `cashier`, `store_keeper`) are always scoped to their assigned branch. All create operations set `branch_id` from `$request->user()->branch_id`.

### Roles and granular permissions

Roles: `admin`, `owner`, `manager`, `cashier`, `store_keeper`.

Two extra boolean flags exist on `User` independent of role: `can_override_gold_rate` and `can_delete_transactions`. Check via `$user->canOverrideGoldRate()` / `$user->canDeleteTransactions()`.

### Sale lifecycle

Sales have two statuses: `draft` and `completed`.

**Draft sales** do not: decrement stock, create `BottleDeposit` records, post accounting journal entries. They can be updated via `PUT /api/sales/{sale}` (only works while `status === 'draft'`).

**Completed sales** trigger all three side effects atomically inside a `DB::transaction`. The `SaleController::store()` method handles this in one method with a `$isDraft` flag.

### The three side-effect services

Every significant mutation should call all three that apply:

| Service | When to call |
|---|---|
| `StockLedger::record()` | Any time `stock_quantity` changes on a `Product` |
| `AccountingService::post*()` | Sales, GRNs, supplier returns, damage reports, salary, income/expense |
| `AuditLog::record()` | Any major state change (sale created/deleted, draft updated, etc.) |

`AccountingService::post()` is **idempotent** — it queries for an existing journal entry by `(source_type, source_id)` before creating. Safe to call multiple times.

### Liquor: open-bottle tracking

When a liquor sale includes `serving_ml > 0` and the product type is `liquor`/`whisky`/`vodka`, `SaleController::handleOpenBottlePour()` is called instead of direct stock decrement. It finds an existing open bottle with enough remaining volume, or auto-opens a new one (decrementing stock by 1 bottle). The open bottle's `remaining_volume_ml` is reduced by the serving amount.

### Gold pricing

`GoldRate::$karatPurity` holds purity multipliers for `9k`/`14k`/`18k`/`22k`/`24k`. Gold value = `rate_per_gram × weight_g × purity`. During a sale, if the frontend sends `gold_value = 0`, the controller auto-calculates from today's rate via `GoldRate::today()`. Making charges can be `per_gram`, `per_piece`, or `percentage`.

### Accounting chart of accounts

Account codes used by `AccountingService` constants:

| Constant | Code | Account |
|---|---|---|
| `ACC_CASH` | 1000 | Cash |
| `ACC_AR` | 1100 | Accounts Receivable |
| `ACC_INVENTORY` | 1200 | Inventory |
| `ACC_AP` | 2000 | Accounts Payable |
| `ACC_TAX_PAYABLE` | 2100 | Tax Payable |
| `ACC_BOTTLE_DEPOSIT` | 2200 | Bottle Deposit Liability |
| `ACC_SALES` | 4000 | Sales Revenue |
| `ACC_OTHER_INCOME` | 4100 | Other Income |
| `ACC_COGS` | 5000 | Cost of Goods Sold |
| `ACC_DAMAGE_EXP` | 5100 | Damage Expense |
| `ACC_SALARY_EXP` | 5200 | Salary Expense |
| `ACC_OPERATING_EXP` | 5300 | Operating Expense |

### Product image storage

`ProductController` uploads to Cloudinary via `CloudinaryService::uploadProductImage()`, storing `image` (URL) and `image_public_id` (for deletion). The `Product::getImageAttribute()` accessor handles both full Cloudinary URLs and legacy local-storage paths transparently. When updating an image, always call `CloudinaryService::destroyImage($product->image_public_id)` before uploading the replacement.

### Key environment variables

Beyond standard Laravel vars, configure:

```
CLOUDINARY_CLOUD_NAME=
CLOUDINARY_API_KEY=
CLOUDINARY_API_SECRET=
CLOUDINARY_FOLDER=products
CLOUDINARY_VERIFY=true   # set false to skip SSL verification in dev
```

Currency throughout the system is **LKR (Sri Lankan Rupees)**.

### Support classes (`app/Support/`)

- `AccountingService` — static methods for posting double-entry journals
- `StockLedger` — static `record()` helper that writes `StockMovement` rows
- `CloudinaryService` — static upload/destroy wrappers around the Cloudinary REST API

### `app/Http/Controllers/Api/` structure

All API controllers live here. The `authorizeBranch()` private method appears in most controllers to enforce branch isolation for non-admin users — always add this guard when accessing a resource by ID.

---

## Frontend SPA (`resources/js/`)

Vue 3 Composition API (`<script setup>`) with vue-router and Axios. All pages are in `resources/js/pages/`, shared components in `resources/js/components/`.

### POS — New Bill page (`pages/NewSale.vue`)

Full POS two-panel layout (`height: calc(100vh - 60px)`):
- **Left panel (56–60%)** — product card grid with category tabs, search input, barcode/camera scanner. Cards highlight amber with qty badge when item is in bill. Out-of-stock cards are disabled/faded.
- **Right panel (40–44%)** — table + customer selectors, scrollable bill items list, pinned payment/totals footer.

Bill item rows include: product thumbnail, name, price, `−/+` qty buttons, inline `serving_ml` pill picker for Liquor products, bottle deposit checkbox, item discount input.

Payment footer is collapsible — TOTAL row always visible, discount/tax expand on click (`showPricingDetails` ref). Quick-denomination buttons are dynamically calculated from the total.

### Draft edit flow

Clicking **Edit** on a draft sale navigates to `/sales/new?draft={id}`. `NewSale.vue` reads `route.query.draft` on mount and calls `loadDraft()` automatically. The old `EditDraft.vue` page (`/sales/:id/edit`) still exists but is no longer linked from the UI.

### Products page (`pages/Products.vue`)

- **Loader** — `loading` ref wraps `fetchProducts()`; while true, the table is replaced by a spinner.
- **Image zoom** — clicking a product thumbnail sets `zoomedImage` ref; a `<Teleport>`ed lightbox renders the full image with a blurred backdrop and fade transition. Click backdrop or press Escape to close.

### Dashboard (`pages/Dashboard.vue`)

5-row layout using vue-chartjs + chart.js v3:

| Row | Content |
|---|---|
| 1 | 6 KPI cards (today revenue, month revenue, purchases, pending, low stock, customers) |
| 2 | Revenue Trend line chart (30 days, dual-axis) + **Fast Moving Items** card with product thumbnails |
| 3 | Payment Methods donut + Category Sales donut + Busiest Hours bar |
| 4 | Recent Bills full-width table |
| 5 | Low Stock Alerts table (hidden when empty) |

**Fast Moving Items** (`data.top_products`) — shows product thumbnail, name, scaled progress bar, `×N sold`, and revenue. The `DashboardController` now includes `products.image` in the `top_products` SELECT.

### `sale_items` schema addition

`serving_ml decimal(8,2) default 0` was added via migration `2026_05_12_234307_add_serving_ml_to_sale_items_table.php`. The column is in `SaleItem::$fillable` and `$casts` (as `float`). Both `SaleController::store()` and `SaleController::update()` persist it.
