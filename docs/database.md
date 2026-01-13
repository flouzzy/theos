# Database Schema

Le Rocher Académie uses PostgreSQL 15 with Doctrine ORM.

## Core Entities

### User
Primary user entity for authentication and authorization.

**Fields:**
- `id`: Primary key (auto-increment)
- `email`: Unique email address
- `password`: Hashed password
- `roles`: JSON array of roles
- `firstname`, `lastname`, `fullname`
- `username`: Unique username (slug)
- `image`: Profile picture path
- `bio`: User biography
- `birth_date`: Date of birth
- `score`: Gamification score
- `payment_status`: Payment status enum
- `is_verified`: Email verification flag
- `created_at`, `updated_at`: Timestamps

**Relationships:**
- Many courses (authored)
- Many completions
- Many cohorts (enrolled)
- Many notifications
- Many notes

### Course
Learning courses with structured content.

**Fields:**
- `id`: Primary key
- `title`: Course title
- `slug`: URL-friendly slug
- `description`: Course description
- `image`: Course thumbnail
- `status`: Published status
- `created_at`, `updated_at`

**Relationships:**
- Belongs to User (author)
- Has many Modules
- Has many Completions
- Belongs to many Cohorts

### Module
Course sections organizing lessons.

**Fields:**
- `id`: Primary key
- `title`: Module title
- `description`: Module overview
- `position`: Sort order
- `created_at`, `updated_at`

**Relationships:**
- Has many Lessons
- Belongs to many Courses
- Has many Completions

### Lesson
Individual learning units.

**Fields:**
- `id`: Primary key
- `title`: Lesson title
- `content`: Lesson content (rich text)
- `video_url`: Optional video
- `duration`: Estimated duration
- `position`: Sort order
- `created_at`, `updated_at`

**Relationships:**
- Belongs to Module
- Has many Completions
- Has many Comments
- Has many Notes

### Completion
Tracks user progress.

**Fields:**
- `id`: Primary key
- `completed_at`: Completion timestamp
- `progress`: Progress percentage (0-100)

**Relationships:**
- Belongs to User
- Polymorphic: Course, Module, or Lesson

### Cohort
Scheduled course groups.

**Fields:**
- `id`: Primary key
- `title`: Cohort name
- `slug`: URL slug
- `start_at`: Start date
- `capacity`: Maximum students
- `status`: Published status
- `created_at`, `updated_at`

**Relationships:**
- Has many Users (students)
- Has many Courses

### Notification
User notifications.

**Fields:**
- `id`: Primary key
- `title`: Notification title
- `message`: Notification content
- `type`: Notification type
- `read_at`: Read timestamp
- `send_at`/`sent_at`: Scheduling
- `created_at`, `updated_at`

**Relationships:**
- Belongs to User

### Badge
Achievements and awards.

**Fields:**
- `id`: Primary key
- `name`: Badge name
- `slug`: URL slug
- `description`: Badge description
- `image`: Badge icon
- `rarity`: Badge rarity level
- `created_at`, `updated_at`

**Relationships:**
- Belongs to BadgeType
- Awarded to many Users

### Other Entities

- **Comment**: Lesson comments
- **Note**: Personal user notes
- **Page**: Static CMS pages
- **PaymentSetting**: Payment configuration
- **ResetPasswordRequest**: Password reset tokens
- **Calendar**: Calendar events

## Migrations

### Creating Migrations

```bash
# Generate migration from entity changes
docker compose exec php bin/console make:migration

# Review generated migration
cat migrations/VersionYYYYMMDDHHIISS.php

# Execute migration
docker compose exec php bin/console doctrine:migrations:migrate
```

### Migration Best Practices

1. **Review Before Execution**: Always review generated migrations
2. **Test Locally First**: Run migrations on dev before production
3. **Backup Production**: Always backup before migrating production
4. **Rollback Plan**: Ensure `down()` methods work

## Indexes

Key indexes for performance:
- `user.email` (unique)
- `user.username` (unique)
- `course.slug` (unique)
- `course.author_id` (foreign key)
- `completion.user_id, completable_type, completable_id` (composite)

## Database Maintenance

### Backup

```bash
# Backup database
docker compose exec database pg_dump -U app app > backup.sql

# Restore database
docker compose exec database psql -U app app < backup.sql
```

### Vacuum & Analyze

```bash
# Optimize database
docker compose exec database psql -U app -d app -c "VACUUM ANALYZE"
```

## ER Diagram

```
User ──< Completion >── Course/Module/Lesson
User ──< Note >── Lesson
User ──< Comment >── Lesson
User ──< Notification
User ──< Badge
User ──< Cohort >── Course
Course ──< Module ──< Lesson
```

## Next Steps

- Review [Architecture](architecture.md)
- Learn about [Development](development.md)
- See [Migration Guide](installation.md#migrations)
