# Project B/C Build Runway

Use this document when moving through Project B and Project C tasks.

The goal is to prevent mid-task blockers by making the build order explicit before `CURRENT-TASK.md` is moved.

## Why Phase B Hit Blockers

Phase A was mostly foundation-first: schemas, auth, settings, files, order/payment rules, and contracts could be built in a straight dependency chain.

Project B and Project C are workflow phases. Their tasks cross parent boundaries:

- Product customization needs cart, order item, and admin file review.
- Checkout needs cart validation, pricing, customer addresses, order creation, payment attempts, idempotency, and later admin visibility.
- Admin order screens need website checkout data before they can be useful.
- Sales orders, quotations, inventory, finance, audit, and notifications all touch the same order/payment/customer records.

The earlier task docs described those cross-links, but some rows made the whole parent task depend on another whole parent task. That created false blockers. The safer rule is to depend on the exact completed subtask or bridge point.

## Naming Rule

Use these names carefully:

- `Platform A`, `Project B`, and `Project C` are project areas in `task-list.md`.
- `Phase 1`, `Phase 2`, `Phase 3`, and `Phase 4` are release/build phases in the master plan.

Do not use "Phase B" in task docs. Say `Project B` or `Phase 1` so the dependency target is clear.

## Dependency Rules

- Do not make a parent task depend on another parent task if only one subtask is actually needed.
- Do not move `CURRENT-TASK.md` to a subtask unless every dependency listed in `subtask-validation.md` is complete.
- If a task is a bridge verification, mark it as such and run it only after the owning implementation task exists.
- If a future admin/CRM/audit module is not built yet, use a minimal integration contract or explicit placeholder only when the master plan says Phase 1 needs that bridge.
- If a dependency points to a non-existent task ID, stop and fix the planning docs before coding.
- If a parent task has deferred bridge subtasks, do not let those subtasks block a downstream parent that only needs the completed foundation.

## Current B/C Runway

### B2 Customization Bridge

Completed:

1. `B2.2.1` Design upload
2. `B2.2.2` File validation
3. `B2.2.3` Product preview
4. `B2.2.4` Placement controls
5. `B2.2.5` Customization metadata
6. `B2.2.6` Cart persistence

Deferred bridge tasks:

1. `B2.2.7` Order persistence runs after `B3.1.7` creates order item/customization storage.
2. `B2.2.8` Admin file access runs after `B2.2.7` and the basic admin order/file access surface exists.

Rule: `B3.1` may proceed after `B2.2.6`; it must not wait for `B2.2.7` or `B2.2.8`.

### B3.1 Checkout Runway

Build in this order:

1. `B3.1.2` Cart item validation
2. `B3.1.3` Price recalculation
3. `B3.1.4` Customer and address validation
4. `B3.1.5` Bulk quantity detection
5. `B3.1.6` Pending-order creation
6. `B3.1.7` Order item/customization storage
7. `B2.2.7` Order persistence bridge verification
8. `B3.1.8` Payment-attempt creation
9. `B3.1.9` Duplicate checkout prevention
10. `B3.1.10` Failed checkout handling
11. `B3.2` Website payment adapter implementation
12. `B3.3` Payment webhook handling

Rule: `B3.1.6` must not be started until `B3.1.2`, `B3.1.3`, and `B3.1.4` are complete.

Rule: `B3.1.7` owns the real order item rows. `B2.2.7` verifies that customization survives through that order item implementation.

### Bulk Flow Bridge

`B3.1.5` should first implement the checkout decision:

- total quantity below 25 may continue checkout,
- total quantity 25 or more returns a customer-safe bulk handoff response,
- no full CRM/quotation module is required inside `B3.1.5`.

Full CRM, quotation, and conversion work stays in `C3.1`, `C1.3`, and later sales-order tasks.

### Project C Runway

Build Project C in this order after the relevant B tasks exist:

1. `C1.1` Basic admin order and payment view after `B3.1` and payment records exist.
2. `C4.1` Simple order processing after `C1.1`.
3. `C4.2` Shipping details after `C4.1`.
4. `B4.1` Customer dashboard after `C1.1`.
5. `B4.2` Customer tracking after `C1.1` and `C4.1`.
6. `C3.1` CRM lead module before full quotation conversion.
7. `C1.2` Sales order creation after `C1.1`.
8. `C1.3` Quotations and bulk-order conversion after `C3.1` and sales-order creation.
9. `C5.1` Finance payment and balance views after `C1.1`.
10. `C5.2` Refund management after `C5.1`.
11. `C2.1` Inventory movements after `C1.1` and product/SKU data are stable.
12. `C2.2` Vendors and purchases after `C2.1`.
13. `C6.1` Immutable audit log after the first order/payment/inventory workflows exist.
14. `C6.2` Notifications after event definitions and core order/payment events exist.
15. `C6.3` Google Sheets backup sync after notification/job foundations and target records exist.
16. `C6.4` Backup, security, and regression gates after the above production flows exist.

## Before Moving `CURRENT-TASK.md`

Check:

- the next subtask row exists in `task-list.md`,
- all dependencies in `subtask-validation.md` are complete,
- the dependency target is not a whole parent when a specific subtask is enough,
- no dependency references a missing ID,
- any bridge task is placed after its owning implementation task,
- the build runway above agrees with the selected next task.

If any check fails, update planning docs before implementation.

## Recommended Next Task

The next unblocked implementation subtask is:

`B3.1.6 Pending-order creation`

Do not start `B3.1.6 Pending-order creation` until `B3.1.2`, `B3.1.3`, and `B3.1.4` are completed.

