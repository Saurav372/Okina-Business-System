# Security remediation plan

Based on the completed Codex Security scan of the current worktree: 11 findings, 6 medium and 5 low. This plan does not assume the findings have been exploited or that optional integrations are enabled in production.

## Delivery order

Implement six focused changes in the order below. Keep existing uncommitted work intact. Before each change, confirm the affected source still matches the finding and add a regression test that demonstrates the broken boundary. Each change must include legitimate-flow tests as well as rejection tests.

| Change | Findings covered | Implementation | Required acceptance checks |
| --- | --- | --- | --- |
| 1. Staff-page script injection | Refund reflected injection; stored category, variant and SKU siblings | Serialize dynamic Alpine values with context-safe JSON (`Js::from` or equivalent). Replace inline category confirmation interpolation with a handler consuming safely encoded data. Validate refund `payment_id` as an integer. Review other instances of the same quoted JavaScript pattern. | Crafted refund query values and flashed old input remain inert. Malicious category names, variant names/labels, SKU codes and barcodes remain inert when Delete/Edit is clicked. Ordinary refund selection and editing still work. Browser tests must verify no script executes. |
| 2. Administrator provisioning and invitations | Privileged invitation takeover; fixed-password bootstrap | Centralize invitation target authorization; require Super Admin authority for Super Admin targets before token generation. Stop returning activation secrets in flash/UI and deliver them to the intended recipient through a configured channel. Restrict test-account creation to local/testing; add an explicit production administrator provisioning path using an operator secret or random one-time invitation. Update deployment instructions. | A staff manager cannot reinvite a privileged target, and rejection leaves its token unchanged. Permitted invitations expire and are single-use. Production seeding creates no fixed-password administrator. Provisioning works with production dependencies, without Faker. |
| 3. Purchase-order integrity | Parent/item mismatch; protected-field mass assignment | Bind items through the supplied purchase order for update and delete, returning 404 on mismatch. Enforce mutability on the actual owning order. Replace raw request `fill()` with validated editable fields; protect parent, SKU and received quantity from ordinary edits. Retain receipt changes in the receiving workflow. | A draft parent paired with another order's item cannot update/delete it. Confirmed-order items stay immutable. Protected fields cannot be forged or reparented. Valid draft edits and receipt processing preserve totals, inventory movements and received quantities. |
| 4. Customer session enforcement | Password recovery leaves sessions active; restricted sessions can mutate | Apply customer eligibility middleware consistently to authenticated checkout and design-upload mutations and audit sibling customer routes. Bind customer sessions to a credential version and invalidate pre-reset versions on every authenticated request, or implement equivalent guard-aware revocation. Handle legacy sessions explicitly; avoid deleting staff sessions solely by a shared numeric user ID. | With two customer sessions, password reset rejects both old sessions while a fresh login succeeds. Active-to-disabled, unverified or locked transitions block checkout/upload. Valid customer flows work. Staff sessions with overlapping IDs remain valid. Remember-me behavior is covered if enabled. |
| 5. Webhook and image processing | Deduplication before signature verification; image decode without pixel limits | Authenticate webhooks using the actual provider contract before duplicate lookup, event-key reservation or sensitive responses. Retain atomic uniqueness/idempotency for authenticated events. Log invalid traffic without reserving trusted event identity. Inspect image dimensions before full decode; enforce width, height and total-pixel limits, then retain byte/MIME limits and post-decode checks. Add upload throttling as a supporting control. | An unsigned event cannot block a subsequent valid event or reveal payment state. Concurrent valid duplicates apply financial changes once. Invalid signatures make no financial mutation. Large-dimension compressed images are rejected before decoder invocation; ordinary images work and failure paths leave no orphan records/files. Test GD-enabled behavior. |
| 6. Spreadsheet output | Google Sheets formulas; courier CSV formulas | Use `RAW` for untrusted Sheets values on append and update while preserving intended numeric/date contracts. Apply a shared formula-neutralization helper to every untrusted textual courier CSV field; reconcile existing export helpers and handle leading whitespace/control characters. | Formula-leading customer names remain literal on append/update. Customer/address/courier text remains literal in CSV imports. Numbers and normal exports retain their expected format. Run an isolated spreadsheet/import compatibility check without production credentials. |

## Existing deployment cleanup

Code fixes do not repair previously created state. Prepare an operator-reviewed checklist and a dry-run inspection before making production changes:

- Identify any default seeded administrator. Secure a legitimate administrator first, then disable or rotate the default account and revoke its sessions.
- Invalidate and reissue potentially exposed privileged invitation tokens after recipient-only delivery is working. Confirm production mail delivery; a log-only mailer is insufficient.
- Inspect webhook event records created from failed authentication. Reconcile affected events against authoritative provider records before deleting reservations or replaying events; avoid duplicate settlement/refunds.
- Check purchase-item parent, SKU and received-quantity consistency against receipts and inventory movements. Investigate discrepancies rather than automatically overwriting accounting data.
- Review existing spreadsheet cells and previously distributed CSVs for formula-bearing customer data. Future encoding does not sanitize already written formulas.

These are conditional cleanup actions: source review did not establish that any affected state exists in a live deployment.

## Release and closure

1. Deliver each change with its regression tests and a review mapping it to the original finding IDs. Use separate reviewable commits or pull requests; related session changes share one change.
2. Run focused tests, then the existing application suite. Build frontend assets for the script-injection change and verify the resulting staff-page behavior in a browser.
3. Exercise the fixes in staging with separate staff/customer identities, production-like session/queue/GD configuration and sandboxed integrations. Verify middleware response codes and valid workflows.
4. Deploy code and any required session-version migration in a compatible order. Restart long-running queue workers when their code changes. Complete applicable cleanup with operator approval and retain an audit trail.
5. Monitor authorization failures, upload rejections, webhook authentication/duplicate results, invitation delivery and spreadsheet sync errors. Rollback must preserve credential rotations and other security state changes.
6. Request a focused security verification of all 11 original findings. Close each only when its exploit condition is rejected, its legitimate flow passes, and applicable existing-state cleanup is complete. Document unresolved deployment assumptions separately.

## Dependencies and implementation decisions

- Recipient-only invitation delivery depends on a functioning production delivery channel.
- Customer credential-version enforcement may need a small migration and a deliberate policy for existing sessions. Prefer predictable reauthentication over accepting unversioned sessions indefinitely.
- Pixel thresholds must be selected against normal artwork requirements and worker memory limits; resizing after decoding is not the primary protection.
- Webhook signature compatibility must follow the gateway actually used in deployment; do not replace the current algorithm with an assumed provider format.
- Google Sheets `RAW` can change automatic date interpretation. Preserve dates explicitly and test downstream reports before release.

No production writes, credential rotations, data cleanup or code fixes are authorized by this planning document alone.
