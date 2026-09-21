---
paths:
  - '{app/Http/Requests/StoreEventRegistrationRequest.php,app/Support/SeedSummitRegistrationPdf.php,app/Mail/SeedSummitRegistration*.php,resources/views/site/seed-summit/register.blade.php,resources/views/mail/seed-summit-registration-*.blade.php}'
---

# Mail

## Require profile photo and attach PDF only after verification
Seed Summit registrations require a delegate profile photo at the form and request boundary, while photo database columns remain nullable for legacy records and the create-then-store upload transaction. Keep the encrypted upload private, normalize and embed it in the PDF in memory, and attach the full PDF only in the post-verification acknowledgement; the initial email remains a PII-free signed verification landing.
