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

- **Date:** 2026-03-08
- **Mistake:** Assuming `#[IsGranted('IS_AUTHENTICATED')]` is evaluated before Doctrine parametrization. For non-existent entities, it throws `NotFoundHttpException` instead of redirecting unauthenticated users to `/login`.
- **Correction:** Used `access_control` in `config/packages/security.yaml` to secure paths that depend on entity resolution, ensuring unauthenticated users act correctly before 404s.
- **Rule:** When securing a route that relies on `EntityValueResolver` and `MapEntity`, use `security.yaml` `access_control` rather than `#[IsGranted]` if you want unauthenticated users to be cleanly redirected to login instead of facing a 404 when querying an invalid/deleted entity.
