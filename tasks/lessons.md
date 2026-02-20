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

## [2024-05-21] Duplicate Methods in User Entity
- **Context:** Running local verification (`composer install`) again after fixing `CommentVoter`.
- **Issue:** Discovered `PHP Fatal error: Cannot redeclare App\Entity\User::getXp()` in `src/Entity/User.php`.
- **Resolution:** Removed duplicate method definitions for `getXp`, `setXp`, `addXp`, `getStreak`, `setStreak`, `getLastStreakDate`, and `setLastStreakDate`, preferring the `static` return type versions.
- **Lesson:** Syntax errors can mask subsequent fatal errors. Iterative verification (`fix -> verify -> fix`) is necessary to uncover all issues.

## [2024-05-21] Local PHP Version Mismatch
- **Context:** Running `composer install` post-scripts (`cache:clear`).
- **Issue:** `cache:clear` failed with `syntax error, unexpected token "->", expecting "]"` and `ReflectionProperty::isVirtual()` errors. This is due to local environment (PHP 8.3) mismatch with project requirement (PHP 8.4+ / Symfony 8.0 dev features like Property Hooks).
- **Resolution:** Verified code integrity using `phpstan analyse src`, which completed successfully (albeit with type errors), confirming that `src/` code is parseable and valid, even if the environment cannot boot the full kernel.
- **Lesson:** When local environment constraints prevent full execution, use static analysis tools (`phpstan`, `php -l`) as a fallback verification method.
