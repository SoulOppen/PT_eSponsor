# Page Builder — eSponsor Challenge

> Full-stack monolithic page builder where creators compose a public profile
> from configurable blocks. Built with Laravel 11 · Vue 3 · Inertia.js · Tailwind CSS · MySQL.

---

## Table of contents

- [Demo](#demo)
- [Features](#features)
- [Tech stack](#tech-stack)
- [Tooling requirements](#tooling-requirements)
- [Prerequisites](#prerequisites)
- [Quick start](#quick-start)
- [Manual setup](#manual-setup)
- [Environment variables (minimum)](#environment-variables-minimum)
- [Running tests](#running-tests)
- [Git workflow](#git-workflow)
- [Project structure](#project-structure)
- [Block system](#block-system)
- [Public page strategy](#public-page-strategy)
- [Page goals](#page-goals)
- [Contributing](#contributing)
- [To Do](#to-do)

---

## Demo

| Credential    | Value                  |
|---------------|------------------------|
| Email         | `demo@example.com`     |
| Password      | `password`             |
| Public page   | `http://localhost:8000/@demo` |

Seed the demo account with:
```bash
php artisan db:seed --class=DemoSeeder
```

---

## Features

- **Auth** — register, login, password reset (Laravel Breeze)
- **One site per user** — slug auto-generated on registration, editable later
- **Block editor** — add, reorder (drag & drop), duplicate, delete, and toggle blocks
- **Schema-driven** — every block type declares its own field schema; inputs are rendered dynamically
- **Live preview** — side-by-side editor and preview on desktop, toggle on mobile
- **Draft / publish** — edit in draft, publish when ready; public URL always shows the published version
- **Public page** — fast Blade-rendered view at `/@{slug}`, cached at the Laravel layer, no JS bundle
- **SEO** — `<title>`, `<meta description>`, Open Graph tags on every public page

### Block catalogue (MVP)

| Block     | Description                                  |
|-----------|----------------------------------------------|
| `links`   | Configurable button list with custom colours |
| `text`    | Rich paragraph / heading block               |
| `image`   | Image with alt text and caption              |
| `video`   | YouTube, TikTok, Twitch, Instagram embed     |
| `social`  | Social network icon links                    |
| `music`   | Spotify, Bandcamp, SoundCloud embed          |

---

## Tech stack

| Layer       | Technology                              |
|-------------|-----------------------------------------|
| Backend     | Laravel 11, PHP 8.2+                    |
| Frontend    | Vue 3 (Composition API), Inertia.js     |
| Styling     | Tailwind CSS v3                         |
| Database    | MySQL 8                                 |
| Auth        | Laravel Breeze (Inertia stack)          |
| Storage     | Laravel local disk (`public`)           |
| Cache       | File cache (swap driver for Redis)      |
| BE tests    | Pest PHP                                |
| FE tests    | Vitest + Vue Test Utils                 |

---

## Tooling requirements

Minimum tools to run the project locally:

- PHP 8.2+
- Composer 2.x
- Node.js 18+
- npm 9+
- Database: MySQL 8 or SQLite

---

## Prerequisites

| Tool       | Minimum version | Check                    |
|------------|-----------------|--------------------------|
| PHP        | 8.2             | `php --version`          |
| Composer   | 2.x             | `composer --version`     |
| Node.js    | 18.x            | `node --version`         |
| npm        | 9.x             | `npm --version`          |
| MySQL      | 8.0             | `mysql --version`        |

> **Tip:** SQLite works fine for local dev. Set `DB_CONNECTION=sqlite` in `.env`
> and skip MySQL entirely — the quick start script handles it automatically.

---

## Quick start

The fastest path from a clean clone to a running app:

```bash
# 1. Clone
git clone https://github.com/your-org/page-builder.git
cd page-builder

# 2. Make scripts executable
chmod +x bin/quickstart.sh bin/commit-step.sh

# 3. Run setup (includes migrate + asset build + full test suite)
./bin/quickstart.sh

# 4. (Optional) Seed demo data
./bin/quickstart.sh --seed-demo

# 5. Start the servers in two terminals
php artisan serve      # → http://localhost:8000
npm run dev            # → Vite HMR on port 5173
```

### Script flags

| Flag           | Effect                                             |
|----------------|----------------------------------------------------|
| `--seed-demo`  | Runs `DemoSeeder` and prints demo credentials      |
| `--skip-npm`   | Skips `npm install` + build (useful in CI)         |

---

## Manual setup

If you prefer step-by-step control:

```bash
# 1. Copy environment file
cp .env.example .env

# 2. Install PHP dependencies
composer install

# 3. Generate application key
php artisan key:generate

# 4. Configure your database in .env, then migrate
php artisan migrate

# 5. (Optional) Seed demo data
php artisan db:seed --class=DemoSeeder

# 6. Create storage symlink
php artisan storage:link

# 7. Install JS dependencies and build assets
npm install
npm run dev          # development (HMR)
# npm run build      # production build

# 8. Start the server
php artisan serve
```

---

## Environment variables (minimum)

Copy `.env.example` to `.env` and update only these keys:

```dotenv
APP_NAME="PT eSponsor"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pt_esponsor
DB_USERNAME=root
DB_PASSWORD=

# Quick local alternative
# DB_CONNECTION=sqlite
# DB_DATABASE=/absolute/path/to/database.sqlite
```

Notes:
- `FILESYSTEM_DISK=public` already matches the avatar upload flow.
- If you use SQLite, you do not need MySQL for local development.

---

## Running tests

### Backend (Pest)

```bash
php artisan test                          # full suite
php artisan test --filter Phase1          # single phase
php artisan test --filter BlockCrud       # single file
php artisan test --stop-on-failure        # bail on first red
php artisan test --coverage --min=80      # coverage gate (requires Xdebug/PCOV)
```

Tests run against an in-memory SQLite database (configured in `phpunit.xml`) — no
MySQL connection needed for the test suite.

### Frontend (Vitest)

```bash
npm run test             # watch mode (development)
npm run test:run         # single pass (CI)
npm run test:coverage    # coverage report
```

### Both at once (CI gate)

```bash
php artisan test --stop-on-failure && npm run test:run
```

---

## Git workflow

This project uses a strict commit discipline enforced by `bin/commit-step.sh`.
Read `AGENT.md` for the full protocol. Summary:

### Branch model

```
main        ← protected; receives merges from develop only
└── develop ← integration branch
    ├── be/phase-1   ← backend dev, phase 1
    ├── fe/phase-1   ← frontend dev, phase 1
    ├── be/phase-2
    └── fe/phase-2   ...
```

### Commit rules

- One commit per `####` section in `AGENT.md` — no bundling, no splitting
- Commit message must match the section header **exactly**
- Tests must be green **before** every commit — use the helper:

```bash
./bin/commit-step.sh "feat: block CRUD endpoints"
```

The helper will:
1. Run `php artisan test --stop-on-failure`
2. Run `npm run test:run`
3. `git add -A`
4. `git commit -m "<your message>"`
5. `git push origin HEAD`

It aborts with a non-zero exit code if any step fails, so CI will also catch it.

### Commit message format

```
<type>: <short description>

Types: feat | fix | refactor | test | docs | chore | style | perf
```

---

## Project structure

```
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── BlockController.php       # Block CRUD + reorder + duplicate
│   │   │   ├── ProfileController.php     # Profile + site settings
│   │   │   ├── PublishController.php     # Draft → published
│   │   │   └── PublicPageController.php  # /@slug Blade render (cached)
│   │   ├── Requests/
│   │   │   ├── BlockRequest.php          # Validates props against schema
│   │   │   └── UpdateProfileRequest.php
│   │   └── Resources/
│   │       ├── BlockResource.php
│   │       └── SiteResource.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Site.php                      # hasMany blocks, belongsTo user
│   │   └── Block.php                     # props cast to array (JSON column)
│   ├── Services/
│   │   └── BlockSchemaRegistry.php       # Single source of truth for schemas
│   └── Policies/
│       ├── SitePolicy.php
│       └── BlockPolicy.php
├── bin/
│   ├── quickstart.sh                     # Full environment setup
│   └── commit-step.sh                    # Test → stage → commit → push
├── config/
│   └── blocks.php                        # Block type schemas (PHP arrays)
├── database/
│   ├── migrations/
│   └── seeders/
│       └── DemoSeeder.php
├── resources/
│   ├── js/
│   │   ├── Pages/Dashboard/
│   │   │   ├── Index.vue                 # Two-column editor layout
│   │   │   └── Settings.vue
│   │   ├── Components/
│   │   │   ├── Editor/                   # BlockList, BlockCard, BlockEditor,
│   │   │   │                             # BlockCatalog, FieldRenderer
│   │   │   ├── Preview/PreviewFrame.vue  # Live preview (same composable as editor)
│   │   │   └── Blocks/                   # Public block renderers (BlockLinks, etc.)
│   │   ├── composables/
│   │   │   ├── useBlocks.js              # All block state + API calls
│   │   │   └── usePublish.js             # Dirty flag + publish action
│   │   └── tests/                        # Vitest unit + component tests
│   └── views/public/
│       ├── page.blade.php                # Public page shell (no Vue bundle)
│       └── blocks/                       # Blade partials per block type
├── routes/
│   ├── web.php                           # Dashboard + /@slug
│   └── api.php                           # Block CRUD (auth:sanctum)
└── tests/
    ├── Feature/Phase1/ … Phase6/
    └── Unit/
```

---

## Block system

Every block type is defined in `config/blocks.php` as a PHP array:

```php
'text' => [
    'label'  => 'Text',
    'icon'   => 'text',
    'fields' => [
        ['key' => 'content', 'type' => 'textarea', 'label' => 'Content', 'required' => true],
        ['key' => 'align',   'type' => 'select',   'label' => 'Alignment',
         'options' => ['left', 'center', 'right'], 'default' => 'left'],
    ],
],
```

`BlockSchemaRegistry` consumes that array and exposes it via `GET /api/block-schemas`.
The Vue editor fetches schemas once on load and renders `FieldRenderer.vue` inputs
dynamically — **no block-specific code lives in the frontend**.

Adding a new block type requires:
1. Adding an entry to `config/blocks.php`
2. Creating a Blade partial at `resources/views/public/blocks/_<type>.blade.php`
3. Creating a Vue renderer at `resources/js/Components/Blocks/Block<Type>.vue`

No migrations needed — props are stored as JSON in the `blocks.props` column.

---

## Public page strategy

`GET /@{slug}` is handled by a plain `PublicPageController` that renders a
Blade view — no Inertia, no Vue bundle on the wire.

**Why Blade instead of Inertia?**

- Zero JS framework boot time → faster First Contentful Paint
- Fully server-rendered HTML → crawlable by search engines without a JS runtime
- Cacheable at the Laravel layer: `Cache::remember("public.site.$slug", 300, fn()...)`
- Cache is invalidated automatically when the user clicks "Publish"

Each block type has a dedicated Blade partial in `resources/views/public/blocks/`.
The only variable passed to each partial is `$block->props` (the JSON array for
that block).

---

## Page goals

- `/` (PublicHome): public landing page with login/register access.
- `/register`: user signup and base site data (includes unique `slug`).
- `/login`: access for registered users.
- `/dashboard`: main editor to manage blocks.
- `/dashboard/settings`: site configuration (name, slug, bio, avatar).
- `/@{slug}`: creator's published public page.

---

## Contributing

1. Read `AGENT.md` fully before writing any code
2. Branch off `develop` following the naming convention (`be/phase-X`, `fe/phase-X`)
3. Use `./bin/commit-step.sh "<message>"` for every commit — direct `git commit` is
   strongly discouraged because it bypasses the test gate
4. Open a PR into `develop` when a phase is complete; both BE and FE phases must be
   merged before moving the integration to `main`

---

## To Do

- [ ] Agregar tests E2E para drag and drop en bloques.
- [ ] Mejorar accesibilidad de iconos en acciones del editor (tooltips + focus states).
- [ ] Añadir validación visual en vivo para `slug` disponible en registro.
- [ ] Documentar estrategia de cache invalidation al publicar.
- [ ] Crear guía corta de despliegue en producción.

## License

All code produced during this challenge is the property of the developer, not eSponsor.
See the challenge brief for full terms.
