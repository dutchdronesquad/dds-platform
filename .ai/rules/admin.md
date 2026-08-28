---
paths:
  - 'resources/js/pages/admin/**/form.tsx'
---

# Admin

## Reset Inertia dirty baseline after save
Admin edit forms that expose isDirty or unsaved-change guards must use Form setDefaultsOnSuccess. Otherwise recentlySuccessful hides a stale dirty state briefly and the UI falls back to Nog niet opgeslagen after a successful save.
