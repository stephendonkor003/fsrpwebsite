---
paths:
  - 'resources/views/**/*.blade.php'
---

# Views

## Use complete PHP blocks in templates that contain PHP blocks
In this Laravel 13 checkout, an inline @php(expression) before a later @php ... @endphp block can be consumed as a raw PHP block, leaving assignments unexecuted and directives in the compiled view. Use complete @php ... @endphp blocks for such assignments. Verify admin content changes with GET create/edit rendering, not only POST saves.
