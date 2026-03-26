<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Block;
use App\Models\Site;
use App\Services\BlockSchemaRegistry;
use App\Support\SitePublishState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class BlockController extends Controller
{
    public function index(Request $request, BlockSchemaRegistry $registry): JsonResponse
    {
        /*
         * Needs: an authenticated request with an associated site.
         * Does: fetches all site blocks sorted by order and id.
         * Returns: a JSON response with serialized block data.
         */
        $site = $this->userSite($request);
        $blocks = $site->blocks()->orderBy('order')->orderBy('id')->get();

        return response()->json([
            'data' => $blocks->map(fn (Block $b) => $this->blockPayload($b))->all(),
        ]);
    }

    public function store(Request $request, BlockSchemaRegistry $registry): JsonResponse
    {
        /*
         * Needs: a valid block type from the registry and props as an array.
         * Does: validates payload, computes next order, and creates a draft block.
         * Returns: a JSON response with the created block and HTTP 201.
         */
        $site = $this->userSite($request);

        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(array_keys($registry->all()))],
            'props' => ['present', 'array'],
        ]);

        $this->validatePropsForType($registry, $validated['type'], $validated['props']);

        $nextOrder = ($site->blocks()->max('order'));
        $order = $nextOrder === null ? 0 : ((int) $nextOrder + 1);

        $block = $site->blocks()->create([
            'type' => $validated['type'],
            'props' => $validated['props'],
            'order' => $order,
            'is_active' => true,
            'is_published' => false,
        ]);

        return response()->json(['data' => $this->blockPayload($block)], 201);
    }

    public function update(Request $request, Block $block, BlockSchemaRegistry $registry): JsonResponse
    {
        /*
         * Needs: update authorization for the block and valid props input.
         * Does: validates props against block schema and updates the block.
         * Returns: a JSON response with the updated block.
         */
        $this->authorize('update', $block);

        $validated = $request->validate([
            'props' => ['required', 'array'],
        ]);

        $this->validatePropsForType($registry, $block->type, $validated['props']);

        $block->update(['props' => $validated['props']]);

        return response()->json(['data' => $this->blockPayload($block->fresh())]);
    }

    public function destroy(Request $request, Block $block): Response
    {
        /*
         * Needs: delete authorization for the target block.
         * Does: deletes the specified block.
         * Returns: an empty HTTP 204 response.
         */
        $this->authorize('delete', $block);
        $block->delete();

        return response()->noContent();
    }

    public function destroyAll(Request $request): JsonResponse
    {
        /*
         * Needs: an authenticated request with an associated site.
         * Does: deletes all site blocks and preserves/updates published baseline snapshot.
         * Returns: a JSON response with success flag and resulting published snapshot.
         */
        $site = $this->userSite($request);
        $site->refresh();
        $baselineSnapshot = $site->published_blocks_snapshot;

        $site->blocks()->delete();

        $snapshot = is_string($baselineSnapshot) && $baselineSnapshot !== ''
            ? $baselineSnapshot
            : SitePublishState::snapshot($site->fresh());

        $site->update(['published_blocks_snapshot' => $snapshot]);

        return response()->json([
            'ok' => true,
            'published_blocks_snapshot' => $snapshot,
        ]);
    }

    public function destroyUnpublished(Request $request): JsonResponse
    {
        /*
         * Needs: an authenticated request and optional baseline snapshot.
         * Does: removes unpublished changes and restores the last published state.
         * Returns: a JSON response with success flag and synchronized snapshot.
         */
        $site = $this->userSite($request);
        $site->refresh();

        $baselineSnapshot = $site->published_blocks_snapshot;

        SitePublishState::pruneSiteBlocksToBaseline($site, $baselineSnapshot);

        if (is_string($baselineSnapshot) && $baselineSnapshot !== '') {
            $site->refresh();
            SitePublishState::restorePublishedBlocksFromSnapshot($site, $baselineSnapshot);
        }

        $site->refresh();

        $snapshot = SitePublishState::snapshot($site);

        $site->update(['published_blocks_snapshot' => $snapshot]);

        return response()->json([
            'ok' => true,
            'published_blocks_snapshot' => $snapshot,
        ]);
    }

    public function reorder(Request $request): JsonResponse
    {
        /*
         * Needs: a valid blocks[{id, order}] list owned by the current site.
         * Does: verifies ownership and updates block order values.
         * Returns: a JSON response with ok=true.
         */
        $site = $this->userSite($request);

        $validated = $request->validate([
            'blocks' => ['required', 'array'],
            'blocks.*.id' => ['required', 'integer', 'exists:blocks,id'],
            'blocks.*.order' => ['required', 'integer'],
        ]);

        $blocksById = $this->loadReorderBlocksById($site, $validated['blocks']);

        foreach ($validated['blocks'] as $row) {
            $block = $blocksById[(int) $row['id']] ?? null;
            if ($block === null) continue;
            $block->update(['order' => $row['order']]);
        }

        return response()->json(['ok' => true]);
    }

    public function duplicate(Request $request, Block $block): JsonResponse
    {
        /*
         * Needs: update authorization for the block and authenticated site context.
         * Does: clones the block, appends it to the end, and marks it as unpublished.
         * Returns: a JSON response with the cloned block and HTTP 201.
         */
        $this->authorize('update', $block);
        $site = $this->userSite($request);

        $nextOrder = $site->blocks()->max('order');
        $order = $nextOrder === null ? 0 : ((int) $nextOrder + 1);

        $copy = $block->replicate();
        $copy->order = $order;
        $copy->is_published = false;
        $copy->save();

        return response()->json(['data' => $this->blockPayload($copy)], 201);
    }

    public function toggle(Request $request, Block $block): JsonResponse
    {
        /*
         * Needs: update authorization for the target block.
         * Does: toggles block visibility by flipping is_active.
         * Returns: a JSON response with the updated block.
         */
        $this->authorize('update', $block);

        $block->update(['is_active' => ! $block->is_active]);

        return response()->json(['data' => $this->blockPayload($block->fresh())]);
    }

    private function blockPayload(Block $block): array
    {
        /*
         * Needs: a Block model instance.
         * Does: maps model fields into API payload format.
         * Returns: an array with public block fields.
         */
        return [
            'id' => $block->id,
            'type' => $block->type,
            'props' => $block->props,
            'order' => $block->order,
            'is_active' => $block->is_active,
            'is_published' => $block->is_published,
        ];
    }

    private function userSite(Request $request): Site
    {
        /*
         * Needs: an authenticated user in the request.
         * Does: resolves the user's site or aborts with 404.
         * Returns: the Site instance linked to the current user.
         */
        $site = $request->user()->site;
        abort_if($site === null, 404);

        return $site;
    }

    private function loadReorderBlocksById(Site $site, array $rows): array
    {
        /*
         * Needs: current site and rows containing id/order pairs.
         * Does: loads blocks by id and ensures each belongs to the site.
         * Returns: an [id => Block] map used for reordering.
         */
        $ids = array_map(fn (array $row): int => (int) $row['id'], $rows);
        $blocks = Block::query()
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        foreach ($ids as $id) {
            $block = $blocks->get($id);
            if (! $block || (int) $block->site_id !== (int) $site->id) {
                abort(403);
            }
        }

        $map = $blocks->all();

        return $map;
    }

    private function validationRulesForSubfield(array $sub, bool $repeaterRequired): array
    {
        /*
         * Needs: subfield definition and repeater-required flag.
         * Does: builds validation rules based on subfield type.
         * Returns: an array of Laravel validation rules.
         */
        $subRequired = $repeaterRequired;

        return match ($sub['type'] ?? 'text') {
            'select' => $subRequired
                ? ['required', Rule::in($sub['options'] ?? [])]
                : ['sometimes', 'nullable', Rule::in($sub['options'] ?? [])],
            'url' => $subRequired
                ? ['required', 'url']
                : ['sometimes', 'nullable', 'url'],
            'text', 'textarea' => $subRequired
                ? ['required', 'string']
                : ['sometimes', 'nullable', 'string'],
            default => $subRequired
                ? ['required', 'string']
                : ['sometimes', 'nullable', 'string'],
        };
    }

    private function validatePropsForType(BlockSchemaRegistry $registry, string $type, array $props): void
    {
        /*
         * Needs: schema registry, block type, and incoming props data.
         * Does: builds dynamic schema rules and validates props.
         * Returns: void; throws validation errors when data is invalid.
         */
        $schema = $registry->get($type);
        $rules = [];

        foreach ($schema['fields'] as $field) {
            $key = $field['key'];
            $path = "props.{$key}";
            $required = ! empty($field['required']);

            switch ($field['type']) {
                case 'text':
                    $rules[$path] = $required
                        ? ['required', 'string']
                        : ['sometimes', 'nullable', 'string'];
                    break;
                case 'textarea':
                    $rules[$path] = $required
                        ? ['required', 'string']
                        : ['sometimes', 'nullable', 'string'];
                    break;
                case 'url':
                    $rules[$path] = $required
                        ? ['required', 'url']
                        : ['sometimes', 'nullable', 'url'];
                    break;
                case 'color':
                    $rules[$path] = $required
                        ? ['required', 'string']
                        : ['sometimes', 'nullable', 'string'];
                    break;
                case 'select':
                    $options = $field['options'] ?? [];
                    $rules[$path] = $required
                        ? ['required', Rule::in($options)]
                        : ['sometimes', 'nullable', Rule::in($options)];
                    break;
                case 'repeater':
                    $rules[$path] = $required
                        ? ['required', 'array', 'min:1']
                        : ['sometimes', 'nullable', 'array'];
                    foreach ($field['subfields'] ?? [] as $sub) {
                        $sk = "{$path}.*.".$sub['key'];
                        $subRules = $this->validationRulesForSubfield($sub, $required);
                        $rules[$sk] = $subRules;
                    }
                    break;
                default:
                    $rules[$path] = ['sometimes', 'nullable'];
            }
        }

        Validator::make(['props' => $props], $rules)->validate();
    }
}
