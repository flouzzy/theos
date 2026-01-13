# Architecture Overview

Le Rocher Académie follows a modern, modular architecture built on Symfony 8.

## Technology Stack

### Backend
- **Framework**: Symfony 8
- **PHP**: 8.4
- **ORM**: Doctrine ORM
- **Database**: PostgreSQL 15
- **Web Server**: FrankenPHP with Caddy

### Frontend
- **CSS Framework**: Tailwind CSS 3
- **UI Components**: Shadcn UI (Twig Components)
- **Icons**: Lucide Icons (via UX Icons)
- **Real-time**: Turbo Frames/Streams
- **Interactivity**: Alpine.js (for mobile menu)

### Infrastructure
- **Containerization**: Docker & Docker Compose
- **Email**: Symfony Mailer + Brevo
- **Queue**: Symfony Messenger
- **Cache**: Symfony Cache (File/Redis)

## Application Structure

```
academie/
├── assets/              # Frontend assets (CSS, JS, icons)
├── config/              # Symfony configuration
│   ├── packages/        # Bundle configuration
│   ├── routes/          # Route definitions
│   └── services.yaml    # Service configuration
├── migrations/          # Database migrations
├── public/              # Public web root
│   ├── images/          # Static images
│   └── index.php        # Front controller
├── src/
│   ├── Controller/      # HTTP controllers
│   ├── Entity/          # Doctrine entities
│   ├── Repository/      # Database repositories
│   ├── Form/            # Form types
│   ├── Service/         # Business logic services
│   ├── EventListener/   # Event subscribers
│   └── Twig/            # Twig components & extensions
├── templates/           # Twig templates
│   ├── components/      # Reusable UI components
│   ├── partials/        # Partial templates
│   ├── app.html.twig    # Main layout
│   └── base.html.twig   # HTML base
└── tests/               # PHPUnit tests
```

## Core Concepts

### Entities

Domain models representing database tables:
- **User**: System users (students, instructors, admins)
- **Course**: Complete learning courses
- **Module**: Course sections
- **Lesson**: Individual learning units
- **Cohort**: Scheduled course groups
- **Completion**: Progress tracking
- **Notification**: User notifications
- **Badge**: Achievements

### Services

Business logic encapsulated in services:
- **BrevoApi**: Email service integration
- **SendMail**: Email abstraction layer
- **Payment**: Payment processing
- **JWTService**: JWT token management

### Controllers

HTTP request handlers organized by feature:
- **HomeController**: Landing  & dashboard
- **CourseController**: Course management
- **LessonController**: Lesson viewing
- **ProfileController**: User profile
- **Admin/***: Admin panels

## Design Patterns

### Repository Pattern
Data access abstracted through repositories:
```php
$users = $userRepository->findByRole('ROLE_STUDENT');
```

### Service Layer
Business logic separated from controllers:
```php
$this->brevoApi->addOrUpdateContact($user);
```

### Event-Driven
Doctrine lifecycle events for automation:
```php
#[AsEntityListener(event: Events::postPersist)]
class UserListener {
    public function postPersist(User $user) {
        $this->brevoApi->addOrUpdateContact($user);
    }
}
```

## Database Schema

See [Database Documentation](database.md) for detailed schema information.

## Security

### Authentication
- Email/Password authentication
- Password reset via email
- JWT-based email verification

### Authorization
- Role-based access control (ROLE_USER, ROLE_ADMIN)
- Voters for fine-grained permissions
- Route protection via security annotations

### CSRF Protection
- Enabled for all forms
- Stateless CSRF for API endpoints

## API Design

The application primarily uses server-side rendering with Turbo Frames for dynamic updates. REST principles are followed where APIs exist.

See [API Documentation](api.md) for endpoints.

## Performance

### Caching Strategy
- Doctrine query caching
- HTTP cache with Caddy
- Template caching (production)

### Database Optimization
- Indexed foreign keys
- Eager loading for N+1 prevention
- Connection pooling

### Asset Optimization
- Tailwind CSS purging (production)
- Asset versioning
- CDN-ready assets

## Testing Architecture

See [Testing Guide](testing.md) for testing strategy and implementation.

## Deployment

See [Deployment Guide](deployment.md) for production architecture and deployment process.
