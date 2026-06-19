# Dashboard Authentication Flow

Status: A2.1 implementation baseline with A2.3 role/permission access model plus future hardening plan.

This document captures the approved dashboard authentication direction. The current implementation covers the first safe baseline: staff-only login, generic errors, rate limiting, temporary lockout, verified email checks, protected admin routes, logout, password reset entrypoints, and profile/security route placeholders.

## Routes

- `/admin/login`
- `/admin/forgot-password`
- `/admin/reset-password/{token}`
- `/admin/two-factor-challenge`
- `/admin/profile`
- `/admin/security`
- `POST /admin/logout`

There must be no public dashboard registration page. Staff accounts must be created only by the Super Admin or an authorized Admin.

## Login Requirements

A user may enter the dashboard only when:

- `user_type = staff`
- `status = active`
- email is verified
- the account is not temporarily locked
- a dashboard role is assigned
- the role has dashboard access through the role/permission model
- 2FA is complete when required by the later 2FA task

Login errors must stay generic:

```text
The provided credentials are incorrect or your account cannot access the dashboard.
```

Do not reveal whether the email exists, the password is wrong, or the account is disabled.

## Staff Statuses

- `invited`: must activate account and create password
- `active`: can log in
- `suspended`: temporarily blocked
- `locked`: blocked because of security attempts
- `disabled`: permanently blocked until re-enabled

Do not delete staff users when someone leaves. Disable the account so historical records remain connected to the correct employee.

## Login Protection

Baseline implemented:

- Validate email and password.
- Limit to 5 failed attempts within 5 minutes per email/IP.
- Track failed login attempts.
- Set `locked_until` after repeated failures.
- Reset failed count and lock after successful login.
- Regenerate the session ID after login.

Future hardening:

- Add configurable temporary delays.
- Add security notifications for suspicious login attempts.
- Add audit entries for login success/failure once audit storage exists.

## Password Reset

Baseline implemented:

- Staff can request reset from `/admin/forgot-password`.
- The response is generic whether the account exists or not.
- Reset tokens expire after 30 minutes.
- Password reset clears failed attempts and temporary lock.
- Password reset deletes existing database sessions for that staff user.

Future hardening:

- Ensure reset links are staff invitation-aware.
- Add audit events for reset request and completion.
- Add notification templates once notification implementation exists.

## Staff Invitation

Future task:

1. Super Admin creates staff account.
2. Super Admin or authorized Admin selects role.
3. Invitation email is sent.
4. Staff opens activation link.
5. Staff creates password.
6. Email becomes verified.
7. Staff configures 2FA when required.
8. Account becomes active.

Do not send temporary passwords through email or WhatsApp.

## Two-Factor Authentication

Future task:

- Mandatory for Super Admin, Admin, and Finance Staff.
- Recommended for Sales Staff, Inventory Staff, and Production Staff.
- Use authenticator-app codes, not SMS.
- Provide QR setup, manual setup key, verification code field, recovery codes, and recovery code regeneration.

The current `/admin/two-factor-challenge` route is a placeholder target only.

## Role Redirects

Implemented access model:

- Super Admin, Admin, Sales Staff, Inventory Staff, Finance Staff, and Production Staff can be granted dashboard access through roles and permissions.

Future redirect behavior:

- Super Admin: main dashboard
- Admin: main dashboard
- Sales Staff: leads/orders dashboard
- Inventory Staff: inventory dashboard
- Finance Staff: payments dashboard
- Production Staff: production dashboard

Every page and action must check permissions on the backend. Hiding a menu item is not enough.

## Profile And Security Pages

Current baseline:

- `/admin/profile` exists and is protected.
- `/admin/security` exists and is protected.

Future security page should include:

- Change password
- Enable/manage 2FA
- View active sessions
- Logout other devices
- Recent login activity
- Recovery codes

Active sessions should show browser/device, IP address, approximate location, last active time, and current device indicator.

## Sensitive Action Confirmation

Future task: ask for the current password again before actions such as:

- Changing email
- Changing password
- Disabling 2FA
- Creating a Super Admin
- Changing roles
- Issuing refunds
- Deleting payments
- Exporting customer data
- Disabling another staff account

## Audit Logging

Future audit tasks should record:

- Login success
- Login failure
- Logout
- Password reset
- 2FA enabled or disabled
- Staff account created
- Staff account disabled
- Role changed
- Permission changed
- Order status changed
- Payment updated
- Refund recorded
- Inventory adjusted
- Customer data exported

Never store passwords, reset tokens, session cookies, or 2FA secrets in audit logs.
