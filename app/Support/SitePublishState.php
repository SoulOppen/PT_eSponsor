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
            ->get(['id', 'order', 'type', 'props', 'is_published']);

        $payload = $rows->map(fn ($b) => [
            'id' => (int) $b->id,
            'order' => (int) $b->order,
            't' => (string) $b->type,
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
     * Deja solo los bloques cuyo id aparece en el snapshot de última publicación (cantidad + identidad).
     * Snapshot null/vacío en columna: solo elimina borradores (is_published = false).
     * JSON "[]": última publicación sin bloques → vacía el sitio.
     */
    public static function pruneSiteBlocksToBaseline(Site $site, ?string $baselineSnapshot): void
    {
        if ($baselineSnapshot === null || $baselineSnapshot === '') {
            $site->blocks()->where('is_published', false)->delete();

            return;
        }

        try {
            /** @var list<array<string, mixed>> $rows */
            $rows = json_decode($baselineSnapshot, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $site->blocks()->where('is_published', false)->delete();

            return;
        }

        if ($rows === []) {
            $site->blocks()->delete();

            return;
        }

        $allowedIds = [];
        foreach ($rows as $row) {
            if (! isset($row['id'])) {
                continue;
            }
            if (array_key_exists('pub', $row) && $row['pub'] === false) {
                continue;
            }
            $allowedIds[] = (int) $row['id'];
        }

        if (count($allowedIds) > 0) {
            $site->blocks()->whereNotIn('id', $allowedIds)->delete();
        } else {
            $site->blocks()->where('is_published', false)->delete();
        }
    }

    /**
     * Restaura orden, props (`p`) y marca publicado según el último snapshot guardado
     * (tras «volver a lo publicado»: mismo contenido que la última publicación, no solo la lista).
     * Si un bloque publicado fue borrado en BD pero sigue en el snapshot, se recrea con el mismo `id`.
     */
    public static function restorePublishedBlocksFromSnapshot(Site $site, string $snapshotJson): void
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

            $blockId = (int) $row['id'];

            $block = Block::query()
                ->where('id', $blockId)
                ->where('site_id', $site->id)
                ->first();

            if ($block === null) {
                self::recreateBlockFromSnapshotRow($site, $row, $blockId);

                continue;
            }

            $updates = [
                'order' => (int) $row['order'],
                'is_published' => true,
            ];

            if (array_key_exists('p', $row) && is_array($row['p'])) {
                $updates['props'] = $row['p'];
            }

            $block->update($updates);
        }
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function recreateBlockFromSnapshotRow(Site $site, array $row, int $blockId): void
    {
        $type = self::resolveTypeFromSnapshotRow($row);
        $props = array_key_exists('p', $row) && is_array($row['p']) ? $row['p'] : [];

        $new = new Block;
        $new->id = $blockId;
        $new->site_id = (int) $site->id;
        $new->type = $type;
        $new->props = $props;
        $new->order = (int) $row['order'];
        $new->is_active = true;
        $new->is_published = true;
        $new->save();
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function resolveTypeFromSnapshotRow(array $row): string
    {
        $raw = $row['t'] ?? null;
        if (! is_string($raw) || $raw === '') {
            return 'text';
        }

        $schemas = config('blocks.schemas', []);

        return array_key_exists($raw, $schemas) ? $raw : 'text';
    }

    /**
     * @deprecated Usar {@see restorePublishedBlocksFromSnapshot}
     */
    public static function restorePublishedBlockOrdersFromSnapshot(Site $site, string $snapshotJson): void
    {
        self::restorePublishedBlocksFromSnapshot($site, $snapshotJson);
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
