---
paths:
  - 'database/**'
---

# Database

## Preserve content when changing database engines
When restoring or changing the configured database engine, inspect database/database.sqlite before seeding: it may hold the site's existing content and administrator account. Preserve business records, password hashes, relationships, and PostgreSQL ID sequences; do not overwrite existing content with demo seeders. Run destructive tests only against a separate database ending in _test.
