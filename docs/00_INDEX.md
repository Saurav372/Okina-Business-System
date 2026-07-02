# Okina Business System — Documentation Index

> **Navigation hub.** Start here. Every document in this suite is listed below with a one-line description and a direct link.

---

## Documentation Conventions (quick reference)

| Convention | Rule |
|---|---|
| File naming | `NN_Title_Case.md` |
| Metadata header | Every file opens with `Last Reviewed`, `Owner`, `Source of Truth` |
| Mermaid diagrams | Quote labels containing `()` or `[]`; no HTML tags in labels |
| Cross-references | Relative paths (e.g. `./06_Database_Design.md`), not absolute |
| Update policy | Update the doc in the same PR/commit as the code change it describes |
| Authoritative source | **Code is always authoritative.** When docs and code conflict, fix the doc. |
| Money | All amounts stored as integers in minor units (paise). Display layer converts. |
| Migrations are truth | Field-level details live in `database/migrations/` — not duplicated here. |

---

## Reading Paths

| Reader | Suggested path |
|---|---|
| **New developer** | 01 → 02 → 03 → 04 → 05 → 06 → 07 → 08 → 09 → 10 → 12 |
| **Operator / DevOps** | 01 → 03 → 11 → Checklists → 13 |
| **Staff onboarding** | 01 → 14 |
| **Customer support** | 15 |

---

## Foundation

| # | Document | Description |
|---|---|---|
| 01 | [Project Overview](./01_Project_Overview.md) | What the system is, what problems it solves, feature summary |
| 02 | [Documentation Conventions](./02_Documentation_Conventions.md) | Naming, Mermaid syntax, cross-reference style, update policy |
| 03 | [System Architecture](./03_System_Architecture.md) | Component diagram, deployment diagram, module boundaries |
| 04 | [Architecture Decisions](./04_Architecture_Decisions.md) | ADRs — why major decisions were made |
| 05 | [Technology Stack](./05_Technology_Stack.md) | Current versions from `composer.json` and `package.json` |

## Development

| # | Document | Description |
|---|---|---|
| 06 | [Database Design](./06_Database_Design.md) | ER diagrams, naming conventions, module ownership, indexing strategy |
| 07 | [API Documentation](./07_API_Documentation.md) | Auth, error format, pagination, endpoint groups |
| 08 | [Business Workflows](./08_Business_Workflows.md) | Sequence diagrams for every major operational flow |
| 09 | [Module Documentation](./09_Module_Documentation.md) | Per-module ownership: models, services, policies, events, jobs |
| 10 | [Authentication & Authorization](./10_Authentication.md) | Guards, RBAC, permission flow diagram |

## Operations

| # | Document | Description |
|---|---|---|
| 11 | [Deployment Guide](./11_Deployment_Guide.md) | Environment variables, server requirements, deploy steps |
| 12 | [Testing Guide](./12_Testing_Guide.md) | PHPUnit, Pint, PHPStan runbooks |
| 13 | [Maintenance Guide](./13_Maintenance_Guide.md) | Daily ops, monitoring, backup — links to operational checklists |
| — | [Deployment Checklist](./DEPLOYMENT-CHECKLIST.md) | Step-by-step production deployment runbook |
| — | [Rollback Procedure](./ROLLBACK-PROCEDURE.md) | Step-by-step rollback and recovery runbook |
| — | [Regression Test Checklist](./REGRESSION-TEST-CHECKLIST.md) | Release gate checklist |

## End User

| # | Document | Description |
|---|---|---|
| 14 | [Staff Manual](./14_Staff_Manual.md) | Admin dashboard guide for staff operators |
| 15 | [Customer Manual](./15_Customer_Manual.md) | Customer website guide |
