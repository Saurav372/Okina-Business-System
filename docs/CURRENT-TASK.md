# Current Task

## Current Parent Task

C5.4 Financial reports

## Current Subtask

C5.4.1 Report query engine foundation

## Current Status

Not Started. Parent Task C6.4 is fully completed. Ready to begin C5.4.1.

## Goal

Implement the financial report query engine foundation. Build database query logic and filters to retrieve aggregates and grouped results for payment, outstanding balance, refund, expense, and sales data.

## Dependencies

- C5.1 — Finance payment and balance views (Completed)
- C5.2 — Refund management (Completed)
- C5.3 — Expense management (Completed)

## Required Deliverables

1. **Query Engine Classes**:
   - Query engines and services to run aggregated reports on payments, balances, refunds, expenses, and sales data.
2. **Filters & Date Parsing**:
   - Input validation, date parsing, and grouping mechanics (by category or monthly grouping).

## Acceptance Criteria

- **Parity with API Contracts**: Grouping by category and grouping by month must return the exact JSON keys, structures, and decimal string precision specified in the master build plan/context (e.g. `currency: "INR"`, `total_amount`, `approved_amount`, etc.).
- **Query Optimizations**: No N+1 query risks. Eager load all relations. Large records must use paging or chunking where needed.

## Tests Required

- **Automated query tests** (Feature tests checking calculations and filters).

## Quality Requirements

- Laravel Pint formatting checks pass.
- PHPStan static analysis runs with zero errors.

## Files Likely Affected

- `app/Services/FinanceReportService.php` (new)
- `tests/Feature/FinanceReportTest.php` (new)
