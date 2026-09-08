# Learn to Drive

Learn to Drive is a web application that helps users prepare for their driving test. It's built with Laravel as the backend/API, with a separate frontend for the user-facing experience.

## Current Status

🚧 **In Development**

- ✅ Admin panel (web) — manage questions, content, and users
- 🔜 Client-side app — for learners to study and take practice tests

## Features

### Admin (available now)
- Manage driving test questions and answer options
- Organize content into categories/topics
- Manage users

### Planned (client-side)
- Browse and study driving test material
- Take practice quizzes/mock tests
- Track progress and scores

## Tech Stack

- **Backend:** Laravel (PHP) — REST API
- **Frontend:** Separate JS application (built with Vite)
- **Database:** MySQL

## Getting Started

### Prerequisites

- PHP >= 8.2
- Composer
- Node.js & npm
- MySQL

### Installation

1. Clone the repository
```bash
   git clone https://github.com/Sumitkc74/learn_to_drive.git
   cd learn_to_drive
```

2. Install PHP dependencies
```bash
   composer install
```

3. Install JS dependencies
```bash
   npm install
```

4. Set up environment file
```bash
   cp .env.example .env
   php artisan key:generate
```

5. Configure your database in `.env`, then run migrations
```bash
   php artisan migrate
```

6. Build frontend assets
```bash
   npm run dev
```

7. Start the Laravel server
```bash
   php artisan serve
```

The app should now be running at `http://localhost:8000`.

## Roadmap

- [ ] Build client-facing frontend
- [ ] Practice test / quiz mode
- [ ] User progress tracking
- [ ] Mobile-friendly UI

> Payment upgrades are temporarily disabled until server-side payment-provider
> verification is implemented. Never grant premium access from a client-provided
> transaction token or amount.

## License

This is a personal project. All rights reserved — not licensed for reuse or distribution.
