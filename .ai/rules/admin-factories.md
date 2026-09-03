---
paths:
  - '{resources/js/pages/admin/locations/form.tsx,app/Http/Requests/Admin/*LocationRequest.php,database/factories/LocationFactory.php}'
---

# Admin Factories

## Write location descriptions in Dutch
Location management exposes and requires one Dutch description and uses the shared MarkdownEditor preview. When editing a legacy record without nl copy, show its en copy as the initial value so it can be saved as Dutch content.
