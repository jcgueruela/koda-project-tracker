# Koda Project Tracker

A Laravel + Inertia (Vue 3 + TypeScript) app for tracking client project engagements — status, priority, timelines, and search/filter/sort over an API-backed table.

## Stack

- Laravel (API + Inertia backend)
- Vue 3 + TypeScript, Inertia.js
- Tailwind CSS + shadcn-vue components
- Pest for testing
- Laravel Sail (Docker) for local development

## Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) running
- Git

You do **not** need PHP, Composer, or Node installed on your host machine — Sail runs everything inside containers. The one exception is the very first `composer install`, before Sail itself exists yet (see below).

## First-time setup

1. **Clone the repo**

   ```bash
   git clone git@github.com:jcgueruela/koda-project-tracker.git
   cd koda-project-tracker
   ```

2. **Install PHP dependencies**

   Sail is a Composer package, so on a fresh clone there's a chicken-and-egg problem: you need `vendor/bin/sail` to run Sail, but Sail itself comes from `composer install`. Run Composer once via a throwaway container:

   ```bash
   docker run --rm \
       -u "$(id -u):$(id -g)" \
       -v "$(pwd):/var/www/html" \
       -w /var/www/html \
       laravelsail/php83-composer:latest \
       composer install --ignore-platform-reqs
   ```

   (Swap `php83` for the PHP version this project targets if different.)

3. **Copy the environment file**

   ```bash
   cp .env.example .env
   ```

   Defaults in `.env.example` are already wired for Sail's service names (`DB_HOST=pgsql` or `mysql`, `REDIS_HOST=redis`, etc.) — you shouldn't need to touch these for local dev.

4. **Start the containers**

   ```bash
   ./vendor/bin/sail up -d
   ```

5. **Generate the app key**

   ```bash
   sail artisan key:generate
   ```

6. **Run migrations**

   ```bash
   sail artisan migrate
   ```

   Add `--seed` if a seeder is set up and you want sample data:

   ```bash
   sail artisan migrate --seed
   sail artisan migrate --seed --class=ProjectSeeder
   ```

7. **Install JS dependencies and build frontend assets**

   ```bash
   sail npm install
   sail npm run dev
   ```

   `npm run dev` starts the Vite dev server with hot module reload and needs to stay running in its own terminal tab alongside `sail up`. For a production-style build instead, use `sail npm run build`.

8. **Open the app**

   By default, Sail exposes the app at:

   ```
   http://localhost:8000
   ```

   (Check `APP_PORT` in `.env` if you've customized it — it'll be `http://localhost:${APP_PORT}` instead.)

## Running tests

The backend test suite uses Pest and runs inside the Sail container so it hits the same DB/service config as the app:

```bash
sail artisan test
```

or, equivalently:

```bash
sail pest
```

Run a single file or filter by test name:

```bash
sail artisan test --filter="creates a project"
sail artisan test tests/Feature/ProjectControllerTest.php
```

Tests use `RefreshDatabase`, so they run against your configured test database and don't touch your local dev data — no separate setup needed beyond the steps above.

## Notes

- The API lives under `/api/projects` (`ProjectController`) and is used by the Inertia frontend via `useHttp` rather than server-rendered pagination — the frontend fetches JSON and renders it client-side.
- Routes are currently guarded by the `auth` middleware — you'll need a logged-in user (via the app's normal registration/login flow, or one created through `sail artisan tinker` / a seeder) to hit any project endpoint.
- shadcn-vue components live under `resources/js/components/ui` (or wherever your `components.json` points) — if you add new ones, use the `shadcn-vue` CLI from inside the container (`sail npm run` a script, or `sail node ...`) rather than your host machine, so versions stay consistent with the container's Node install.
