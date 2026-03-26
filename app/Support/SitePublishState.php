<?php

namespace App\Support;

use App\Models\Block;
use App\Models\Site;

final class SitePublishState
{
    public static function snapshot(Site $site): string
    {
        /*
         * Needs: a Site instance with related blocks.
         * Does: builds canonical JSON snapshot for active blocks ordered by order/id.
         * Returns: snapshot JSON string.
         */
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
        /*
         * Needs: a Site instance with current and stored publish state.
         * Does: checks unpublished active blocks and compares current snapshot hash.
         * Returns: true when pending publish changes exist; otherwise false.
         */
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

    public static function pruneSiteBlocksToBaseline(Site $site, ?string $baselineSnapshot): void
    {
        /*
         * Needs: a Site instance and optional baseline snapshot JSON.
         * Does: prunes blocks to published baseline, handling empty/invalid snapshot safely.
         * Returns: void.
         */
        if ($baselineSnapshot === null || $baselineSnapshot === '') {
            $site->blocks()->where('is_published', false)->delete();

            return;
        }

        try {
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

    public static function restorePublishedBlocksFromSnapshot(Site $site, string $snapshotJson): void
    {
        /*
         * Needs: a Site instance and a snapshot JSON payload.
         * Does: restores published rows and recreates missing published blocks.
         * Returns: void.
         */
        try {
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

    private static function recreateBlockFromSnapshotRow(Site $site, array $row, int $blockId): void
    {
        /*
         * Needs: site, snapshot row payload, and target block id.
         * Does: creates a published active block using snapshot values.
         * Returns: void.
         */
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

    private static function resolveTypeFromSnapshotRow(array $row): string
    {
        /*
         * Needs: one snapshot row payload.
         * Does: resolves safe block type and falls back to text when invalid.
         * Returns: a valid block type string.
         */
        $raw = $row['t'] ?? null;
        if (! is_string($raw) || $raw === '') {
            return 'text';
        }

        $schemas = config('blocks.schemas', []);

        return array_key_exists($raw, $schemas) ? $raw : 'text';
    }

    public static function restorePublishedBlockOrdersFromSnapshot(Site $site, string $snapshotJson): void
    {
        /*
         * Needs: site and snapshot JSON payload.
         * Does: forwards legacy call to full snapshot restore method.
         * Returns: void.
         */
        self::restorePublishedBlocksFromSnapshot($site, $snapshotJson);
    }

    private static function normalizeProps(mixed $props): mixed
    {
        /*
         * Needs: props value that can be scalar, list, or associative array.
         * Does: recursively normalizes arrays to a stable key order.
         * Returns: normalized props preserving original structure semantics.
         */
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
