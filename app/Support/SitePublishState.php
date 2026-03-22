<?php

namespace App\Support;

use App\Models\Site;

/**
 * Estado publicable del sitio: comparación estructural de bloques activos (orden, props, is_published).
 * No usa updated_at (rompe “volver a lo publicado” y otras rutas).
 */
final class SitePublishState
{
    /**
     * JSON canónico de bloques activos, ordenados por `order` e `id`.
     */
    public static function snapshot(Site $site): string
    {
        $rows = $site->blocks()
            ->where('is_active', true)
            ->orderBy('order')
            ->orderBy('id')
            ->get(['id', 'order', 'props', 'is_published']);

        $payload = $rows->map(fn ($b) => [
            'id' => (int) $b->id,
            'order' => (int) $b->order,
            'p' => self::normalizeProps($b->props ?? []),
            'pub' => (bool) $b->is_published,
        ])->values()->all();

        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    public static function hasPendingChanges(Site $site): bool
    {
        $hasUnpublished = $site->blocks()
            ->where('is_active', true)
            ->where('is_published', false)
            ->exists();

        $stored = $site->published_blocks_snapshot;

        if ($stored === null) {
            return $hasUnpublished;
        }

        if ($hasUnpublished) {
            return true;
        }

        return ! hash_equals($stored, self::snapshot($site));
    }

    private static function normalizeProps(mixed $props): mixed
    {
        if (! is_array($props)) {
            return $props;
        }

        if (array_is_list($props)) {
            return array_map(
                fn ($item) => is_array($item) ? self::normalizeProps($item) : $item,
                $props,
            );
        }

        ksort($props);

        foreach ($props as $k => $v) {
            $props[$k] = is_array($v) ? self::normalizeProps($v) : $v;
        }

        return $props;
    }
}
