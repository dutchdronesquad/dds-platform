---
paths:
  - 'app/Http/Controllers/Public/{Article,Event}Controller.php'
---

# Controllers Public

## Preview saved draft content without publishing
Article and event previews render only persisted content through authenticated, policy-authorized routes. Mark previews visibly, set noindex and nofollow, open them in a separate tab, disable preview while the form is dirty, and never change status or published_at.
