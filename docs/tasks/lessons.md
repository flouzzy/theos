# Lessons Learned

This file documents mistakes and lessons learned to prevent future recurrence.

## Lesson Format

- **Date:** YYYY-MM-DD
- **Mistake:** Description of the error.
- **Correction:** How it was fixed.
- **Rule:** The new rule to follow.

## Lessons

- **Date:** 2025-05-27
- **Mistake:** Interpreting CI failures as code defects without checking the error message.
- **Correction:** Identified that the error "The job was not started because recent account payments have failed" is an infrastructure/billing issue.
- **Rule:** CI failures with the message 'The job was not started because recent account payments have failed' indicate GitHub billing limits, not code defects. In such cases, verification should rely on local static analysis and code review.
