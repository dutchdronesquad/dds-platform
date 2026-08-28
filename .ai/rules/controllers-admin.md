---
paths:
  - 'app/{Http/Requests/Admin/**,Http/Controllers/Admin/**,Support/LocalDateTime.php}'
---

# Controllers Admin

## Keep admin form times local and storage UTC
DateTimePicker values represent Europe/Amsterdam wall time. Normalize every admin date-time input through LocalDateTime before validation and persistence, keep database values in UTC, and use LocalDateTime::forForm when returning editable values to admin forms.
