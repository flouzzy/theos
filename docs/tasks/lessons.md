# Lessons Learned

This file documents mistakes and lessons learned to prevent future recurrence.

## Lesson Format

- **Date:** YYYY-MM-DD
- **Mistake:** Description of the error.
- **Correction:** How it was fixed.
- **Rule:** The new rule to follow.

## Lessons

- **Date:** 2025-01-26
- **Mistake:** Hardcoded badge type strings in CompletionService.
- **Correction:** Replaced with constants in BadgeType entity.
- **Rule:** Define public constants for identifiers in Entity classes to improve maintainability and type safety.

- **Date:** 2025-01-26
- **Mistake:** Assuming CI failure is code-related.
- **Correction:** Checked annotations for billing errors ("recent account payments have failed").
- **Rule:** Verify locally when CI is broken due to billing limits; do not attempt to fix non-code issues.
