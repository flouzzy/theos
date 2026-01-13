# API Documentation

Le Rocher Académie currently uses server-side rendering with Turbo Frames for most functionality. API endpoints may be added in future iterations.

## Current API Endpoints

### Authentication

Currently handled via Symfony Security forms. Future API authentication will use JWT tokens.

### Planned API Endpoints

The following RESTful API endpoints are planned for future releases:

#### Courses
- `GET /api/courses` - List all courses
- `GET /api/courses/{id}` - Get course details
- `GET /api/courses/{id}/modules` - Get course modules

#### Lessons
- `GET /api/lessons/{id}` - Get lesson details
- `POST /api/lessons/{id}/complete` - Mark lesson complete

#### User Progress
- `GET /api/me/progress` - Get user progress
- `GET /api/me/courses` - Get enrolled courses

## Future API Features

- JSON:API format
- API rate limiting
- OAuth 2.0 authentication
- Webhook notifications
- GraphQL endpoint (consideration)

## Contributing

See [Contributing Guide](contributing.md) for how to propose API additions.

## Contact

For API partnership inquiries, contact the development team.
