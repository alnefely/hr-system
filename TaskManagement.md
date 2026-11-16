# Role & Mission

Act as a Senior Software Architect, Senior Laravel Engineer, Database Architect, and Enterprise Product Designer.

Your mission is to analyze an existing Laravel-based HR Management System and integrate a complete, professional Task & Project Management Module into it.

This is NOT a new application. It is an extension of an existing production-oriented HR system.

The new module must integrate seamlessly with the existing architecture, database, authentication, employees, departments, organizational hierarchy, roles, permissions, notifications, and frontend conventions.

The goal is to build a professional internal Work Management System inspired by the best concepts of ClickUp, Asana, Monday.com, and Jira, while keeping the implementation appropriate for a company with approximately 20–50 employees.

The system must support both:
- Daily employee tasks and assignments.
- Large projects containing multiple tasks, subtasks, teams, and departments.

The application already uses Laravel. You MUST inspect the actual repository and discover its Laravel version, installed packages, frontend architecture, database structure, and coding conventions before making implementation decisions.

---

# 1. CRITICAL DEVELOPMENT RULES

These rules are mandatory.

1. Never assume the existing database structure.
2. Never create duplicate employee, department, role, permission, or authentication systems.
3. Never replace existing HR functionality with newly created implementations.
4. Never rename or delete existing database tables or columns without explicit justification and approval.
5. Reuse existing models, relationships, services, traits, policies, middleware, and packages whenever appropriate.
6. Follow the existing Laravel version and its supported conventions.
7. Follow the existing project structure instead of imposing an incompatible architecture.
8. Do not introduce unnecessary dependencies.
9. Do not implement everything inside controllers.
10. Keep business logic in dedicated services or appropriate application-layer classes.
11. Use Form Requests for validation when consistent with the existing project.
12. Use API Resources or the project's existing response transformation conventions.
13. Use database transactions for operations involving multiple related records.
14. Use queues for asynchronous and potentially expensive operations.
15. Use Laravel Policies and Gates for authorization.
16. Never trust frontend authorization. Every sensitive operation must be validated on the backend.
17. Preserve backward compatibility with existing HR features.
18. All new migrations must be safe for an existing production database.
19. Do not perform destructive database operations.
20. Do not generate fake employee records or fake production data.

IMPORTANT:
Before implementing anything, inspect the existing project and prepare a detailed integration plan.

Do not start by creating migrations or models before understanding the existing system.

---

# 2. PHASE ZERO: EXISTING SYSTEM AUDIT

Start by examining the complete project.

Inspect:

- Laravel version.
- PHP version requirements.
- composer.json and installed packages.
- Application folder structure.
- Existing Models and their relationships.
- Database migrations.
- Existing employees/users table.
- Departments and organizational structure.
- Teams, if available.
- Employee manager/supervisor relationships.
- Existing authentication system.
- Existing roles and permissions.
- Existing notification system.
- Existing FCM or push notification integration.
- Existing file upload and storage implementation.
- Existing frontend framework and component conventions.
- Existing API routes and response format.
- Existing middleware and authorization policies.
- Existing queues, events, listeners, and scheduled jobs.
- Existing dashboard and reporting architecture.
- Existing activity or audit logging mechanisms.

Pay special attention to these questions:

A. Which table represents an employee?

B. Does the employee have a department relationship?

C. Is there an existing manager or supervisor relationship?

D. Can an employee belong to multiple teams?

E. Which package or custom implementation manages roles and permissions?

F. How are API authentication and mobile sessions handled?

G. How are notifications delivered?

H. How are private files uploaded and downloaded?

I. Is there already an organizational hierarchy that can be reused?

J. Are there existing conventions for services, repositories, actions, or domain modules?

## Required Audit Deliverable

Create:

docs/task-management/system-audit.md

It must contain:

- Existing architecture overview.
- Existing database entities relevant to HR.
- Existing relationships.
- Existing authorization implementation.
- Existing notification implementation.
- Existing frontend conventions.
- Integration opportunities.
- Potential conflicts.
- Recommended architecture.

Do not invent answers. Reference actual files, models, migrations, and relationships.

After completing the audit, prepare an implementation plan and identify any architectural decisions that require approval.

---

# 3. MODULE ARCHITECTURE

Create a logically independent Task Management module integrated into the existing application.

Suggested logical components:

- Organization Integration
- Projects
- Tasks
- Task Assignments
- Task Workflows
- Comments & Discussions
- Attachments
- Activity Logs
- Approvals
- Notifications
- Time Tracking
- Reports
- Dashboard
- Automation (future-ready)

The exact folder structure must follow the existing repository conventions.

If the application uses a modular architecture, integrate accordingly.

If it uses the standard Laravel structure, do not introduce a complex module framework unnecessarily.

The Task Management module must reuse existing HR entities rather than duplicating them.

---

# 4. ORGANIZATION & EMPLOYEE INTEGRATION

The company already has an HR system.

Use existing employee records as the single source of truth.

Requirements:

- Reuse the existing employee/user model.
- Reuse existing departments.
- Reuse existing positions and job titles.
- Reuse the existing reporting manager hierarchy.
- Reuse existing employee activation and deactivation rules.
- Never duplicate employee profile information.

Organizational rules:

1. An employee belongs to one primary department, according to the existing HR structure.
2. An employee may participate in multiple teams if supported by the organization model.
3. A team belongs to a department unless the existing architecture dictates otherwise.
4. An employee may participate in projects outside their primary department.
5. A project may involve multiple departments.
6. A project manager does not necessarily have to be a department manager.
7. An employee may have different responsibilities in different projects.
8. Inactive employees must not receive new task assignments.
9. Existing task history must remain accessible when an employee becomes inactive.
10. Organizational changes must not destroy historical task assignments.

If teams do not exist, implement them as an extension of the existing organization structure.

Do not create unnecessary organizational entities if equivalent functionality already exists.

---

# 5. USER ROLES & PERMISSIONS

Implement the module using the existing authorization system.

Do not create a competing role/permission implementation.

Suggested roles:

- System Administrator
- Company Manager
- Department Manager
- Team Leader
- Project Manager
- Employee

These are logical responsibilities, not necessarily exclusive roles.

A user may have multiple roles depending on the existing permission system.

## Required Permission Categories

### Project Permissions

- View projects.
- Create projects.
- Edit projects.
- Archive projects.
- Delete projects.
- Manage project members.
- Assign project managers.
- View project reports.

### Task Permissions

- View tasks.
- Create tasks.
- Edit tasks.
- Delete tasks.
- Assign employees.
- Change task priority.
- Change task status.
- Submit tasks for review.
- Approve tasks.
- Request changes.
- Reopen completed tasks.

### Management Permissions

- View department tasks.
- View team tasks.
- View employee workload.
- View management dashboards.
- Manage workflows.
- Manage task statuses.
- Access activity logs.

## Data Scope Authorization

Implement authorization at two levels:

1. Action permission: Is the user allowed to perform this action?
2. Data scope: Is the user allowed to access this particular project or task?

Examples:

- An employee can view their assigned tasks.
- An employee can view tasks they created.
- A team leader can view tasks within their authorized team.
- A department manager can view department tasks.
- A project manager can manage their assigned projects.
- A company manager can access company-wide reports.
- An administrator can manage system configuration.

Avoid hardcoded role-name checks throughout the application.

Use policies and reusable authorization services.

Support configurable visibility policies.

---

# 6. PROJECT MANAGEMENT

Build a complete project management feature.

Each project should support:

- Project name.
- Description.
- Unique project reference.
- Project manager.
- Project members.
- Participating departments.
- Start date.
- Due date.
- Priority.
- Project status.
- Progress percentage.
- Project attachments.
- Project discussions.
- Project activity history.
- Project tasks.
- Project templates.
- Archive functionality.

## Project Statuses

Provide default project statuses:

- Planning
- Active
- On Hold
- Completed
- Cancelled
- Archived

Statuses should be manageable through configuration where appropriate.

## Project Membership

A project can contain employees from different departments.

Support:
- Project Manager.
- Project Member.
- Project Viewer.

Use a many-to-many relationship.

Do not assume project membership automatically grants access to every HR record.

## Project Progress

Calculate progress using task completion data.

The progress calculation must be consistent and documented.

Do not allow arbitrary progress values to override actual task progress without a clearly defined administrative mechanism.

## Project Templates

Prepare an extensible design for project templates.

A template may contain:

- Default tasks.
- Default subtasks.
- Suggested assignments.
- Relative deadlines.
- Default priorities.
- Workflow configuration.

Templates may be implemented in a later phase if necessary.

---

# 7. TASK MANAGEMENT CORE

This is the most important module.

Tasks must support both:

A. Standalone tasks that are not associated with a project.

B. Project tasks that belong to a specific project.

## Task Fields

A task should support:

- Unique task reference.
- Title.
- Description.
- Project ID (nullable).
- Parent task ID (nullable).
- Creator/Reporter.
- Primary assignee.
- Multiple additional assignees.
- Status.
- Priority.
- Start date.
- Due date.
- Estimated duration.
- Actual duration.
- Completion timestamp.
- Review requirement.
- Tags.
- Attachments.
- Comments.
- Activity history.

Use relational tables for normal business entities.

Do not store assignees, statuses, comments, or dependencies as JSON arrays inside the tasks table.

## Multiple Assignees

A task can be assigned to multiple employees.

Requirements:

- Every task has a creator.
- Every task can have a primary assignee.
- Additional employees can participate.
- Assignments must be stored in a separate relationship table.
- Assignment history should be preserved where practical.
- Reassignments must generate activity records and notifications.

For the initial implementation, completion should be managed at the task level, not independently by every assignee.

## Task Priorities

Provide:

- Urgent
- High
- Normal
- Low

Allow filtering and sorting by priority.

## Task Statuses

Provide default statuses:

- Backlog
- To Do
- In Progress
- On Hold
- In Review
- Changes Requested
- Done
- Cancelled

Use a status category system to support reporting.

For example:

- Backlog
- Not Started
- Active
- Review
- Completed
- Cancelled

The exact status names should be configurable.

Do not hardcode business logic against display labels.

## Task Subtasks

Support parent-child task relationships.

Requirements:

- A task can have multiple subtasks.
- Subtasks may have their own assignees.
- Subtasks may have independent priorities and deadlines.
- Display hierarchical task structures.
- Calculate parent task progress from child tasks.
- Prevent circular parent-child relationships.
- Validate parent completion rules.

Support nested subtasks with a reasonable maximum depth, configurable if practical.

A parent task should not be completed while incomplete subtasks remain unless an authorized override is used and the reason is recorded.

## Task Dependencies

Prepare support for dependencies between tasks.

Initially implement:

Finish-to-Start dependency.

Example:

Task B cannot begin until Task A is completed.

Prevent circular dependencies.

Other dependency types can be added later.

## Task Scheduling

Support:

- Start date.
- Due date.
- Overdue detection.
- Upcoming deadlines.
- Rescheduling.
- Deadline change history.

Overdue tasks should be calculated dynamically or through a reliable scheduled process.

Do not depend on a manually maintained overdue flag as the only source of truth.

---

# 8. WORKFLOW & APPROVAL SYSTEM

Build a reliable task lifecycle.

Suggested workflow:

Backlog → To Do → In Progress → In Review → Done

Additional transitions:

In Progress → On Hold

In Review → Changes Requested → In Progress

Any eligible state → Cancelled

Done → Reopened (authorized operation)

## Workflow Rules

- Validate every status transition on the backend.
- Define allowed transitions.
- Check the user's authorization.
- Record every status change.
- Notify relevant employees.
- Preserve historical status information.
- Do not allow unauthorized users to approve their own work when independent approval is required.

## Approval Process

Each task may optionally require approval.

Flow:

1. Employee completes the work.
2. Employee submits the task for review.
3. Task moves to In Review.
4. Authorized reviewer inspects the work.
5. Reviewer approves or requests changes.
6. If changes are requested, a reason is mandatory.
7. Employee updates the task and resubmits it.
8. Approved tasks become Done.

Support recording:

- Reviewer.
- Review decision.
- Review date.
- Review comments.
- Requested changes.

Keep approval history instead of overwriting previous decisions.

Design this feature to support more advanced approval workflows later.

---

# 9. COMMENTS & DISCUSSIONS

Every task must have a discussion section.

Features:

- Add comments.
- Edit own comments according to permissions.
- Delete comments according to permissions.
- Reply to comments.
- Mention employees using @mentions.
- Upload attachments to comments.
- Display timestamps.
- Notify mentioned employees.
- Preserve important discussion history.

Avoid storing comments inside the task description.

---

# 10. ACTIVITY LOG & AUDIT TRAIL

This feature is mandatory.

Create a task activity timeline.

Record events such as:

- Task created.
- Task updated.
- Assignee added.
- Assignee removed.
- Priority changed.
- Deadline changed.
- Status changed.
- Comment added.
- Attachment uploaded.
- Task submitted for review.
- Task approved.
- Changes requested.
- Task reopened.
- Task archived.

Each activity should contain:

- Actor.
- Related task.
- Action type.
- Old values when applicable.
- New values when applicable.
- Timestamp.
- Relevant metadata.

Use structured JSON for change snapshots when appropriate.

Keep business activity history separate from security-sensitive administrative audit logs if the existing application already supports this distinction.

Activity records should not be editable by ordinary employees.

---

# 11. NOTIFICATIONS

Integrate with the existing notification infrastructure.

Do not create a separate notification engine if one already exists.

Support:

- In-app notifications.
- Push notifications.
- Existing FCM integration, if available.
- Optional email notifications.

Important notification events:

- New task assignment.
- Assignee changed.
- Task deadline changed.
- New comment.
- Employee mentioned.
- Upcoming deadline.
- Overdue task.
- Review requested.
- Review approved.
- Changes requested.
- Task reopened.

Requirements:

- Avoid duplicate notifications.
- Avoid unnecessary notification spam.
- Respect notification preferences.
- Use queues for asynchronous notifications.
- Support read/unread states.
- Support notification pagination.
- Notify the correct recipients according to project and task permissions.

---

# 12. FILE ATTACHMENTS

Reuse the existing upload and storage architecture.

Support attachments on:

- Projects.
- Tasks.
- Comments.

Requirements:

- Validate MIME types.
- Validate maximum file sizes.
- Use secure file naming.
- Store private attachments securely.
- Prevent unauthorized downloads.
- Use temporary signed URLs where appropriate.
- Record uploader and upload timestamp.
- Support deletion according to permissions.
- Never expose private storage paths directly.

Do not store uploaded file contents in the database.

---

# 13. TIME TRACKING

Prepare an extensible time tracking system.

Support:

- Estimated time.
- Actual time.
- Manual time entries.
- Employee-specific time records.
- Optional start/stop timer.

Each time entry should include:

- Task.
- Employee.
- Start time.
- End time.
- Duration.
- Description.
- Creation timestamp.

Prevent invalid durations and duplicate active timers for the same employee when using a timer.

Time tracking should be an optional feature, not a requirement for completing every task.

---

# 14. TASK VIEWS & FRONTEND EXPERIENCE

First inspect the current frontend stack.

Do not introduce a new frontend framework without justification.

The UI must be responsive and work well on desktop, tablet, and mobile.

Build the following views:

## My Tasks

Show the current employee's tasks.

Include:

- Assigned to me.
- Created by me.
- Due today.
- Upcoming.
- Overdue.
- In progress.
- Awaiting my review.

## All Tasks

Allow authorized users to search and filter tasks.

Filters:

- Project.
- Department.
- Team.
- Assignee.
- Creator.
- Status.
- Priority.
- Start date.
- Due date.
- Tags.

Support sorting and pagination.

## List View

Display tasks in a structured table/list.

Support:

- Search.
- Filters.
- Sorting.
- Pagination.
- Quick status updates.
- Task detail navigation.

## Kanban Board

Display tasks grouped by status.

Support drag and drop when permitted.

Validate every drag-and-drop status transition on the backend.

Do not update the UI as if the operation succeeded before receiving a successful API response.

## Calendar View

Display task deadlines and scheduled work.

Support monthly and weekly navigation where practical.

## Project Details

Display:

- Project overview.
- Project progress.
- Project members.
- Tasks.
- Activity timeline.
- Attachments.
- Project settings.

## Task Details

Provide a comprehensive task detail interface with:

- Description.
- Status.
- Priority.
- Assignees.
- Dates.
- Subtasks.
- Dependencies.
- Comments.
- Attachments.
- Activity timeline.
- Approval actions.

Use a clean, professional interface inspired by modern project management tools.

Avoid unnecessary visual complexity.

---

# 15. DASHBOARDS & REPORTING

Build dashboards based on the existing user's permissions and organizational scope.

## Employee Dashboard

- My tasks.
- Tasks due today.
- Overdue tasks.
- Upcoming deadlines.
- Tasks awaiting review.
- Recently completed tasks.

## Team Leader Dashboard

- Team task overview.
- Tasks by status.
- Overdue tasks.
- Tasks awaiting review.
- Employee workload.
- Upcoming deadlines.

## Department Manager Dashboard

- Department performance.
- Department projects.
- Task completion.
- Overdue tasks.
- Workload distribution.
- Project delays.

## Company Dashboard

- Total active projects.
- Completed projects.
- Tasks by status.
- Overdue tasks.
- Workload by department.
- Project progress.
- Overall completion trends.

## Performance Metrics

Calculate:

- Task completion rate.
- On-time completion rate.
- Average completion time.
- Overdue task count.
- Project completion rate.
- Estimated versus actual work time.

IMPORTANT:

Do not evaluate employee performance using task count alone.

Consider complexity, estimated effort, deadlines, quality, and review outcomes.

Make metric definitions explicit and consistent.

Avoid expensive database queries inside dashboard loops.

Use appropriate aggregation queries, indexes, and caching where beneficial.

---

# 16. AUTOMATION & RECURRING TASKS

Design the architecture so that future automation can be added without rewriting the core module.

Potential future features:

- Recurring tasks.
- Automated reminders.
- Automatic notifications.
- Task templates.
- Automatic status changes.
- Automatic parent progress updates.
- Rules triggered by task events.

Do not build an unnecessarily complex automation engine in the first release.

Implement straightforward scheduled reminders using Laravel Scheduler and queued jobs where appropriate.

---

# 17. DATABASE DESIGN REQUIREMENTS

Before writing migrations, prepare a complete database schema proposal.

Potential new tables include:

Projects:
- projects
- project_members
- project_departments
- project_statuses
- project_attachments

Tasks:
- tasks
- task_assignees
- task_statuses
- task_comments
- task_attachments
- task_dependencies
- task_activities
- task_time_entries
- task_approvals

Supporting tables:
- tags
- task_tags
- project_templates
- task_templates
- task_template_items

These are suggestions, not mandatory table names.

Inspect the existing database and avoid creating duplicates.

## Database Rules

- Use foreign keys.
- Use appropriate indexes.
- Use transactions for multi-record business operations.
- Use Soft Deletes where appropriate.
- Preserve important historical records.
- Use appropriate decimal/integer types for durations.
- Prevent duplicate task assignments.
- Prevent duplicate dependencies.
- Prevent circular dependencies.
- Prevent invalid parent-child relationships.
- Avoid unnecessary polymorphic relationships.
- Document every important relationship.

Use JSON only where it is appropriate, such as activity snapshots and configurable metadata.

## Timezone

Use the existing application's timezone configuration.

The company operates in Egypt.

Ensure correct handling of local task deadlines, UTC storage where appropriate, and consistent date comparisons.

Never assume the server's timezone is the same as the company's business timezone.

---

# 18. API ARCHITECTURE

The module must work with both the existing Web frontend and a Flutter mobile application.

Follow existing API conventions.

Use versioned endpoints if consistent with the application architecture.

Suggested API groups:

Organization:
- Departments.
- Teams.
- Employees.

Projects:
- Project listing.
- Project creation.
- Project details.
- Project updates.
- Project members.
- Project archiving.

Tasks:
- Task listing.
- Task creation.
- Task details.
- Task updates.
- Task assignment.
- Task status transitions.
- Task submission for review.
- Task approval.
- Change requests.
- Subtasks.
- Dependencies.
- Comments.
- Attachments.

Dashboard:
- Employee overview.
- Team overview.
- Department overview.
- Company overview.

Notifications:
- Listing.
- Read.
- Read all.

Requirements:

- Use consistent response formats.
- Use pagination.
- Validate query filters.
- Protect all endpoints with authentication and authorization.
- Avoid N+1 queries.
- Return only necessary data.
- Use eager loading appropriately.
- Keep API responses stable for mobile clients.
- Document request and response examples.

Do not implement business rules exclusively in controllers or frontend code.

---

# 19. PERFORMANCE & SCALABILITY

Although the company currently has 20–50 employees, design the module with reasonable scalability.

Requirements:

- Avoid N+1 queries.
- Use pagination.
- Use indexed filters.
- Use Redis caching when appropriate.
- Use queues for notifications and heavy jobs.
- Use events and listeners where they improve separation of responsibilities.
- Avoid excessive synchronous operations.
- Avoid loading all tasks into memory.
- Optimize dashboard aggregation.
- Prevent duplicate scheduled jobs.
- Monitor failed jobs.
- Use database transactions.
- Implement appropriate logging.

Do not introduce distributed architecture or microservices without a real need.

A well-organized Laravel Modular Monolith is preferred.

---

# 20. TESTING REQUIREMENTS

Testing is mandatory.

Follow the existing test framework and conventions.

Create tests for:

Authentication:
- Unauthenticated access is rejected.
- Authenticated users access authorized endpoints.

Authorization:
- Employees cannot access unauthorized tasks.
- Team leaders cannot access unrelated teams.
- Department managers respect department scope.
- Project managers can manage authorized projects.
- Administrators can perform authorized administrative operations.

Projects:
- Project creation.
- Project updates.
- Project membership.
- Department participation.
- Project archiving.

Tasks:
- Task creation.
- Standalone tasks.
- Project tasks.
- Multiple assignees.
- Primary assignee validation.
- Subtask creation.
- Parent-child validation.
- Dependency validation.
- Circular dependency prevention.
- Priority updates.
- Status transitions.
- Deadline changes.
- Task deletion.

Approvals:
- Review submission.
- Approval.
- Change requests.
- Unauthorized approval prevention.
- Approval history preservation.

Comments:
- Comment creation.
- Replies.
- Mentions.
- Permissions.

Notifications:
- Correct recipients.
- Duplicate prevention.
- Read/unread state.

Dashboard:
- Correct organizational scope.
- Correct completion calculations.
- Correct overdue calculations.

Test important edge cases, not only successful scenarios.

---

# 21. IMPLEMENTATION ROADMAP

Implement the module in controlled phases.

## Phase 1: Integration Foundation

- Existing system audit.
- Organization integration.
- Employee relationships.
- Permission integration.
- Database schema.
- Core project and task models.
- Core migrations.
- Basic tests.

## Phase 2: Projects & Tasks

- Project management.
- Project members.
- Task creation.
- Multiple assignees.
- Subtasks.
- Task statuses.
- Task priorities.
- Standalone tasks.
- Task assignment.

## Phase 3: Collaboration

- Comments.
- Replies.
- Mentions.
- Attachments.
- Activity logs.
- Notifications.

## Phase 4: Workflows

- Status transition rules.
- Task reviews.
- Approval workflow.
- Reopening tasks.
- Dependencies.

## Phase 5: Frontend

- My Tasks.
- All Tasks.
- Project pages.
- Task details.
- List View.
- Kanban Board.
- Calendar View.
- Responsive layouts.

## Phase 6: Management & Analytics

- Employee dashboard.
- Team dashboard.
- Department dashboard.
- Company dashboard.
- Workload.
- Reports.
- Time tracking.

## Phase 7: Advanced Features

- Recurring tasks.
- Templates.
- Automation.
- Custom fields.
- Advanced reports.

---

# 22. DOCUMENTATION

Maintain technical documentation throughout implementation.

Create:

docs/task-management/

- system-audit.md
- requirements.md
- architecture.md
- database-design.md
- authorization-matrix.md
- workflow-design.md
- api-documentation.md
- implementation-roadmap.md
- testing-guide.md

Update documentation whenever implementation decisions change.

---

# 23. EXECUTION INSTRUCTIONS

Follow this exact execution strategy:

STEP 1:
Inspect the existing repository thoroughly.

STEP 2:
Produce the system audit.

STEP 3:
Identify reusable HR entities and existing infrastructure.

STEP 4:
Prepare the complete proposed database schema and ERD.

STEP 5:
Prepare the authorization matrix and workflow rules.

STEP 6:
Present the architecture and integration plan.

STEP 7:
Identify conflicts, assumptions, and decisions requiring clarification.

STEP 8:
Implement the module incrementally, preserving the existing application.

STEP 9:
Run migrations in the appropriate development environment and execute tests.

STEP 10:
Verify existing HR functionality has not been broken.

STEP 11:
Document completed functionality and remaining work.

## Important Execution Behavior

Do not attempt to implement all phases in one uncontrolled operation.

Complete each phase with:

1. Files created or modified.
2. Database changes.
3. Business logic implemented.
4. Tests executed.
5. Test results.
6. Security considerations.
7. Remaining issues.

Do not mark a feature complete unless it has actually been implemented and tested.

Do not claim that a migration, API endpoint, notification, or UI component exists unless you have verified it in the repository.

When encountering missing information, inspect the codebase first. If a business decision cannot be inferred safely, ask a focused question instead of inventing a rule.

Never overwrite existing project files blindly.

Never run destructive production commands.

---

# FINAL OBJECTIVE

Deliver a production-quality Task & Project Management module integrated into the existing Laravel HR system.

The result should provide a unified company workspace where management can create and manage projects, assign tasks, monitor employees, track deadlines, review work, and analyze organizational productivity.

The implementation must be:

- Maintainable.
- Secure.
- Modular.
- Testable.
- Responsive.
- Compatible with the existing HR application.
- Ready for Web and Flutter integration.
- Extensible without unnecessary complexity.

Start with PHASE ZERO: SYSTEM AUDIT.

Your first response must summarize the actual existing architecture, identify reusable HR components, highlight integration risks, and present the proposed implementation plan.

Do not start coding before completing this audit.