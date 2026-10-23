---
paths:
  - '{app/Http/Controllers/Admin/UserController.php,app/Http/Controllers/AuthController.php,app/Http/Middleware/EnsureAdmin.php,app/Http/Requests/Admin/*User*.php,app/Models/User.php,routes/web.php}'
---

# Models

## Revoke user access across every session driver
User administration is restricted to active administrators through dedicated user endpoints, never the generic content writer. Disabling login, removing administrator access or changing a password increments session_version, rotates the remember token and removes configured database sessions. Login records the session version and EnsureAdmin rejects stale versions even after reactivation; legacy sessions default to version zero. Preserve auth.session and server-side self-lockout protection.
