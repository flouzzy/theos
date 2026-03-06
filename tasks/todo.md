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
- [ ] Implement Gold Standard Workflow
- [ ] Verify Implementation
