# AGENT.md — Page Builder (eSponsor Challenge)

> Context file for AI coding agents (Claude Code, Cursor, Copilot, etc.).
> Read this before touching any file. It describes architecture decisions,
> conventions, boundaries between roles, and the tests that must pass at
> every commit before moving on.

---

## ⚠️ Mandatory git commit protocol

**This is the most important rule in this file. The agent must follow it without exception.**

Every `####` section in "Phase-by-phase test specifications" is one atomic unit of work.
The agent MUST complete the following checklist **in order** before moving to the next section:

```
[ ] 1. Write the code described in the section header
[ ] 2. Write the test file(s) listed in the section
[ ] 3. Run the tests — they must be GREEN (zero failures)
[ ] 4. Stage all changed files
[ ] 5. Commit with the EXACT message shown in the section header
[ ] 6. Push to the remote branch
[ ] 7. Only then start the next section
```

**The agent must NEVER:**
- Move to the next section while tests are red
- Bundle two `####` sections into a single commit
- Skip the push step (remote must always reflect local state)
- Use a commit message different from the one in the section header

### Commit command template

After each section, run:

```bash
# 1. Verify tests are green first
php artisan test --stop-on-failure     # for BE sections
npm run test:run                        # for FE sections (or both)

# 2. Stage everything
git add -A

# 3. Commit with the EXACT message from the section header
git commit -m "<message from #### header>"

# 4. Push
git push origin HEAD
```

Or use the helper script (see Quick start section):

```bash
./bin/commit-step.sh "feat: laravel project init with breeze and inertia"
```

The helper automatically runs tests, stages, commits, and pushes — and aborts if any test fails.

### Branch strategy

```
main          ← protected, receives PRs only
└── develop   ← integration branch, all commits land here first
    ├── be/phase-1   ← BE developer branch per phase
    └── fe/phase-1   ← FE developer branch per phase
```

Each developer works on their phase branch and merges to `develop` when the phase is complete.
`main` is only updated when a full phase passes both BE and FE tests.

Initial setup:
```bash
git checkout -b develop
git push -u origin develop

# BE dev
git checkout -b be/phase-1
# FE dev
git checkout -b fe/phase-1
```

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
| BE tests   | Pest PHP (ships with Laravel 11)    |
| FE tests   | Vitest + Vue Test Utils             |

---

## Test tooling setup

### Backend — Pest PHP
Laravel 11 ships with Pest. Use `RefreshDatabase` on every Feature test.
Use in-memory SQLite for speed — add to `phpunit.xml`:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
<env name="CACHE_DRIVER" value="array"/>
<env name="QUEUE_CONNECTION" value="sync"/>
```

```bash
php artisan test                        # full suite
php artisan test --filter Phase1        # single phase
php artisan test --coverage --min=80    # coverage gate
```

### Frontend — Vitest + Vue Test Utils

```bash
npm install -D vitest @vue/test-utils @vitejs/plugin-vue jsdom
```

Add to `vite.config.js`:
```js
test: {
  environment: 'jsdom',
  globals: true,
  setupFiles: ['./resources/js/tests/setup.js'],
}
```

`resources/js/tests/setup.js`:
```js
import { config } from '@vue/test-utils'
config.global.stubs = { Link: { template: '<a><slot /></a>' } }
```

```bash
npm run test:run       # CI single pass
npm run test:coverage  # coverage report
```

> **Coverage targets**: BE ≥ 80% on `app/Http/Controllers` + `app/Services`.
> FE ≥ 70% on `Components/` + `composables/`.

---

## Repository structure

```
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   ├── BlockController.php
│   │   │   ├── ProfileController.php
│   │   │   ├── PublishController.php
│   │   │   └── PublicPageController.php
│   │   ├── Requests/
│   │   │   ├── UpdateProfileRequest.php
│   │   │   └── BlockRequest.php
│   │   └── Resources/
│   │       ├── BlockResource.php
│   │       └── SiteResource.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Site.php
│   │   └── Block.php
│   ├── Services/
│   │   └── BlockSchemaRegistry.php
│   └── Policies/
│       ├── SitePolicy.php
│       └── BlockPolicy.php
├── config/
│   └── blocks.php
├── database/
│   ├── migrations/
│   └── seeders/
│       └── DemoSeeder.php
├── resources/
│   ├── js/
│   │   ├── Pages/
│   │   │   ├── Auth/
│   │   │   └── Dashboard/
│   │   │       ├── Index.vue
│   │   │       └── Settings.vue
│   │   ├── Components/
│   │   │   ├── Editor/
│   │   │   │   ├── BlockList.vue
│   │   │   │   ├── BlockCard.vue
│   │   │   │   ├── BlockEditor.vue
│   │   │   │   ├── BlockCatalog.vue
│   │   │   │   └── FieldRenderer.vue
│   │   │   ├── Preview/
│   │   │   │   └── PreviewFrame.vue
│   │   │   └── Blocks/
│   │   │       ├── BlockLinks.vue
│   │   │       ├── BlockText.vue
│   │   │       ├── BlockImage.vue
│   │   │       ├── BlockVideo.vue
│   │   │       ├── BlockSocial.vue
│   │   │       └── BlockMusic.vue
│   │   ├── Layouts/
│   │   │   ├── AppLayout.vue
│   │   │   └── GuestLayout.vue
│   │   ├── composables/
│   │   │   ├── useBlocks.js
│   │   │   └── usePublish.js
│   │   └── tests/
│   │       ├── setup.js
│   │       ├── unit/
│   │       │   ├── useBlocks.test.js
│   │       │   ├── usePublish.test.js
│   │       │   └── FieldRenderer.test.js
│   │       └── components/
│   │           ├── BlockCard.test.js
│   │           ├── BlockEditor.test.js
│   │           ├── BlockCatalog.test.js
│   │           ├── PreviewFrame.test.js
│   │           └── blocks/
│   │               ├── BlockLinks.test.js
│   │               ├── BlockText.test.js
│   │               ├── BlockImage.test.js
│   │               ├── BlockVideo.test.js
│   │               ├── BlockSocial.test.js
│   │               └── BlockMusic.test.js
│   └── views/
│       └── public/
│           ├── page.blade.php
│           └── blocks/
│               ├── _links.blade.php
│               ├── _text.blade.php
│               ├── _image.blade.php
│               ├── _video.blade.php
│               ├── _social.blade.php
│               └── _music.blade.php
├── routes/
│   ├── web.php
│   └── api.php
└── tests/
    ├── Feature/
    │   ├── Phase1/
    │   │   ├── MigrationsTest.php
    │   │   └── ModelsRelationsTest.php
    │   ├── Phase2/
    │   │   ├── AuthTest.php
    │   │   └── ProfileTest.php
    │   ├── Phase3/
    │   │   ├── BlockSchemaRegistryTest.php
    │   │   ├── BlockCrudTest.php
    │   │   ├── BlockReorderTest.php
    │   │   └── BlockPolicyTest.php
    │   ├── Phase4/
    │   │   ├── PublishFlowTest.php
    │   │   └── PublicPageTest.php
    │   ├── Phase5/
    │   │   ├── SeoMetaTest.php
    │   │   └── ExtraBlockSchemasTest.php
    │   └── Phase6/
    │       └── DemoSeederTest.php
    └── Unit/
        ├── BlockSchemaRegistryUnitTest.php
        └── BlockRequestValidationTest.php
```

---

## Database schema

### `users`
Standard Laravel users table (Breeze). No extra columns here.

### `sites`
```sql
id               BIGINT PK
user_id          BIGINT FK → users.id (unique — one site per user)
name             VARCHAR(100)
slug             VARCHAR(60) UNIQUE
bio              TEXT NULLABLE
avatar_url       VARCHAR(500) NULLABLE
published_at     TIMESTAMP NULLABLE
created_at, updated_at
```

### `blocks`
```sql
id               BIGINT PK
site_id          BIGINT FK → sites.id
type             VARCHAR(50)   -- 'links'|'text'|'image'|'video'|'social'|'music'
props            JSON          -- validated against schema for this type
order            INT DEFAULT 0
is_active        BOOLEAN DEFAULT true
is_published     BOOLEAN DEFAULT false
created_at, updated_at
```

> **Why JSON for `props`?** Each block type has different fields. A single JSON
> column keeps the schema flexible and allows adding new block types without new
> migrations. Props are always validated server-side against the block's schema.

---

## Block schema system — the core contract

`config/blocks.php` — consumed by `BlockSchemaRegistry`:

```php
return [
    'links' => [
        'label' => 'Links / Buttons',
        'icon'  => 'link',
        'fields' => [
            ['key' => 'title', 'type' => 'text', 'label' => 'Título', 'required' => true],
            ['key' => 'items', 'type' => 'repeater', 'label' => 'Links', 'required' => true,
             'subfields' => [
                ['key' => 'label', 'type' => 'text', 'label' => 'Texto del botón'],
                ['key' => 'url',   'type' => 'url',  'label' => 'URL'],
             ]],
            ['key' => 'color',      'type' => 'color', 'label' => 'Color fondo',  'default' => '#000000'],
            ['key' => 'text_color', 'type' => 'color', 'label' => 'Color texto',  'default' => '#ffffff'],
        ],
    ],
    'text'   => [ ... ],
    'image'  => [ ... ],
    'video'  => [ ... ],
    'social' => [ ... ],
    'music'  => [ ... ],
];
```

### Field types

| Type        | HTML rendered                 |
|-------------|-------------------------------|
| `text`      | `<input type="text">`         |
| `textarea`  | `<textarea>`                  |
| `url`       | `<input type="url">`          |
| `color`     | `<input type="color">`        |
| `select`    | `<select>` with `options` key |
| `image`     | File upload → storage URL     |
| `repeater`  | Dynamic list of subfields     |

`GET /api/block-schemas` exposes the full config as JSON. **Never hardcode schema shape in the FE.**

---

## API routes (authenticated, `auth:sanctum`)

```
GET    /api/block-schemas
GET    /api/blocks
POST   /api/blocks
PUT    /api/blocks/{id}
DELETE /api/blocks/{id}
POST   /api/blocks/{id}/duplicate
PATCH  /api/blocks/{id}/toggle
POST   /api/blocks/reorder           body: [{id, order}, ...]
POST   /api/site/publish
PATCH  /api/profile
```

---

## Phase-by-phase test specifications

Every commit has a **"Tests that must pass"** block.
Run the relevant tests before marking the commit done.

---

### Phase 1 — Setup inicial

#### `feat: laravel project init with breeze and inertia`

**`tests/Feature/Phase1/MigrationsTest.php`**
```php
it('runs all migrations without errors', function () {
    expect(true)->toBeTrue(); // RefreshDatabase already ran them
});

it('has the expected tables after migration', function () {
    expect(Schema::hasTable('users'))->toBeTrue();
    expect(Schema::hasTable('sites'))->toBeTrue();
    expect(Schema::hasTable('blocks'))->toBeTrue();
});

it('blocks table has required columns', function () {
    expect(Schema::hasColumns('blocks', [
        'id', 'site_id', 'type', 'props', 'order', 'is_active', 'is_published',
    ]))->toBeTrue();
});

it('sites table has required columns', function () {
    expect(Schema::hasColumns('sites', [
        'id', 'user_id', 'name', 'slug', 'bio', 'avatar_url', 'published_at',
    ]))->toBeTrue();
});
```

```bash
# ✅ COMMIT CHECKPOINT — run this before moving on
./bin/commit-step.sh "feat: laravel project init with breeze and inertia"
```

#### `feat: eloquent models and relationships`

**`tests/Feature/Phase1/ModelsRelationsTest.php`**
```php
it('user has one site', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['user_id' => $user->id]);
    expect($user->site->id)->toBe($site->id);
});

it('site belongs to user', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['user_id' => $user->id]);
    expect($site->user->id)->toBe($user->id);
});

it('site has many blocks', function () {
    $site = Site::factory()->create();
    Block::factory()->count(3)->create(['site_id' => $site->id]);
    expect($site->blocks)->toHaveCount(3);
});

it('block belongs to site', function () {
    $block = Block::factory()->create();
    expect($block->site)->toBeInstanceOf(Site::class);
});

it('props column casts to array', function () {
    $block = Block::factory()->create(['props' => ['title' => 'Hello']]);
    expect($block->fresh()->props)->toBeArray();
    expect($block->fresh()->props['title'])->toBe('Hello');
});
```

**`resources/js/tests/unit/useBlocks.test.js`** (scaffold)
```js
import { describe, it, expect } from 'vitest'

describe('useBlocks (scaffold)', () => {
  it('module can be imported', async () => {
    const mod = await import('../../../composables/useBlocks.js')
    expect(typeof mod.useBlocks).toBe('function')
  })
})
```

---


```bash
# ✅ COMMIT CHECKPOINT — run this before moving on
./bin/commit-step.sh "feat: eloquent models and relationships"
```

### Phase 2 — Autenticación y perfil

#### `feat: auth routes and site creation on register`

**`tests/Feature/Phase2/AuthTest.php`**
```php
it('user can register with valid data', function () {
    $this->post('/register', [
        'name' => 'Ana García', 'email' => 'ana@example.com',
        'password' => 'password', 'password_confirmation' => 'password',
    ])->assertRedirect('/dashboard');
    expect(User::where('email', 'ana@example.com')->exists())->toBeTrue();
});

it('a site is automatically created on registration', function () {
    $this->post('/register', [
        'name' => 'Ana', 'email' => 'ana@example.com',
        'password' => 'password', 'password_confirmation' => 'password',
    ]);
    $user = User::where('email', 'ana@example.com')->first();
    expect($user->site)->not->toBeNull();
});

it('auto-generated slug is url-safe', function () {
    $this->post('/register', [
        'name' => 'Ana García', 'email' => 'a@b.com',
        'password' => 'password', 'password_confirmation' => 'password',
    ]);
    $slug = User::where('email', 'a@b.com')->first()->site->slug;
    expect($slug)->toMatch('/^[a-z0-9\-]+$/');
});

it('user can login with correct credentials', function () {
    $user = User::factory()->create(['password' => bcrypt('secret')]);
    $this->post('/login', ['email' => $user->email, 'password' => 'secret'])
         ->assertRedirect('/dashboard');
});

it('login fails with wrong password', function () {
    $user = User::factory()->create(['password' => bcrypt('secret')]);
    $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])
         ->assertSessionHasErrors('email');
});

it('guest cannot access dashboard', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});
```


```bash
# ✅ COMMIT CHECKPOINT — run this before moving on
./bin/commit-step.sh "feat: auth routes and site creation on register"
```

#### `feat: profile and site update endpoints`

**`tests/Feature/Phase2/ProfileTest.php`**
```php
it('authenticated user can update their profile', function () {
    $user = User::factory()->hasSite()->create();
    $this->actingAs($user)
         ->patch('/api/profile', ['name' => 'New Name', 'bio' => 'Bio', 'slug' => 'new-slug'])
         ->assertOk();
    expect($user->site->fresh()->slug)->toBe('new-slug');
});

it('slug must be unique across sites', function () {
    User::factory()->hasSite(['slug' => 'taken'])->create();
    $user = User::factory()->hasSite()->create();
    $this->actingAs($user)
         ->patch('/api/profile', ['slug' => 'taken'])
         ->assertUnprocessable()
         ->assertJsonValidationErrors('slug');
});

it('slug rejects invalid characters', function () {
    $user = User::factory()->hasSite()->create();
    $this->actingAs($user)
         ->patch('/api/profile', ['slug' => 'my slug!'])
         ->assertUnprocessable();
});

it('user cannot overwrite another users site', function () {
    $victim   = User::factory()->hasSite(['slug' => 'victim-slug'])->create();
    $attacker = User::factory()->hasSite()->create();
    $this->actingAs($attacker)
         ->patch('/api/profile', ['slug' => 'new-for-attacker'])
         ->assertOk();
    expect($victim->site->fresh()->slug)->toBe('victim-slug');
});
```


```bash
# ✅ COMMIT CHECKPOINT — run this before moving on
./bin/commit-step.sh "feat: profile and site update endpoints"
```

#### `feat: avatar upload with storage`

**Added to `ProfileTest.php`**
```php
it('user can upload an avatar image', function () {
    Storage::fake('public');
    $user = User::factory()->hasSite()->create();
    $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);
    $this->actingAs($user)->patch('/api/profile', ['avatar' => $file])->assertOk();
    Storage::disk('public')->assertExists('avatars/' . $file->hashName());
    expect($user->site->fresh()->avatar_url)->toContain('avatars/');
});

it('rejects non-image avatar uploads', function () {
    $user = User::factory()->hasSite()->create();
    $file = UploadedFile::fake()->create('doc.pdf', 100);
    $this->actingAs($user)
         ->patch('/api/profile', ['avatar' => $file])
         ->assertUnprocessable()
         ->assertJsonValidationErrors('avatar');
});

it('avatar file size is capped at 2 MB', function () {
    $user = User::factory()->hasSite()->create();
    $file = UploadedFile::fake()->image('big.jpg')->size(3000);
    $this->actingAs($user)
         ->patch('/api/profile', ['avatar' => $file])
         ->assertUnprocessable();
});
```

**`resources/js/tests/components/Settings.test.js`**
```js
import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import Settings from '../../Pages/Dashboard/Settings.vue'

describe('Settings page', () => {
  it('renders name, slug, and bio fields', () => {
    const wrapper = mount(Settings, {
      props: { site: { name: 'Ana', slug: 'ana', bio: 'Hi', avatar_url: null } },
    })
    expect(wrapper.find('input[name="name"]').exists()).toBe(true)
    expect(wrapper.find('input[name="slug"]').exists()).toBe(true)
    expect(wrapper.find('textarea[name="bio"]').exists()).toBe(true)
  })

  it('shows avatar preview when avatar_url is set', () => {
    const wrapper = mount(Settings, {
      props: { site: { name: '', slug: '', bio: '', avatar_url: '/img/test.jpg' } },
    })
    expect(wrapper.find('img').attributes('src')).toBe('/img/test.jpg')
  })
})
```

---


```bash
# ✅ COMMIT CHECKPOINT — run this before moving on
./bin/commit-step.sh "feat: avatar upload with storage"
```

### Phase 3 — Sistema de bloques (core)

#### `feat: block schema definitions`

**`tests/Unit/BlockSchemaRegistryUnitTest.php`**
```php
it('returns all registered block types', function () {
    $registry = new BlockSchemaRegistry();
    expect(array_keys($registry->all()))
        ->toContain('links', 'text', 'image', 'video', 'social', 'music');
});

it('returns schema for a specific type', function () {
    $schema = (new BlockSchemaRegistry())->get('links');
    expect($schema)->toHaveKey('label')->toHaveKey('fields');
    expect($schema['fields'])->not->toBeEmpty();
});

it('throws on unknown block type', function () {
    expect(fn () => (new BlockSchemaRegistry())->get('unknown'))
        ->toThrow(InvalidArgumentException::class);
});

it('every field in every schema has key, type, and label', function () {
    foreach ((new BlockSchemaRegistry())->all() as $type => $schema) {
        foreach ($schema['fields'] as $field) {
            expect($field)->toHaveKeys(['key', 'type', 'label']);
        }
    }
});

it('GET /api/block-schemas returns all schemas', function () {
    $user = User::factory()->hasSite()->create();
    $this->actingAs($user)
         ->getJson('/api/block-schemas')
         ->assertOk()
         ->assertJsonStructure(['links', 'text', 'image', 'video', 'social', 'music']);
});
```


```bash
# ✅ COMMIT CHECKPOINT — run this before moving on
./bin/commit-step.sh "feat: block schema definitions"
```

#### `feat: block CRUD endpoints`

**`tests/Feature/Phase3/BlockCrudTest.php`**
```php
it('authenticated user can create a block', function () {
    $user = User::factory()->hasSite()->create();
    $this->actingAs($user)
         ->postJson('/api/blocks', ['type' => 'text', 'props' => ['content' => 'Hello']])
         ->assertCreated()
         ->assertJsonPath('data.type', 'text');
});

it('block is associated with the users site', function () {
    $user = User::factory()->hasSite()->create();
    $this->actingAs($user)
         ->postJson('/api/blocks', ['type' => 'text', 'props' => ['content' => 'Hi']]);
    expect($user->site->blocks)->toHaveCount(1);
});

it('creating a block with invalid props returns 422', function () {
    $user = User::factory()->hasSite()->create();
    $this->actingAs($user)
         ->postJson('/api/blocks', ['type' => 'text', 'props' => []])
         ->assertUnprocessable()
         ->assertJsonValidationErrors('props.content');
});

it('creating a block with unknown type returns 422', function () {
    $user = User::factory()->hasSite()->create();
    $this->actingAs($user)
         ->postJson('/api/blocks', ['type' => 'unknown', 'props' => []])
         ->assertUnprocessable();
});

it('user can list only their own blocks', function () {
    $user  = User::factory()->hasSite()->create();
    $other = User::factory()->hasSite()->create();
    Block::factory()->count(3)->create(['site_id' => $other->site->id]);
    Block::factory()->count(2)->create(['site_id' => $user->site->id]);
    $this->actingAs($user)
         ->getJson('/api/blocks')
         ->assertOk()
         ->assertJsonCount(2, 'data');
});

it('user can update props of their own block', function () {
    $user  = User::factory()->hasSite()->create();
    $block = Block::factory()->create([
        'site_id' => $user->site->id, 'type' => 'text', 'props' => ['content' => 'Old'],
    ]);
    $this->actingAs($user)
         ->putJson("/api/blocks/{$block->id}", ['props' => ['content' => 'New']])
         ->assertOk();
    expect($block->fresh()->props['content'])->toBe('New');
});

it('user cannot update a block from another site', function () {
    $owner  = User::factory()->hasSite()->create();
    $block  = Block::factory()->create([
        'site_id' => $owner->site->id, 'type' => 'text', 'props' => ['content' => 'x'],
    ]);
    $other = User::factory()->hasSite()->create();
    $this->actingAs($other)
         ->putJson("/api/blocks/{$block->id}", ['props' => ['content' => 'hacked']])
         ->assertForbidden();
});

it('user can delete their own block', function () {
    $user  = User::factory()->hasSite()->create();
    $block = Block::factory()->create(['site_id' => $user->site->id]);
    $this->actingAs($user)
         ->deleteJson("/api/blocks/{$block->id}")
         ->assertNoContent();
    expect(Block::find($block->id))->toBeNull();
});

it('user can duplicate a block', function () {
    $user  = User::factory()->hasSite()->create();
    $block = Block::factory()->create([
        'site_id' => $user->site->id, 'type' => 'text', 'props' => ['content' => 'Original'],
    ]);
    $this->actingAs($user)
         ->postJson("/api/blocks/{$block->id}/duplicate")
         ->assertCreated();
    expect($user->site->blocks)->toHaveCount(2);
});

it('toggle flips is_active', function () {
    $user  = User::factory()->hasSite()->create();
    $block = Block::factory()->create(['site_id' => $user->site->id, 'is_active' => true]);
    $this->actingAs($user)->patchJson("/api/blocks/{$block->id}/toggle")->assertOk();
    expect($block->fresh()->is_active)->toBeFalse();
});
```


```bash
# ✅ COMMIT CHECKPOINT — run this before moving on
./bin/commit-step.sh "feat: block CRUD endpoints"
```

#### `feat: block reorder endpoint`

**`tests/Feature/Phase3/BlockReorderTest.php`**
```php
it('user can reorder their blocks', function () {
    $user   = User::factory()->hasSite()->create();
    $first  = Block::factory()->create(['site_id' => $user->site->id, 'order' => 0]);
    $second = Block::factory()->create(['site_id' => $user->site->id, 'order' => 1]);

    $this->actingAs($user)
         ->postJson('/api/blocks/reorder', [
             'blocks' => [
                 ['id' => $first->id,  'order' => 1],
                 ['id' => $second->id, 'order' => 0],
             ],
         ])
         ->assertOk();

    expect($first->fresh()->order)->toBe(1);
    expect($second->fresh()->order)->toBe(0);
});

it('cannot reorder blocks from another site', function () {
    $owner = User::factory()->hasSite()->create();
    $block = Block::factory()->create(['site_id' => $owner->site->id, 'order' => 0]);
    $other = User::factory()->hasSite()->create();
    $this->actingAs($other)
         ->postJson('/api/blocks/reorder', ['blocks' => [['id' => $block->id, 'order' => 99]]])
         ->assertForbidden();
});
```

**`resources/js/tests/unit/useBlocks.test.js`**
```js
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { useBlocks } from '../../../composables/useBlocks.js'

global.fetch = vi.fn()

describe('useBlocks', () => {
  beforeEach(() => vi.clearAllMocks())

  it('initializes with empty block list', () => {
    const { blocks } = useBlocks([])
    expect(blocks.value).toEqual([])
  })

  it('initializes with provided blocks', () => {
    const { blocks } = useBlocks([{ id: 1, type: 'text', props: {}, order: 0 }])
    expect(blocks.value).toHaveLength(1)
  })

  it('sortedBlocks returns blocks in ascending order', () => {
    const { sortedBlocks } = useBlocks([
      { id: 2, order: 1, type: 'text', props: {} },
      { id: 1, order: 0, type: 'text', props: {} },
    ])
    expect(sortedBlocks.value[0].id).toBe(1)
    expect(sortedBlocks.value[1].id).toBe(2)
  })

  it('addBlock inserts a new block on success', async () => {
    fetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ data: { id: 99, type: 'text', props: { content: 'New' }, order: 0 } }),
    })
    const { blocks, addBlock } = useBlocks([])
    await addBlock('text', { content: 'New' })
    expect(blocks.value).toHaveLength(1)
    expect(blocks.value[0].id).toBe(99)
  })

  it('removeBlock removes item from local state', async () => {
    fetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) })
    const { blocks, removeBlock } = useBlocks([{ id: 1, type: 'text', props: {}, order: 0 }])
    await removeBlock(1)
    expect(blocks.value).toHaveLength(0)
  })

  it('toggleBlock flips is_active', async () => {
    fetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ data: { id: 1, is_active: false } }),
    })
    const { blocks, toggleBlock } = useBlocks([{ id: 1, is_active: true, type: 'text', props: {}, order: 0 }])
    await toggleBlock(1)
    expect(blocks.value[0].is_active).toBe(false)
  })
})
```

**`resources/js/tests/components/BlockEditor.test.js`**
```js
import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import BlockEditor from '../../Components/Editor/BlockEditor.vue'

const textSchema = {
  fields: [
    { key: 'content', type: 'textarea', label: 'Contenido', required: true },
    { key: 'align',   type: 'select',   label: 'Alineación', options: ['left', 'center', 'right'] },
  ],
}

describe('BlockEditor', () => {
  it('renders one input per schema field', () => {
    const wrapper = mount(BlockEditor, {
      props: { schema: textSchema, modelValue: { content: '', align: 'left' } },
    })
    expect(wrapper.find('textarea').exists()).toBe(true)
    expect(wrapper.find('select').exists()).toBe(true)
  })

  it('emits update:modelValue when a field changes', async () => {
    const wrapper = mount(BlockEditor, {
      props: { schema: textSchema, modelValue: { content: '', align: 'left' } },
    })
    await wrapper.find('textarea').setValue('Hello')
    expect(wrapper.emitted('update:modelValue')[0][0].content).toBe('Hello')
  })
})
```

**`resources/js/tests/components/BlockCatalog.test.js`**
```js
import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import BlockCatalog from '../../Components/Editor/BlockCatalog.vue'

const schemas = {
  text:  { label: 'Texto',  icon: 'text'  },
  links: { label: 'Links',  icon: 'link'  },
  image: { label: 'Imagen', icon: 'image' },
}

describe('BlockCatalog', () => {
  it('renders one card per block type', () => {
    const wrapper = mount(BlockCatalog, { props: { schemas } })
    expect(wrapper.findAll('[data-block-type]')).toHaveLength(3)
  })

  it('emits select with block type on click', async () => {
    const wrapper = mount(BlockCatalog, { props: { schemas } })
    await wrapper.find('[data-block-type="text"]').trigger('click')
    expect(wrapper.emitted('select')[0][0]).toBe('text')
  })
})
```

**`resources/js/tests/components/BlockCard.test.js`**
```js
import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import BlockCard from '../../Components/Editor/BlockCard.vue'

const block = { id: 1, type: 'text', props: { content: 'Hi' }, is_active: true, order: 0 }

describe('BlockCard', () => {
  it('shows block type label', () => {
    expect(mount(BlockCard, { props: { block } }).text()).toContain('text')
  })

  it('emits delete when delete button clicked', async () => {
    const wrapper = mount(BlockCard, { props: { block } })
    await wrapper.find('[data-action="delete"]').trigger('click')
    expect(wrapper.emitted('delete')).toBeTruthy()
  })

  it('emits toggle when toggle button clicked', async () => {
    const wrapper = mount(BlockCard, { props: { block } })
    await wrapper.find('[data-action="toggle"]').trigger('click')
    expect(wrapper.emitted('toggle')).toBeTruthy()
  })

  it('emits duplicate when duplicate button clicked', async () => {
    const wrapper = mount(BlockCard, { props: { block } })
    await wrapper.find('[data-action="duplicate"]').trigger('click')
    expect(wrapper.emitted('duplicate')).toBeTruthy()
  })

  it('shows inactive indicator when is_active is false', () => {
    const wrapper = mount(BlockCard, { props: { block: { ...block, is_active: false } } })
    expect(wrapper.find('[data-inactive]').exists()).toBe(true)
  })
})
```

**`resources/js/tests/unit/FieldRenderer.test.js`**
```js
import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import FieldRenderer from '../../Components/Editor/FieldRenderer.vue'

const cases = [
  { type: 'text',     selector: 'input[type="text"]' },
  { type: 'textarea', selector: 'textarea' },
  { type: 'url',      selector: 'input[type="url"]' },
  { type: 'color',    selector: 'input[type="color"]' },
  { type: 'select',   selector: 'select',
    field: { key: 'x', type: 'select', label: 'X', options: ['a', 'b'] } },
]

describe('FieldRenderer', () => {
  cases.forEach(({ type, selector, field }) => {
    it(`renders ${selector} for type "${type}"`, () => {
      const f = field ?? { key: 'x', type, label: 'X' }
      const wrapper = mount(FieldRenderer, { props: { field: f, modelValue: '' } })
      expect(wrapper.find(selector).exists()).toBe(true)
    })
  })

  it('emits update:modelValue on input', async () => {
    const wrapper = mount(FieldRenderer, {
      props: { field: { key: 'x', type: 'text', label: 'X' }, modelValue: '' },
    })
    await wrapper.find('input').setValue('hello')
    expect(wrapper.emitted('update:modelValue')[0][0]).toBe('hello')
  })
})
```


```bash
# ✅ COMMIT CHECKPOINT — run this before moving on
./bin/commit-step.sh "feat: block reorder endpoint"
```

### Phase 4 — Preview en vivo + página pública

#### `feat: draft and published versioning on blocks`

**`tests/Feature/Phase4/PublishFlowTest.php`**
```php
it('publishing marks all active blocks as is_published', function () {
    $user  = User::factory()->hasSite()->create();
    $block = Block::factory()->create(['site_id' => $user->site->id, 'is_active' => true, 'is_published' => false]);
    $this->actingAs($user)->postJson('/api/site/publish')->assertOk();
    expect($block->fresh()->is_published)->toBeTrue();
});

it('inactive blocks are not published', function () {
    $user  = User::factory()->hasSite()->create();
    $block = Block::factory()->create(['site_id' => $user->site->id, 'is_active' => false]);
    $this->actingAs($user)->postJson('/api/site/publish')->assertOk();
    expect($block->fresh()->is_published)->toBeFalse();
});

it('publishing sets published_at on the site', function () {
    $user = User::factory()->hasSite()->create();
    $this->actingAs($user)->postJson('/api/site/publish')->assertOk();
    expect($user->site->fresh()->published_at)->not->toBeNull();
});

it('unpublished content is not visible on public page', function () {
    $user = User::factory()->hasSite(['slug' => 'jane'])->create();
    Block::factory()->create([
        'site_id' => $user->site->id, 'type' => 'text',
        'props' => ['content' => 'Draft content'], 'is_published' => false, 'is_active' => true,
    ]);
    $this->get('/@jane')->assertDontSee('Draft content');
});

it('published content is visible on public page', function () {
    $user = User::factory()->hasSite(['slug' => 'jane'])->create();
    Block::factory()->create([
        'site_id' => $user->site->id, 'type' => 'text',
        'props' => ['content' => 'Published content'], 'is_published' => true, 'is_active' => true,
    ]);
    $this->get('/@jane')->assertSee('Published content');
});
```


```bash
# ✅ COMMIT CHECKPOINT — run this before moving on
./bin/commit-step.sh "feat: draft and published versioning on blocks"
```

#### `feat: public route /@slug with caching`

**`tests/Feature/Phase4/PublicPageTest.php`**
```php
it('public page returns 200 for a valid slug', function () {
    User::factory()->hasSite(['slug' => 'alice'])->create();
    $this->get('/@alice')->assertOk();
});

it('public page returns 404 for unknown slug', function () {
    $this->get('/@nobody')->assertNotFound();
});

it('public page shows site name and bio', function () {
    User::factory()->hasSite(['slug' => 'bob', 'name' => 'Bob Builder', 'bio' => 'I can fix it'])->create();
    $this->get('/@bob')->assertSee('Bob Builder')->assertSee('I can fix it');
});

it('inactive blocks are not rendered on public page', function () {
    $user = User::factory()->hasSite(['slug' => 'carol'])->create();
    Block::factory()->create([
        'site_id' => $user->site->id, 'type' => 'text',
        'props' => ['content' => 'Hidden'], 'is_active' => false, 'is_published' => true,
    ]);
    $this->get('/@carol')->assertDontSee('Hidden');
});

it('public page is cached after first load', function () {
    Cache::spy();
    User::factory()->hasSite(['slug' => 'dave'])->create();
    $this->get('/@dave');
    Cache::shouldHaveReceived('remember')
         ->with(Mockery::pattern('/dave/'), Mockery::any(), Mockery::any())
         ->once();
});

it('cache is invalidated after publishing', function () {
    Cache::spy();
    $user = User::factory()->hasSite(['slug' => 'eve'])->create();
    $this->actingAs($user)->postJson('/api/site/publish');
    Cache::shouldHaveReceived('forget')
         ->with(Mockery::pattern('/eve/'))
         ->once();
});

it('public page does not load the vue dashboard bundle', function () {
    User::factory()->hasSite(['slug' => 'frank'])->create();
    $this->get('/@frank')->assertDontSee('app.js');
});
```

**`resources/js/tests/components/PreviewFrame.test.js`**
```js
import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import PreviewFrame from '../../Components/Preview/PreviewFrame.vue'

describe('PreviewFrame', () => {
  it('renders a block for each active block', () => {
    const blocks = [
      { id: 1, type: 'text',  props: { content: 'Hello' }, is_active: true,  order: 0 },
      { id: 2, type: 'image', props: { url: '/img.jpg', alt: '' }, is_active: true, order: 1 },
    ]
    const wrapper = mount(PreviewFrame, { props: { blocks, site: { name: '', bio: '' } } })
    expect(wrapper.find('[data-block-type="text"]').exists()).toBe(true)
    expect(wrapper.find('[data-block-type="image"]').exists()).toBe(true)
  })

  it('does not render inactive blocks', () => {
    const blocks = [{ id: 1, type: 'text', props: { content: 'Hidden' }, is_active: false, order: 0 }]
    const wrapper = mount(PreviewFrame, { props: { blocks, site: { name: '', bio: '' } } })
    expect(wrapper.find('[data-block-type="text"]').exists()).toBe(false)
  })

  it('renders blocks in order asc', () => {
    const blocks = [
      { id: 2, type: 'image', props: { url: '/b.jpg', alt: '' }, is_active: true, order: 1 },
      { id: 1, type: 'text',  props: { content: 'First' },        is_active: true, order: 0 },
    ]
    const wrapper = mount(PreviewFrame, { props: { blocks, site: { name: '', bio: '' } } })
    const rendered = wrapper.findAll('[data-block-type]')
    expect(rendered[0].attributes('data-block-type')).toBe('text')
    expect(rendered[1].attributes('data-block-type')).toBe('image')
  })
})
```

**`resources/js/tests/unit/usePublish.test.js`**
```js
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { usePublish } from '../../../composables/usePublish.js'

global.fetch = vi.fn()

describe('usePublish', () => {
  beforeEach(() => vi.clearAllMocks())

  it('isDirty starts as false', () => {
    expect(usePublish().isDirty.value).toBe(false)
  })

  it('markDirty sets isDirty to true', () => {
    const { isDirty, markDirty } = usePublish()
    markDirty()
    expect(isDirty.value).toBe(true)
  })

  it('publish calls the API and resets isDirty', async () => {
    fetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) })
    const { isDirty, markDirty, publish } = usePublish()
    markDirty()
    await publish()
    expect(isDirty.value).toBe(false)
    expect(fetch).toHaveBeenCalledWith('/api/site/publish', expect.any(Object))
  })
})
```

---


```bash
# ✅ COMMIT CHECKPOINT — run this before moving on
./bin/commit-step.sh "feat: public route /@slug with caching"
```

### Phase 5 — Bloques extra, responsividad y SEO

#### `feat: SEO meta tags on public page`

**`tests/Feature/Phase5/SeoMetaTest.php`**
```php
it('public page title is person name hyphen public site name', function () {
    User::factory()
        ->state(['name' => 'Ada Lovelace'])
        ->hasSite(['slug' => 'grace', 'name' => 'Mi página pública'])
        ->create();
    $this->get('/@grace')->assertSee('<title>Ada Lovelace - Mi página pública</title>', false);
});

it('public page og:title matches document title', function () {
    User::factory()
        ->state(['name' => 'Ada Lovelace'])
        ->hasSite(['slug' => 'grace', 'name' => 'Mi página pública'])
        ->create();
    $this->get('/@grace')
         ->assertSee('property="og:title"', false)
         ->assertSee('Ada Lovelace - Mi página pública', false);
});

it('public page uses bio as meta and og:description when bio is set', function () {
    User::factory()->hasSite(['slug' => 'grace', 'bio' => 'Pioneer of computing'])->create();
    $this->get('/@grace')
         ->assertSee('name="description"', false)
         ->assertSee('Pioneer of computing', false)
         ->assertSee('property="og:description"', false);
});

it('public page uses default description when bio is empty', function () {
    User::factory()->hasSite(['slug' => 'grace', 'bio' => null])->create();
    $this->get('/@grace')
         ->assertSee('name="description"', false)
         ->assertSee(e(config('seo.default_description')), false);
});

it('public page has og:image when avatar is set', function () {
    User::factory()->hasSite(['slug' => 'grace', 'avatar_url' => 'https://cdn.test/avatar.jpg'])->create();
    $this->get('/@grace')->assertSee('https://cdn.test/avatar.jpg');
});
```


```bash
# ✅ COMMIT CHECKPOINT — run this before moving on
./bin/commit-step.sh "feat: SEO meta tags on public page"
```

#### `feat: additional block schemas (music, social icons)`

**`tests/Feature/Phase5/ExtraBlockSchemasTest.php`**
```php
it('music schema has platform and url fields', function () {
    $fields = collect((new BlockSchemaRegistry())->get('music')['fields']);
    expect($fields->pluck('key')->toArray())->toContain('platform', 'url');
});

it('social schema has a links repeater', function () {
    $fields = collect((new BlockSchemaRegistry())->get('social')['fields']);
    expect($fields->firstWhere('type', 'repeater'))->not->toBeNull();
});

it('can create a music block via API', function () {
    $user = User::factory()->hasSite()->create();
    $this->actingAs($user)
         ->postJson('/api/blocks', [
             'type'  => 'music',
             'props' => ['platform' => 'spotify', 'url' => 'https://open.spotify.com/track/123'],
         ])
         ->assertCreated();
});
```

**`resources/js/tests/components/blocks/BlockMusic.test.js`**
```js
import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import BlockMusic from '../../../Components/Blocks/BlockMusic.vue'

describe('BlockMusic', () => {
  it('renders a Spotify embed for spotify platform', () => {
    const wrapper = mount(BlockMusic, {
      props: { props: { platform: 'spotify', url: 'https://open.spotify.com/track/123' } },
    })
    expect(wrapper.find('iframe').attributes('src')).toContain('spotify')
  })

  it('renders a Bandcamp embed for bandcamp platform', () => {
    const wrapper = mount(BlockMusic, {
      props: { props: { platform: 'bandcamp', url: 'https://artist.bandcamp.com/track/x' } },
    })
    expect(wrapper.find('iframe').attributes('src')).toContain('bandcamp')
  })
})
```

**`resources/js/tests/components/blocks/BlockVideo.test.js`**
```js
import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import BlockVideo from '../../../Components/Blocks/BlockVideo.vue'

describe('BlockVideo', () => {
  it('renders a YouTube embed', () => {
    const wrapper = mount(BlockVideo, {
      props: { props: { url: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ' } },
    })
    expect(wrapper.find('iframe').attributes('src')).toContain('youtube.com/embed')
  })

  it('renders a TikTok embed', () => {
    const wrapper = mount(BlockVideo, {
      props: { props: { url: 'https://www.tiktok.com/@user/video/123' } },
    })
    expect(wrapper.find('[data-tiktok]').exists()).toBe(true)
  })
})
```

**`resources/js/tests/components/blocks/BlockLinks.test.js`**
```js
import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import BlockLinks from '../../../Components/Blocks/BlockLinks.vue'

const props = {
  title: 'My Links',
  items: [
    { label: 'GitHub',  url: 'https://github.com/user' },
    { label: 'Twitter', url: 'https://twitter.com/user' },
  ],
  color: '#000000',
  text_color: '#ffffff',
}

describe('BlockLinks', () => {
  it('renders one anchor per link item', () => {
    expect(mount(BlockLinks, { props: { props } }).findAll('a')).toHaveLength(2)
  })

  it('renders block title', () => {
    expect(mount(BlockLinks, { props: { props } }).text()).toContain('My Links')
  })

  it('links have correct href', () => {
    const wrapper = mount(BlockLinks, { props: { props } })
    expect(wrapper.find('a').attributes('href')).toBe('https://github.com/user')
  })
})
```


```bash
# ✅ COMMIT CHECKPOINT — run this before moving on
./bin/commit-step.sh "feat: additional block schemas (music, social icons)"
```

### Phase 6 — QA, documentación y entrega

#### `feat: database seeder with demo data`

**`tests/Feature/Phase6/DemoSeederTest.php`**
```php
it('demo seeder creates a user with a complete site', function () {
    $this->artisan('db:seed', ['--class' => 'DemoSeeder'])->assertSuccessful();
    $user = User::where('email', 'demo@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->site)->not->toBeNull();
    expect($user->site->blocks->count())->toBeGreaterThanOrEqual(4);
});

it('demo site has at least one block of each MVP type', function () {
    $this->artisan('db:seed', ['--class' => 'DemoSeeder']);
    $types = User::where('email', 'demo@example.com')->first()
                 ->site->blocks->pluck('type')->unique()->toArray();
    foreach (['links', 'text', 'image', 'video'] as $required) {
        expect($types)->toContain($required);
    }
});

it('demo site is published and publicly accessible', function () {
    $this->artisan('db:seed', ['--class' => 'DemoSeeder']);
    $slug = User::where('email', 'demo@example.com')->first()->site->slug;
    $this->get("/@{$slug}")->assertOk();
});
```


```bash
# ✅ COMMIT CHECKPOINT — run this before moving on
./bin/commit-step.sh "feat: database seeder with demo data"
```

#### Final gate — run before tagging the release

```bash
php artisan test --stop-on-failure   # must be 0 failures
npm run test:run                     # must be 0 failures
```

---

## Public page strategy (performance + SEO)

`/@{slug}` renders a **plain Blade view** — no Inertia, no Vue bundle:

- Faster TTFB, no JS framework boot.
- Fully crawlable HTML.
- `Cache::remember("public.site.$slug", 300, ...)` — invalidated on publish.
- `<title>` / `og:title`: `{user.name} - {site.name}`; `meta description` / `og:description`: site bio or `config('seo.default_description')`.

---

## Frontend conventions

No Pinia/Vuex. State lives in `useBlocks()` and `usePublish()`.
Inertia page props seed the initial data; mutations patch local state optimistically.

Every interactive element must carry a `data-action="..."` or `data-block-type="..."`
attribute — these are the selectors the component tests depend on.

---

## Naming conventions

- PHP classes: PascalCase — PHP methods: camelCase
- Vue components: PascalCase filenames — Composables: `use`-prefixed camelCase
- DB columns + API JSON keys: snake_case
- Test data attributes: `data-action`, `data-block-type`, `data-inactive`
- Git commits: `type: short description` (feat / fix / refactor / test / docs / chore)

---

## Role boundaries

**BE owns**: `app/`, `config/`, `database/`, `routes/`, `tests/Feature/`, `tests/Unit/`, Blade views.
**FE owns**: `resources/js/`, `resources/js/tests/`, Tailwind config.

Shared decisions to align before Phase 3:
1. Schema field shape — agree before FE starts `FieldRenderer.vue`
2. API response envelope — use Laravel Resources consistently
3. Props JSON shape per block type — document an example before building renderers
4. `data-*` attribute names — FE defines them, BE never touches them

---

## Environment setup

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
npm install && npm run dev
php artisan storage:link
php artisan serve
```

`.env` required:
```
DB_DATABASE=pagebuilder
DB_USERNAME=root
DB_PASSWORD=
APP_URL=http://localhost:8000
FILESYSTEM_DISK=public
```

`phpunit.xml` for tests:
```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
<env name="CACHE_DRIVER" value="array"/>
```

---

## Out of scope for MVP

- Custom domain mapping, analytics, multiple sites per user, billing
- Block-level A/B testing
- Redis cache, image resize queues, rate limiting (Cloudflare for prod)
- E2E tests with Playwright / Cypress (recommended post-MVP)

---

## Quick start

Save this file as `bin/quickstart.sh` in the root of the repo, then run:

```bash
chmod +x bin/quickstart.sh && ./bin/quickstart.sh
```

```bash
#!/usr/bin/env bash
# bin/quickstart.sh — Page Builder · eSponsor Challenge
# Sets up the full dev environment from a clean clone.
# Usage: ./bin/quickstart.sh [--seed-demo] [--skip-npm]

set -euo pipefail

# ── colours ──────────────────────────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
CYAN='\033[0;36m'; BOLD='\033[1m'; RESET='\033[0m'

step()  { echo -e "\n${CYAN}${BOLD}▶ $*${RESET}"; }
ok()    { echo -e "${GREEN}✔ $*${RESET}"; }
warn()  { echo -e "${YELLOW}⚠ $*${RESET}"; }
die()   { echo -e "${RED}✖ $*${RESET}" >&2; exit 1; }

# ── flags ─────────────────────────────────────────────────────────────────────
SEED_DEMO=false
SKIP_NPM=false
for arg in "$@"; do
  case $arg in
    --seed-demo) SEED_DEMO=true ;;
    --skip-npm)  SKIP_NPM=true  ;;
    --help|-h)
      echo "Usage: $0 [--seed-demo] [--skip-npm]"
      echo "  --seed-demo   Also run DemoSeeder after migrate"
      echo "  --skip-npm    Skip npm install + build (useful in CI)"
      exit 0 ;;
    *) warn "Unknown flag: $arg (ignored)" ;;
  esac
done

# ── requirements check ────────────────────────────────────────────────────────
step "Checking system requirements"

require() {
  local cmd=$1 hint=$2
  command -v "$cmd" &>/dev/null || die "$cmd not found. $hint"
  ok "$cmd $(${cmd} --version 2>&1 | head -1)"
}

require php    "Install PHP 8.2+ (https://php.net)"
require composer "Install Composer (https://getcomposer.org)"
require mysql  "Install MySQL 8 or set DB_CONNECTION=sqlite in .env"

PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
[[ "$(echo "$PHP_VERSION >= 8.2" | bc -l 2>/dev/null || echo 0)" == "1" ]] \
  || php -r "version_compare(PHP_VERSION, '8.2.0', '>=') or die();" \
  || die "PHP 8.2+ required (found $PHP_VERSION)"

if [[ "$SKIP_NPM" == false ]]; then
  require node "Install Node.js 18+ (https://nodejs.org)"
  require npm  "npm ships with Node"
fi

ok "All requirements met"

# ── .env setup ────────────────────────────────────────────────────────────────
step "Setting up .env"

if [[ ! -f .env ]]; then
  if [[ -f .env.example ]]; then
    cp .env.example .env
    ok "Copied .env.example → .env"
  else
    die ".env.example not found. Are you in the project root?"
  fi
else
  warn ".env already exists — skipping copy"
fi

# ── composer ──────────────────────────────────────────────────────────────────
step "Installing PHP dependencies"
composer install --no-interaction --prefer-dist --optimize-autoloader
ok "composer install done"

# ── app key ───────────────────────────────────────────────────────────────────
step "Generating application key"
if grep -q "APP_KEY=$" .env || grep -q "APP_KEY=\"\"" .env; then
  php artisan key:generate
  ok "Key generated"
else
  warn "APP_KEY already set — skipping"
fi

# ── database ──────────────────────────────────────────────────────────────────
step "Running migrations"

DB_CON=$(php artisan tinker --execute="echo config('database.default');" 2>/dev/null | tail -1)

if [[ "$DB_CON" == "sqlite" ]]; then
  DB_PATH=$(php artisan tinker --execute="echo config('database.connections.sqlite.database');" 2>/dev/null | tail -1)
  if [[ ! -f "$DB_PATH" ]]; then
    touch "$DB_PATH"
    ok "Created SQLite database at $DB_PATH"
  fi
fi

php artisan migrate --force
ok "Migrations complete"

if [[ "$SEED_DEMO" == true ]]; then
  step "Seeding demo data"
  php artisan db:seed --class=DemoSeeder --force
  ok "Demo site seeded (demo@example.com / password)"
fi

# ── storage ───────────────────────────────────────────────────────────────────
step "Linking storage"
if [[ ! -L public/storage ]]; then
  php artisan storage:link
  ok "Storage linked"
else
  warn "public/storage symlink already exists — skipping"
fi

# ── npm ───────────────────────────────────────────────────────────────────────
if [[ "$SKIP_NPM" == false ]]; then
  step "Installing JS dependencies"
  npm install
  ok "npm install done"

  step "Building assets"
  npm run build
  ok "Assets built"
fi

# ── test suite ────────────────────────────────────────────────────────────────
step "Running backend tests"
php artisan test --stop-on-failure \
  && ok "All backend tests passed" \
  || die "Backend tests failed — fix before continuing"

if [[ "$SKIP_NPM" == false ]]; then
  step "Running frontend tests"
  npm run test:run \
    && ok "All frontend tests passed" \
    || die "Frontend tests failed — fix before continuing"
fi

# ── done ──────────────────────────────────────────────────────────────────────
echo ""
echo -e "${GREEN}${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${RESET}"
echo -e "${GREEN}${BOLD}  ✔  Setup complete!${RESET}"
echo -e "${GREEN}${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${RESET}"
echo ""
echo -e "  Start the dev server:  ${CYAN}php artisan serve${RESET}"
echo -e "  Start Vite watcher:    ${CYAN}npm run dev${RESET}"

if [[ "$SEED_DEMO" == true ]]; then
  APP_URL=$(grep "^APP_URL=" .env | cut -d= -f2)
  echo ""
  echo -e "  Demo login:   ${CYAN}demo@example.com${RESET} / ${CYAN}password${RESET}"
  echo -e "  Public page:  ${CYAN}${APP_URL}/@demo${RESET}"
fi

echo ""
```
