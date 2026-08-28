---
paths:
  - resources/js/components/admin/admin-form.tsx
---

# Components Admin

## Use platform dialog for dirty-form navigation
Intercept internal dirty-form GET visits with the shared platform dialog and replay the pending Inertia visit only after explicit confirmation. Keep beforeunload for refreshes, tab closes, and external navigation because browsers do not allow a custom dialog there.
