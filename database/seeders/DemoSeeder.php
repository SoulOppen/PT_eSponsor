<?php

namespace Database\Seeders;

use App\Models\Block;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Demo Creator',
            'email' => 'demo@example.com',
            'password' => Hash::make('password'),
        ]);

        $site = Site::query()->create([
            'user_id' => $user->id,
            'name' => 'Demo Site',
            'slug' => 'demo-showcase',
            'bio' => 'Página de demostración del page builder.',
            'avatar_url' => null,
            'published_at' => now(),
        ]);

        $definitions = [
            ['type' => 'text', 'order' => 0, 'props' => ['content' => 'Bienvenido al sitio demo.']],
            ['type' => 'links', 'order' => 1, 'props' => ['title' => 'Mi enlace', 'url' => 'https://example.com']],
            ['type' => 'image', 'order' => 2, 'props' => ['url' => 'https://placehold.co/600x400/png', 'alt' => 'Placeholder']],
            ['type' => 'video', 'order' => 3, 'props' => ['url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']],
        ];

        foreach ($definitions as $row) {
            Block::query()->create([
                'site_id' => $site->id,
                'type' => $row['type'],
                'props' => $row['props'],
                'order' => $row['order'],
                'is_active' => true,
                'is_published' => true,
            ]);
        }
    }
}
