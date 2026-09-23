---
paths:
  - '{app/Models/EventRegistration*.php,app/Support/*Registration*.php,app/Http/Controllers/Admin/Registration*.php,app/Console/Commands/RebuildEventRegistrationSearchIndex.php}'
---

# Commands

## Search encrypted registrations through blind tokens
Keep registration identity and professional fields encrypted. Exact country, gender, and capacity filters plus name or organisation prefix search must use the domain-separated HMAC rows in event_registration_search_tokens; never add plaintext search columns or query encrypted ciphertext. Rebuild existing rows with registrations:rebuild-search-index after the migration.
