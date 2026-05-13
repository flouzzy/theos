# Theos

**Theos** is a modern Learning Management System (LMS) built with Symfony 8, designed to deliver engaging online courses with a seamless user experience.
The platform enables educators to create, manage, and deliver comprehensive courses to learners. The platform provides a rich set of features for both administrators and students, including course management, progress tracking, interactive lessons, and community engagement.

## ✨ Key Features

- **📚 Course Management** - Create and organize courses with modules and lessons
- **📝 Interactive Lessons** - Rich content support with multimedia integration
- **📊 Progress Tracking** - Monitor student progress and completion rates
- **💬 Comments & Discussions** - Foster community engagement on lessons
- **📅 Calendar & Scheduling** - Manage cohorts and course schedules
- **🔔 Notifications** - Real-time updates for students and instructors
- **👤 User Profiles** - Personalized learning experiences
- **🎓 Badges & Achievements** - Gamification to motivate learners
- **💳 Payment Integration** - Manage subscriptions and course payments
- **📱 Fully Responsive** - Optimized for mobile, tablet, and desktop
- **🌐 Multi-language Support** - Internationalization ready

## 🛠️ Tech Stack

- **Backend:** Symfony 8 (PHP 8.4)
- **Frontend:** Tailwind CSS + Shadcn UI Components
- **Database:** PostgreSQL 15
- **Web Server:** FrankenPHP + Caddy
- **Email Service:** Brevo (via Symfony Mailer)
- **Containerization:** Docker & Docker Compose
- **Testing:** PHPUnit + Symfony Browser Kit
- **Real-time:** Turbo Frames for dynamic updates

## 🚀 Quick Start

1. **Clone the repository**
   ```bash
   git clone git@github.com:flouzzy/theos.git
   cd theos
   ```

2. **Start Docker containers**
   ```bash
   docker compose up -d
   ```

3. **Install dependencies & setup database**
   ```bash
   docker compose exec php composer install
   docker compose exec php bin/console doctrine:migrations:migrate
   docker compose exec php bin/console doctrine:fixtures:load
   ```

4. **Access the application**
   - **HTTPS:** `https://localhost:8096`
   - **HTTP:** `http://localhost:8095`

For detailed installation instructions, see [docs/installation.md](docs/installation.md).

## 📖 Documentation

- [Installation Guide](docs/installation.md) - Complete setup instructions
- [Development Guide](docs/development.md) - Local development workflow
- [Architecture Overview](docs/architecture.md) - Application structure and design
- [Features Documentation](docs/features.md) - Detailed feature descriptions
- [Database Schema](docs/database.md) - Database structure and migrations
- [Testing Guide](docs/testing.md) - Running and writing tests
- [Deployment Guide](docs/deployment.md) - Production deployment
- [Contributing Guidelines](CONTRIBUTING.md) - How to contribute
- [Troubleshooting](docs/troubleshooting.md) - Common issues and solutions
- [API Documentation](docs/api.md) - API endpoints (if applicable)

## 🧪 Testing

Run the full test suite:
```bash
docker compose exec php bin/phpunit
```

Run specific test suites:
```bash
# Smoke tests
docker compose exec php bin/phpunit tests/SmokeTest.php

# Functional tests
docker compose exec php bin/phpunit tests/RegistrationFunctionalTest.php
```

## 📝 License

This project is licensed under the MIT License.

## 👥 Credits

Developed by **Le Rocher** team.

---

**Need help?** Check our [Troubleshooting Guide](docs/troubleshooting.md) or open an issue on GitHub.
