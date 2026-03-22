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
- **Public page** — Blade en `/@{slug}` sin bundle del editor; datos desde BD. Claves `public_site:{slug}` se invalidan al publicar y al guardar perfil (listas para caché futura si se añade `Cache::remember`)
- **SEO** — pública `/@slug`: título `Nombre de la persona - Nombre de la plataforma` (`APP_NAME`), descripción = bio (o texto por defecto `config/seo.php` / `SEO_DEFAULT_DESCRIPTION`); Inertia: meta description por defecto en `app.blade.php`

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
> and skip MySQL entirely.

---

## Quick start

The fastest path from a clean clone to a running app:

```bash
# 1. Clone
git clone https://github.com/SoulOppen/PT_eSponsor.git
cd PT_eSponsor

# 2. Install dependencies
composer install
npm install

# 3. Environment and app key
cp .env.example .env
php artisan key:generate

# 4. Database
php artisan migrate

# 5. (Optional) Seed demo data
php artisan db:seed --class=DemoSeeder

# 6. Storage symlink for avatar/public files
php artisan storage:link

# 7. Start the servers in two terminals
php artisan serve      # → http://localhost:8000
npm run dev            # → Vite HMR on port 5173
```

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
- Opcional: `SEO_DEFAULT_DESCRIPTION` en `.env` (ver `config/seo.php`) para la meta description por defecto.

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
- Tests must be green **before** every commit.

```bash
php artisan test --stop-on-failure
npm run test:run
git add -A
git commit -m "feat: your message"
git push origin HEAD
```

### Commit message format

```
<type>: <short description>

Types: feat | fix | refactor | test | docs | chore | style | perf
```

---

## Project structure

Árbol orientativo (omitidos `vendor/`, `node_modules/`, `public/build/`, etc.):

```
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── DashboardController.php       # /dashboard, /dashboard/settings (Inertia)
│   │   │   ├── PublicPageController.php      # /@slug → Blade (publicado)
│   │   │   ├── DraftPageController.php       # /draft/@slug → vista previa borrador (auth)
│   │   │   ├── ProfileController.php         # Perfil Breeze (Inertia, rutas web)
│   │   │   ├── Api/
│   │   │   │   ├── BlockController.php       # CRUD, reorder, duplicate, toggle
│   │   │   │   ├── BlockSchemaController.php # GET /api/block-schemas
│   │   │   │   ├── ProfileController.php     # PATCH /api/profile (JSON + avatar multipart)
│   │   │   │   └── SitePublishController.php # POST /api/site/publish
│   │   │   └── Auth/                         # Login, registro, verificación… (Breeze)
│   │   ├── Middleware/
│   │   │   ├── AcceptJson.php                # Accept: application/json en API
│   │   │   └── HandleInertiaRequests.php
│   │   └── Requests/
│   │       ├── Auth/LoginRequest.php
│   │       └── ProfileUpdateRequest.php      # Formulario perfil Breeze
│   ├── Models/
│   │   ├── User.php
│   │   ├── Site.php                          # belongsTo user, hasMany blocks
│   │   └── Block.php                         # props → array (JSON)
│   ├── Policies/
│   │   └── BlockPolicy.php
│   ├── Providers/
│   └── Services/
│       └── BlockSchemaRegistry.php           # Lee config/blocks.php
├── bootstrap/
│   └── app.php                               # Rutas web + api + middleware
├── config/
│   ├── blocks.php                            # Esquemas por tipo de bloque
│   └── seo.php                               # Meta description por defecto
├── database/
│   ├── factories/                            # User, Site, Block
│   ├── migrations/
│   └── seeders/                              # DatabaseSeeder, DemoSeeder
├── resources/
│   ├── js/
│   │   ├── Pages/                            # PublicHome, Auth/*, Dashboard/*, Profile/*
│   │   ├── Components/
│   │   │   ├── Editor/                       # BlockList, BlockCard, BlockEditor, …
│   │   │   ├── Preview/PreviewFrame.vue
│   │   │   └── Blocks/                       # Vista previa por tipo (BlockLinks, …)
│   │   ├── composables/                      # useBlocks.js, usePublish.js
│   │   ├── Layouts/
│   │   └── tests/                            # Vitest (componentes + composables)
│   ├── css/
│   └── views/
│       ├── app.blade.php                     # Shell Inertia + meta por defecto
│       └── public/
│           ├── site.blade.php                # HTML público /@slug (SEO + bloques)
│           └── blocks/                       # _<tipo>.blade.php (reciben $block)
├── routes/
│   ├── web.php                               # /, /@slug, /dashboard, Breeze perfil
│   ├── api.php                               # prefijo /api; middleware web + auth + verified
│   └── auth.php
└── tests/
    ├── Feature/                              # Auth/, Phase1/ … Phase6/
    └── Unit/
```

**Notas rápidas**

- La API bajo `/api/*` usa **sesión web** (cookies + CSRF), igual que Inertia; no hace falta token Sanctum para el editor.
- La validación de `props` frente al esquema vive en `Api\BlockController` (no hay `FormRequest` dedicado ni `JsonResource` para bloques).

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
2. Creating a Blade partial at `resources/views/public/blocks/_<type>.blade.php` (la vista incluye el partial con `['block' => $block]`; usa `$block->props`, etc.)
3. Creating a Vue renderer at `resources/js/Components/Blocks/Block<Type>.vue` (vista previa / catálogo en el editor)

No migrations needed — props are stored as JSON in the `blocks.props` column.

---

## Public page strategy

`GET /@{slug}` is handled by a plain `PublicPageController` that renders a
Blade view — no Inertia, no Vue bundle on the wire.

**Why Blade instead of Inertia?**

- Zero JS framework boot time → faster First Contentful Paint
- Fully server-rendered HTML → crawlable by search engines without a JS runtime
- Hoy cada `GET /@slug` carga el `Site` y los bloques publicados desde la **base de datos** (sin `Cache::remember` en el controlador). Existe la convención de clave `public_site:{slug}` y se hace `Cache::forget` al **publicar** y al **guardar perfil**, para cuando se quiera envolver la respuesta en caché.

Each block type has a dedicated Blade partial in `resources/views/public/blocks/`.
Each partial receives the full **`$block`** model (p. ej. `$block->props`, `$block->type`).

---

## Page goals

- `/` (PublicHome): public landing page with login/register access.
- `/register`: user signup and base site data (includes unique `slug`).
- `/login`: access for registered users.
- `/dashboard`: main editor to manage blocks.
- `/dashboard/settings`: site configuration (name, slug, bio, avatar).
- `/@{slug}`: creator's published public page.
- `/draft/@{slug}`: full-page draft preview (active blocks, including unpublished) — **auth + verified** required; cualquier usuario autenticado puede abrirla, pero **solo el dueño** ve la barra para **publicar** y **reordenar bloques** por arrastre (mismas APIs que el editor). **Publicar** se habilita si hay bloques activos sin publicar o si el estado actual (orden, props, `is_published` de cada bloque activo) **no coincide** con `sites.published_blocks_snapshot` guardado al publicar; **«Volver a lo publicado»** actualiza ese snapshot al eliminar borradores.

---

## Contributing

1. Read `AGENT.md` fully before writing any code
2. Branch off `develop` following the naming convention (`be/phase-X`, `fe/phase-X`)
3. Run backend + frontend tests before each commit
4. Open a PR into `develop` when a phase is complete; both BE and FE phases must be
   merged before moving the integration to `main`

---

## To Do

- [ ] Agregar tests E2E para drag and drop en bloques.
- [ ] Mejorar accesibilidad de iconos en acciones del editor (tooltips + focus states).
- [ ] Añadir validación visual en vivo para `slug` disponible en registro.
- [ ] Actualizar `AGENT.md` con la invalidación de `public_site:{slug}` al publicar/perfil (resumen en este README).
- [ ] Crear guía corta de despliegue en producción.

## License

All code produced during this challenge is the property of the developer, not eSponsor.
See the challenge brief for full terms.
