# AGENTS.md - Le Rocher Académie Workflow Standards

## 1. General Agent Instructions
*Placeholder for general instructions.*

## 2. Code Conventions
*Placeholder for coding standards.*

## 3. Testing Standards
*Placeholder for testing requirements.*

## 4. Documentation
*Placeholder for documentation guidelines.*

## 5. Git Workflow
*Placeholder for git workflow rules.*

## 6. Workflow Orchestration

### Plan Node Default
Always begin any non-trivial task with a clear, step-by-step plan. Use the `set_plan` tool to outline your approach. Do not proceed with execution until the plan is reviewed and solid. Break down complex tasks into smaller, manageable chunks.

### Subagent Strategy
Utilize subagents for parallel analysis or independent subtasks where appropriate. This allows for specialized focus and faster execution. However, ensure coordination and integration of their outputs.

### Self-Improvement Loop
Continuous improvement is mandatory. After completing a task or encountering a significant issue, reflect on the process. Update `tasks/lessons.md` with key takeaways to prevent repeating mistakes.

### Verification Before Done
Never mark a task as complete without explicit verification. Use read-only tools (`read_file`, `grep`, `ls`, etc.) to confirm that changes were applied correctly and had the intended effect. Trust, but verify.

### Demand Elegance (Balanced)
Strive for elegant, clean, and maintainable solutions. Reject "good enough" if a better, more robust solution is feasible within reasonable constraints. Code should be idiomatic and easy to understand.

### Autonomous Bug Fixing
If a fix fails, do not blindly retry. Analyze the root cause deeply. Use debugging tools and logs to understand *why* it failed. Apply a different strategy if the first one didn't work.

## Core Principles

### Simplicity First
Avoid over-engineering. The simplest solution that fully addresses the problem is usually the best. Complexity increases maintenance burden.

### No Laziness (Root Cause Analysis)
Do not just patch symptoms. Dig deep to find the underlying cause of an issue. A true fix addresses the root, preventing recurrence.

### Minimal Impact
Make surgical changes. Avoid modifying unrelated parts of the codebase. Ensure that your changes do not introduce regressions in other areas.

## 7. Security Guidelines
*Placeholder for security rules.*

## 8. Performance Optimization
*Placeholder for performance guidelines.*

## 9. Task Management Lifecycle

### 1. Plan First
Define the goal and the steps required to achieve it. Create a clear plan before writing code.

### 2. Verify Plan
Review the plan against requirements and constraints. Ensure it is feasible and covers edge cases.

### 3. Track Progress
Keep `tasks/todo.md` updated as you complete steps. This provides a clear status of the work.

### 4. Explain Changes
Document *why* changes are being made, not just *what* is being changed. Context is crucial for future maintainers.

### 5. Document Results
Update code comments and project documentation to reflect the changes. Ensure knowledge is captured.

### 6. Capture Lessons
Add new learnings to `tasks/lessons.md`. Share knowledge to improve future performance.
