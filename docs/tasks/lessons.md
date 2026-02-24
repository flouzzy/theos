# Lessons Learned

This file documents mistakes and lessons learned to prevent future recurrence.

## Lesson Format

- **Date:** YYYY-MM-DD
- **Mistake:** Description of the error.
- **Correction:** How it was fixed.
- **Rule:** The new rule to follow.

## Lessons

- **Date:** 2025-02-09
- **Mistake:** Assuming CI failures are always code-related without checking the exact error message.
- **Correction:** Analyzed CI failure message, identified billing issue, but still proactively fixed static analysis errors (PHPStan) found locally to ensure code quality.
- **Rule:** When CI fails due to infrastructure (e.g., billing), still verify code locally with `php -l`, `phpstan`, and tests to ensure no hidden code issues exist.
