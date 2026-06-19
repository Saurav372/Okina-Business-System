# Step 9: Business Decision Or Clarification Capture

Use when a business or operational decision is unclear.

Permission gate:

Do not code. Do not decide permanently unless the docs already provide the answer.

Prompt:

```text
Read:
1. docs/PROJECT-CONTEXT.md
2. docs/CURRENT-TASK.md
3. okina_craft_master_build_plan.md
4. docs/main-system-requirements.md or docs/feature-list.md only if needed

Do not code.

Identify business or operational decisions needed before this task can proceed safely.

For each decision, return:
1. Decision question
2. Why it matters
3. Affected tasks/modules
4. Recommended default, if there is a safe conservative default
5. Options with tradeoffs
6. Where the final answer should be documented

If all needed decisions are already clear, say that clearly.

Do not implement until I provide or approve the decision.
```