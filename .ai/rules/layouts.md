---
paths:
  - '{config/seed_summit.php,app/Http/Requests/StoreEventRegistrationRequest.php,app/Models/Event.php,resources/views/site/**,resources/views/layouts/site.blade.php}'
---

# Layouts

## Keep Seed Summit applications closed at every entry point
New Seed Summit applications are closed by the committed seed_summit.registration_open=false setting. Enforce closure before POST validation/storage and suppress all public registration calls to action in every locale, including external event registration URLs. Keep signed receipts, email verification and administration of existing registrations available.
