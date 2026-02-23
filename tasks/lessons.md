# Lessons Learned

## 2024-05-22 - Performance Optimization - Pagination
* **Mistake:** Attempting to load all records in an admin view (`CompletionController::showLessons`) caused performance issues.
* **Correction:** Implemented pagination using `Doctrine\ORM\Tools\Pagination\Paginator` in the repository and updated the controller to handle page requests.
* **Rule:** Always implement pagination for list views in admin controllers to ensure scalability.
* **Testing:** Use `TestCase` with Doctrine mocks (`EntityManager`, `QueryBuilder`, `Query`) to verify repository logic when functional tests are not feasible due to environment constraints.
