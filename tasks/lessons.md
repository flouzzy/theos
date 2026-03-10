# Lessons Learned

## 2024-05-22 - Performance Optimization - Pagination
* **Mistake:** Attempting to load all records in an admin view (`CompletionController::showLessons`) caused performance issues.
* **Correction:** Implemented pagination using `Doctrine\ORM\Tools\Pagination\Paginator` in the repository and updated the controller to handle page requests.
* **Rule:** Always implement pagination for list views in admin controllers to ensure scalability.
* **Testing:** Use `TestCase` with Doctrine mocks (`EntityManager`, `QueryBuilder`, `Query`) to verify repository logic when functional tests are not feasible due to environment constraints.

## 2024-05-22 - User Entity - Name Parsing
* **Mistake:** Naive `explode(' ', $fullname)` usage in `User::setUserDetails` caused empty strings and incorrect parsing when extra spaces were present in the name.
* **Correction:** Replaced `explode` with `preg_split('/\s+/', trim($fullname))` to robustly handle whitespace.
* **Rule:** Use regex splitting (`preg_split`) when parsing user-provided strings where whitespace consistency is not guaranteed.
* **Testing:** Verified with unit tests covering edge cases like multiple spaces, single names, and missing full names.

## 2026-03-02 - Template Error - Missing Property (shortDescription)
* **Mistake:** Accessing a non-existent property `shortDescription` in `App\Entity\Course` from a Twig template (`cohort/index.html.twig`) caused a 500 error.
* **Correction:** Replaced `course.shortDescription` with `course.description|striptags|u.truncate(100, '...')` to provide a safe preview while using existing fields.
* **Rule:** Always verify that properties accessed in Twig templates exist in the corresponding Entity or DTO. Use the `u.truncate` filter from `twig/string-extra` for safe text previews.
* **Testing:** Verified by checking logs and performing a `curl` request to the affected route.

## 2026-03-02 - Template Error - Missing Property (lessons)
* **Mistake:** Accessing a non-existent property `lessons` (or `getLessons()`) in `App\Entity\Course` from `course/show.html.twig` caused a 500 error.
* **Correction:** Added a `getLessons()` method to the `Course` entity that aggregates lessons from all associated modules.
* **Rule:** When an entity has a nested relationship (Course -> Module -> Lesson), implement a getter in the parent entity to provide direct access if needed by templates, or ensure the template navigates the relationship correctly.
* **Testing:** Verified by adding the method, clearing the cache, and checking the route behavior (no more 500 error related to this property).

## 2026-03-02 - Template & Repository Errors - Missing Properties and Methods
* **Mistake 1:** Template `note/index.html.twig` tried to access `module.course`, but `Module` had a `ManyToMany` relationship with `Course` (named `courses`).
* **Correction 1:** Added a `getCourse()` method to the `Module` entity that returns the first course from the collection.
* **Mistake 2:** `EvaluationController` called `findWithScoreByUser()` on `ModuleCompletionRepository`, but the method was missing.
* **Correction 2:** Implemented `findWithScoreByUser()` in `ModuleCompletionRepository`.
* **Mistake 3:** `ProfileController` called `countTotalDurationByUser()` on `CompletionRepository`, but the method was missing.
* **Correction 3:** Implemented `countTotalDurationByUser()` in `CompletionRepository`.
* **Rule:** When dealing with `ManyToMany` relationships in templates that expect a single object, provide a convenience getter for the "main" or "first" object if appropriate. Ensure repositories implement all custom methods used in controllers.
* **Testing:** Verified by implementing the methods, clearing the cache, and checking route accessibility.

## 2026-03-02 - Route Parameter Error - Ambiguous "id" and Renaming
* **Mistake:** Using generic `{id}` for lesson routes caused confusion and errors when multiple entities were involved in the same route. Also, some mandatory parameters were missing in templates.
* **Correction:** Renamed `{id}` to `{lessonId}` in `LessonController` for all related routes (`lesson_show`, `lesson_complete`, `lesson_add_comment`) and updated all `path()` and `redirectToRoute()` calls in templates and controllers.
* **Rule:** Use descriptive parameter names in routes (e.g., `{lessonId}` instead of `{id}`) when the route context involves multiple entities to avoid ambiguity and improve readability.
* **Testing:** Verified by updating all occurrences, clearing the cache, and ensuring no "missing mandatory parameter" errors persist.

## 2026-03-02 - UI/UX Refactoring - Sidebar Navigation Unification & Stabilization
* **Mistake:** Inconsistent sidebar menus led to a disjointed user experience, and lack of base CSS classes caused layout shifts/overlaps during page load.
* **Correction:** Created a unified `partials/_sidebar_navigation.html.twig`. Added base classes `lg:w-64`, `lg:pl-64`, and `z-40` to the asides and main content areas in `app.html.twig` and `appWithBottomTabs.html.twig` to ensure layout stability before Alpine.js initialization.
* **Rule:** Factorize UI components into partials and use static CSS classes for core layout dimensions to prevent "Flicker of Unstyled Content" (FOUC) or layout breaking during JS hydration.
* **Testing:** Verified by inspecting rendering across multiple pages (Home, Courses, Notifications) and confirming the sidebar no longer overlaps the main content.

## 2026-03-06 - Intégration AI Agent - Gemini API via Symfony HttpClient
* **Feature:** Remplacement du mock du Coach par une véritable interaction avec le LLM Google Gemini.
* **Correction:** Installation de `gemini-api-php/client` couplée à `symfony/http-client`. Création d'un Service `CoachAIAgent` encapsulant l'appel vers le modèle `gemini-1.5-flash` avec un *System Instruction* pédagogique.
* **Rule:** Ne pas embarquer Guzzle si Symfony HttpClient est pré-existant/recommandé. Câbler les appels LLM en asynchrone (AJAX/Fetch) sur le Front pour ne pas bloquer le thread PHP principal lors de la génération (Streaming).
* **Testing:** Vérification du Endpoint JSON via `fetch` JS. Validation du loader dynamique et du parsing de la réponse texte.

## 2026-03-10 - Test Environment & PHPStan Hardening
* **Mistake:** Tests were failing due to low memory limit (128M) and misconfigured `test` environment (SQLite table missing, missing `APP_ENV=test` in `.env.test`).
* **Correction:** Increased PHP memory limit to 512M in Docker config. Updated `Makefile` to include database initialization in `tests` target. Added `APP_ENV=test` to `.env.test`. Fixed multiple PHPStan level 8 errors related to CSRF validation by using `$request->getPayload()->getString('_token')`.
* **Rule:** Ensure test environments match production/dev capabilities (memory, extensions). Always use strict type methods like `getString()` for request parameters to satisfy PHPStan level 8.
* **Testing:** All 157 tests now pass in the Docker environment using `make tests`.
