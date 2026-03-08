# Todo List

- [x] **Dynamic Cohorts & Course Visibility**
    - [x] Update `Course` entity with visibility field (public/restricted)
    - [x] Create `CohortSession` service to manage active cohort in session
    - [x] Add a `cohort_switch` route to change the active cohort
    - [x] Update `CohortController` to filter courses by active cohort and visibility
    - [x] Update UI:
        - [x] Dynamic "Welcome" message (Promo name instead of hardcoded 2024)
        - [x] Cohort switcher component (if user has > 1 cohort)
        - [x] Filter course listing based on active cohort
- [x] **Dynamic Brevo Integration & Automation**
    - [x] Create/Enrich `Setting` entity for `brevo_list_onboarded` and `brevo_list_alumni`
    - [x] Implement Admin interface for managing these Brevo List IDs
    - [x] Refactor `BrevoService` (BrevoApi) to fetch List IDs from database instead of `.env`
    - [x] Implement `RegistrationSubscriber` to add students to "Onboarded" list upon validation
    - [x] Implement `TrainingCompletionSubscriber` to move students from "Onboarded" to "Alumni" list
    - [x] Add tests for Brevo list management and automation logic
- [x] Implement Gold Standard Workflow
- [x] Verify Implementation
