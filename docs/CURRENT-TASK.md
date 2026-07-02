# Current Task

## Current Parent Task

C6.4 Backup, security, and regression gates

## Current Subtask

C6.4.3 Deployment checklist

## Current Status

Not Started. C6.4.2 (Comprehensive application security review) is fully completed and verified by tests. Ready to begin C6.4.3.

## Goal

Ensure the system is fully hardened against security vulnerabilities. Run a comprehensive security review matrix and build automated integration tests verifying permissions, rate limiting, CORS configuration, file upload safety, and payment webhook signature validation.

## Dependencies

- A2.3 — Role and permission model (Completed)
- A4.1 — File upload service (Completed)
- A5.3 — Payment gateway service contract (Completed)
- B3.3 — Payment webhook handling (Completed)

## Required Deliverables

1. **Security Review Test Suite**:
   - `tests/Feature/SecurityReviewTest.php` containing automated tests that assert security controls are active and correctly configured.
2. **Security Fixes/Hardening**:
   - Address any gaps discovered in permissions, rate limits, CORS headers, upload validators, or signature checks.

## Acceptance Criteria

- **Permission Matrix**: Verify all admin endpoints reject unauthorized users with `403` and allow authorized roles.
- **Rate Limiting**: Verify login, password resets, checkout, and webhook endpoints have active rate limiting.
- **CORS Configuration**: Verify CORS headers restrict requests to authorized origins.
- **File Upload Safety**: Verify file upload API blocks executable files, validates MIME types, and respects size limits.
- **Webhook Signatures**: Verify Cashfree webhook endpoint rejects payloads without correct signature headers or with spoofed hashes.

## Tests Required

- `tests/Feature/SecurityReviewTest.php`:
  - `test_admin_endpoint_permissions_matrix`
  - `test_rate_limiting_active_on_sensitive_routes`
  - `test_cors_origins_restricted`
  - `test_file_upload_extensions_and_size_restrictions`
  - `test_payment_webhook_signature_verification`

## Quality Requirements

- Clean Laravel Pint formatting.
- PHPStan static analysis with zero errors.

## Files Likely Affected

- `app/Http/Controllers/Api/PaymentWebhookController.php` (if signature check is missing or bypassable)
- `tests/Feature/SecurityReviewTest.php` (new)
