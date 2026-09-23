---
paths:
  - database/seeders/AfricanUnionAdministratorsSeeder.php
---

# Database Seeders

## Keep privileged roster seeding explicit and non-destructive
Run AfricanUnionAdministratorsSeeder only as an explicit targeted production command; never call it from DatabaseSeeder. Supply its password through the transient config-backed environment variable, never source or logs. Existing identity or case collisions must fail closed, and reruns must never reset a changed password.
