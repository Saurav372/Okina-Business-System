# Step 5: Update Current Task

Use when moving to the next subtask.

Permission gate:

Documentation-only changes are allowed when requested. Do not implement the new subtask.

Prompt:

```text
Read:
1. docs/task-list.md
2. docs/subtask-validation.md

Prepare docs/CURRENT-TASK.md for this subtask:
[PUT SUBTASK ID AND NAME HERE]

Include only:
- Current Parent Task
- Current Subtask
- Current Status
- Goal
- Dependencies
- Required Deliverables
- Acceptance Criteria
- Tests Required
- Quality Requirements
- Files Likely Affected
- Tasks Not Included
- Reference Details

Keep it narrow.
Do not copy unrelated project information.
Do not implement the subtask after updating the file.
```