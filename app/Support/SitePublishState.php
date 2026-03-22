<?php

namespace App\Support;

use App\Models\Block;
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

    /**
     * Restaura el campo `order` de los bloques publicados según el último snapshot
     * (p. ej. tras «volver a lo publicado»: quita borradores y revierte el orden al de la última publicación).
     */
    public static function restorePublishedBlockOrdersFromSnapshot(Site $site, string $snapshotJson): void
    {
        try {
            /** @var list<array<string, mixed>> $rows */
            $rows = json_decode($snapshotJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return;
        }

        foreach ($rows as $row) {
            if (! isset($row['id'], $row['order'])) {
                continue;
            }

            if (array_key_exists('pub', $row) && $row['pub'] === false) {
                continue;
            }

            $block = Block::query()
                ->where('id', (int) $row['id'])
                ->where('site_id', $site->id)
                ->where('is_active', true)
                ->where('is_published', true)
                ->first();

            if ($block !== null) {
                $block->update(['order' => (int) $row['order']]);
            }
        }
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
