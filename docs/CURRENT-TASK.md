# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

C5.1 Finance payment and balance views

## Current Subtask

C5.1.6 Finance authorization and calculation regression tests

## Current Status

Not Started. C5.1.5 is implemented and verified.

## Next Subtask

C5.2.1 Refund request (under C5.2 Refund management parent task)

## Goal

Develop and verify a comprehensive regression test suite validating all role-based access rules (finance/cost visibility), ledger list fields, calculation panel outcomes, and correct filter-aggregate behaviors on the finance endpoints.

## Dependencies

- C5.1.1 Finance access boundary and sensitive-field policy (Completed)
- C5.1.2 Payment and refund ledger list (Completed)
- C5.1.3 Order payment detail and balance panel (Completed)
- C5.1.4 Shared balance calculation presentation (Completed)
- C5.1.5 Finance filters, totals, and pagination (Completed)

## Required Deliverables

- Consolidated or dedicated regression test suite verifying:
  - Role-based gates for viewing payments and refunds (e.g. Finance Staff vs Sales Staff vs unauthorized users).
  - Proper filtering and pagination of both index endpoints.
  - Assertions on exact minor aggregate values in the meta block of the list response.
  - Proper masking/exclusion of sensitive fields (`gateway_fee_minor`, `net_amount_minor`) based on user permissions.
  - Order details summary calculations mapping correctness.

## Acceptance Criteria

- All access policy rules and data boundaries are fully covered in regression tests.
- Calculations in order detail summary balances remain correct and robust.
- The test suite passes cleanly with no regressions.

## Tests Required

- Run all finance-related feature tests (e.g., `FinanceBoundaryTest`, `FinanceAccessPolicyTest`, `FinanceLedgerFilterTest`, `AdminOrderDetailTest`).
- Ensure the full backend test suite (`php artisan test`) passes completely.

## Quality Requirements

- Zero N+1 query regression.
- Adhere strictly to authorization boundaries.

## Files Likely Affected

- `tests/Feature/FinanceBoundaryTest.php`
- `tests/Feature/FinanceAccessPolicyTest.php`
- `tests/Feature/FinanceLedgerFilterTest.php`

## Tasks Not Included

- Modification of database schema or controller logic (except for bug fixes discovered during testing).