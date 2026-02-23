# Lessons Learned

## [YYYY-MM-DD] Feature/Bug Title
- **Context:** Brief description of the task.
- **Issue:** What went wrong or was unexpected?
- **Resolution:** How was it fixed?
- **Lesson:** What should be done differently next time?

## [2024-05-21] CI Failure Analysis
- **Context:** Integrating Gold Standard workflow documentation.
- **Issue:** GitHub Actions CI failed with "The job was not started because recent account payments have failed".
- **Resolution:** Verified changes locally. Acknowledged external infrastructure issue.
- **Lesson:** Always verify local environment readiness and check for external service status when CI fails unexpectedly.

## [2024-05-21] Fatal Error in CommentVoter
- **Context:** Running local verification (`composer install`) to mitigate CI failure.
- **Issue:** Discovered a fatal error in `src/Security/Voter/CommentVoter.php`: `voteOnAttribute` signature mismatch with Symfony 8 `Voter` class.
- **Resolution:** Updated `voteOnAttribute` to include the optional `$vote` argument and imported the `Vote` class.
- **Lesson:** Local verification is crucial even when CI is down. It revealed a critical application error that would have been missed if I solely relied on CI status.

## [2025-02-22] Timing Attack on JWT Signature
- **Context:** Fixing a security vulnerability in JWT signature verification.
- **Issue:** The code used `===` for signature comparison, which is vulnerable to timing attacks.
- **Resolution:** Replaced `===` with `hash_equals()` to ensure constant-time comparison.
- **Lesson:** Always use `hash_equals()` when comparing cryptographic hashes or signatures to prevent timing attacks.
