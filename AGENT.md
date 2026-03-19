# AGENT.md — Page Builder (eSponsor Challenge)

> Context file for AI coding agents (Claude Code, Cursor, Copilot, etc.).
> Read this before touching any file. It describes architecture decisions,
> conventions, and boundaries between roles.

---

## Project overview

A full-stack monolithic app where creators build a public profile page using
configurable blocks. Built with **Laravel 11 + Vue 3 + Inertia.js + Tailwind CSS + MySQL**.

One user → one site → many blocks.
Public URL: `/@{slug}` — rendered as a fast, cacheable Blade view.

---

## Stack

| Layer      | Technology                          |
|------------|-------------------------------------|
| Backend    | Laravel 11, PHP 8.2+                |
| Frontend   | Vue 3 (Composition API), Inertia.js |
| Styling    | Tailwind CSS v3                     |
| DB         | MySQL 8                             |
| Auth       | Laravel Breeze (Inertia stack)      |
| Storage    | Laravel local disk (public)         |
| Cache      | Laravel file cache (Redis-ready)    |

---

## Repository structure

```
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/                   # Breeze auth controllers
│   │   │   ├── BlockController.php     # CRUD + reorder + duplicate
│   │   │   ├── ProfileController.php   # User profile + site settings
│   │   │   ├── PublishController.php   # Draft → published snapshot
│   │   │   └── PublicPageController.php# Renders /@slug (Blade, cached)
│   │   ├── Requests/
│   │   │   ├── UpdateProfileRequest.php
│   │   │   └── BlockRequest.php        # Validates props against schema
│   │   └── Resources/
│   │       ├── BlockResource.php
│   │       └── SiteResource.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Site.php
│   │   └── Block.php
│   ├── Services/
│   │   └── BlockSchemaRegistry.php     # Single source of truth for schemas
│   └── Policies/
│       ├── SitePolicy.php
│       └── BlockPolicy.php
├── config/
│   └── blocks.php                      # Block type definitions (schemas)
├── database/
│   ├── migrations/
│   └── seeders/
│       └── DemoSeeder.php              # Full demo site with all block types
├── resources/
│   ├── js/
│   │   ├── Pages/
│   │   │   ├── Auth/                   # Login, Register
│   │   │   ├── Dashboard/
│   │   │   │   ├── Index.vue           # Main editor page (two-column layout)
│   │   │   │   └── Settings.vue        # Profile / site settings
│   │   │   └── Public/
│   │   │       └── Page.vue            # (unused — public = Blade)
│   │   ├── Components/
│   │   │   ├── Editor/
│   │   │   │   ├── BlockList.vue       # Drag-and-drop sortable list
│   │   │   │   ├── BlockCard.vue       # Collapsed block row + controls
│   │   │   │   ├── BlockEditor.vue     # Schema-driven prop editor
│   │   │   │   ├── BlockCatalog.vue    # Modal to pick block type
│   │   │   │   └── FieldRenderer.vue   # Renders input by field type
│   │   │   ├── Preview/
│   │   │   │   └── PreviewFrame.vue    # Live preview panel
│   │   │   └── Blocks/                 # Public-facing block renderers
│   │   │       ├── BlockLinks.vue
│   │   │       ├── BlockText.vue
│   │   │       ├── BlockImage.vue
│   │   │       ├── BlockVideo.vue
│   │   │       ├── BlockSocial.vue
│   │   │       └── BlockMusic.vue
│   │   ├── Layouts/
│   │   │   ├── AppLayout.vue           # Dashboard shell
│   │   │   └── GuestLayout.vue         # Auth pages shell
│   │   └── composables/
│   │       ├── useBlocks.js            # Block CRUD + reorder state
│   │       └── usePublish.js           # Draft/publish flow
│   └── views/
│       └── public/
│           ├── page.blade.php          # Public page (NO vue bundle)
│           └── blocks/                 # Blade partials per block type
│               ├── _links.blade.php
│               ├── _text.blade.php
│               ├── _image.blade.php
│               ├── _video.blade.php
│               ├── _social.blade.php
│               └── _music.blade.php
├── routes/
│   ├── web.php                         # Dashboard + public /@slug routes
│   └── api.php                         # Block CRUD API (auth:sanctum)
└── tests/
    ├── Feature/
    │   ├── BlockCrudTest.php
    │   ├── PublicPageTest.php
    │   └── PublishFlowTest.php
    └── Unit/
        └── BlockSchemaRegistryTest.php
```

---

## Database schema

### `users`
Standard Laravel users table (Breeze). No extra columns here.

### `sites`
```sql
id               BIGINT PK
user_id          BIGINT FK → users.id (unique — one site per user)
name             VARCHAR(100)       -- public display name
slug             VARCHAR(60) UNIQUE -- used in /@slug URL
bio              TEXT NULLABLE
avatar_url       VARCHAR(500) NULLABLE
published_at     TIMESTAMP NULLABLE -- null = never published
created_at, updated_at
```

### `blocks`
```sql
id               BIGINT PK
site_id          BIGINT FK → sites.id
type             VARCHAR(50)        -- 'links' | 'text' | 'image' | 'video' | 'social' | 'music'
props            JSON               -- validated against schema for this type
order            INT DEFAULT 0      -- sort order within the site
is_active        BOOLEAN DEFAULT true
is_published     BOOLEAN DEFAULT false  -- true once site is published
created_at, updated_at
```

> **Why JSON for `props`?** Each block type has different fields. A single JSON
> column keeps the schema flexible and allows adding new block types without new
> migrations. Props are always validated server-side against the block's schema.

---

## Block schema system — the core contract

### Where schemas live

`config/blocks.php` — PHP array consumed by `BlockSchemaRegistry`.

```php
// config/blocks.php
return [
    'links' => [
        'label' => 'Links / Buttons',
        'icon'  => 'link',
        'fields' => [
            ['key' => 'title',       'type' => 'text',     'label' => 'Título del bloque',  'required' => true],
            ['key' => 'items',       'type' => 'repeater', 'label' => 'Links',               'required' => true,
             'subfields' => [
                ['key' => 'label', 'type' => 'text', 'label' => 'Texto del botón'],
                ['key' => 'url',   'type' => 'url',  'label' => 'URL'],
             ]
            ],
            ['key' => 'color',       'type' => 'color',    'label' => 'Color de fondo',      'default' => '#000000'],
            ['key' => 'text_color',  'type' => 'color',    'label' => 'Color de texto',      'default' => '#ffffff'],
        ],
    ],
    'text' => [ ... ],
    'image' => [ ... ],
    'video' => [ ... ],
    'social' => [ ... ],
    'music' => [ ... ],
];
```

### Field types supported

| Type        | HTML rendered                 |
|-------------|-------------------------------|
| `text`      | `<input type="text">`         |
| `textarea`  | `<textarea>`                  |
| `url`       | `<input type="url">`          |
| `color`     | `<input type="color">`        |
| `select`    | `<select>` with `options` key |
| `image`     | File upload → storage URL     |
| `repeater`  | Dynamic list of subfields     |

### API endpoint that exposes schemas to the FE

```
GET /api/block-schemas
→ returns the full config/blocks.php array as JSON
```

The FE fetches this once on dashboard load and uses it to render `BlockEditor.vue`
and `BlockCatalog.vue` dynamically. **Never hardcode schema shape in the FE.**

---

## API routes (authenticated, `auth:sanctum`)

```
GET    /api/block-schemas          → all schemas
GET    /api/blocks                 → blocks for authenticated user's site
POST   /api/blocks                 → create block (type + props)
PUT    /api/blocks/{id}            → update props
DELETE /api/blocks/{id}            → delete
POST   /api/blocks/{id}/duplicate  → clone block
PATCH  /api/blocks/{id}/toggle     → flip is_active
POST   /api/blocks/reorder         → body: [{id, order}, ...]
POST   /api/site/publish           → snapshot draft → published
PATCH  /api/profile                → update name, slug, bio, avatar
```

---

## Public page strategy (performance + SEO)

The route `/@{slug}` is handled by `PublicPageController` and renders a **plain
Blade view** — no Inertia, no Vue bundle. This is intentional:

- Faster TTFB — no JS framework to boot.
- Fully crawlable HTML for SEO.
- Cacheable at the Laravel layer: `Cache::remember("public.site.$slug", 300, ...)`.
- Cache is **invalidated** when the user clicks "Publicar".

The Blade view includes per-block partials in `resources/views/public/blocks/`.
They receive `$block->props` as the only variable.

Meta tags included in every public page:
```html
<title>{site.name}</title>
<meta name="description" content="{site.bio}">
<meta property="og:title" content="{site.name}">
<meta property="og:image" content="{site.avatar_url}">
<meta property="og:url" content="https://app.test/@{site.slug}">
```

---

## Frontend conventions

### State management
No Pinia/Vuex. State lives in composables:
- `useBlocks()` — reactive block list, all CRUD + reorder actions
- `usePublish()` — publish state, dirty flag (unsaved changes indicator)

Inertia page props provide the initial data on load. Mutations go through the
composable which calls the API and patches local state optimistically.

### Component responsibilities

| Component        | What it does                                          |
|------------------|-------------------------------------------------------|
| `BlockList.vue`  | Renders sorted blocks, emits reorder events           |
| `BlockCard.vue`  | Shows collapsed block, controls (edit/delete/toggle)  |
| `BlockEditor.vue`| Expanded form, reads schema, delegates to FieldRenderer|
| `FieldRenderer.vue`| Renders one field by `type` (text/url/color/etc.)  |
| `PreviewFrame.vue`| Mirror of the public page, reactive to block state  |

### Preview implementation
`PreviewFrame.vue` is a live Vue render (not an iframe) that imports the same
`components/Blocks/*.vue` components used in the public page. It reads from the
same `useBlocks()` composable so changes appear instantly without any API call.

### Drag and drop
Use `vuedraggable` (wraps SortableJS). On drag end, call `useBlocks().reorder(newOrder)`.
Include arrow-button fallback (`moveUp` / `moveDown`) for accessibility.

---

## Naming conventions

- **PHP classes**: PascalCase (`BlockSchemaRegistry`)
- **PHP methods**: camelCase (`getSchema`, `validateProps`)
- **Vue components**: PascalCase filenames (`BlockEditor.vue`)
- **Composables**: camelCase prefixed with `use` (`useBlocks.js`)
- **DB columns**: snake_case
- **API JSON keys**: snake_case
- **Tailwind**: utility-first, no custom CSS unless unavoidable
- **Git commits**: `type: short description` — types: `feat`, `fix`, `refactor`, `test`, `docs`, `chore`

---

## Role boundaries

### Backend (BE) owns:
- All Laravel files (`app/`, `config/`, `database/`, `routes/`, `tests/`)
- Schema definitions in `config/blocks.php`
- Validation logic in `BlockRequest.php`
- Cache invalidation strategy
- Blade public view and partials

### Frontend (FE) owns:
- All Vue/JS files (`resources/js/`)
- Tailwind configuration and design tokens
- `PreviewFrame.vue` and all block renderer components
- Composables

### Shared decisions (both devs must align):
1. **Schema field shape** — agree on the exact JSON structure before FE starts `FieldRenderer.vue`
2. **API response envelope** — use Laravel Resources consistently (no raw `->toArray()`)
3. **Props JSON shape per block** — document an example for each block type before implementing renderers

---

## Environment setup

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
npm install
npm run dev
php artisan storage:link
php artisan serve
```

Required `.env` values:
```
DB_DATABASE=pagebuilder
DB_USERNAME=root
DB_PASSWORD=

APP_URL=http://localhost:8000
FILESYSTEM_DISK=public
```

---

## Running tests

```bash
php artisan test                        # all tests
php artisan test --filter BlockCrud     # specific suite
```

---

## Out of scope for MVP (document but don't build)

- Custom domain mapping per site
- Analytics (page views per block)
- Multiple sites per user
- Billing / plan limits
- Block-level A/B testing
- Redis cache (replace file cache driver in `.env` for prod)
- Queue for image processing (resize/optimize avatars)
- Rate limiting on public page (Cloudflare recommended for prod)
