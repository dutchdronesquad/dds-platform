---
paths:
  - 'app/{Http/Requests/Admin/**,Http/Controllers/Admin/**,Support/UtcDateTime.php},resources/js/components/ui/date-time-picker.tsx'
---

# Ui

## Keep admin form times offset-aware and storage UTC
DateTimePicker must submit a complete ISO timestamp derived in the browser so the user's actual UTC offset is preserved. Normalize date-time request values through UtcDateTime before persistence, keep database values in UTC, and return ISO timestamps to forms so the browser converts them for local display. Do not assume a fixed Europe/Amsterdam timezone.
