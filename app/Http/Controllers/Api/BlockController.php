<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Block;
use App\Models\Site;
use App\Services\BlockSchemaRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BlockController extends Controller
{
    public function index(Request $request, BlockSchemaRegistry $registry): JsonResponse
    {
        $site = $this->userSite($request);
        $blocks = $site->blocks()->orderBy('order')->orderBy('id')->get();

        return response()->json([
            'data' => $blocks->map(fn (Block $b) => $this->blockPayload($b))->all(),
        ]);
    }

    public function store(Request $request, BlockSchemaRegistry $registry): JsonResponse
    {
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
        $site = $this->userSite($request);
        $this->authorizeBlock($site, $block);

        $validated = $request->validate([
            'props' => ['required', 'array'],
        ]);

        $this->validatePropsForType($registry, $block->type, $validated['props']);

        $block->update(['props' => $validated['props']]);

        return response()->json(['data' => $this->blockPayload($block->fresh())]);
    }

    public function destroy(Request $request, Block $block): Response
    {
        $site = $this->userSite($request);
        $this->authorizeBlock($site, $block);
        $block->delete();

        return response()->noContent();
    }

    public function duplicate(Request $request, Block $block): JsonResponse
    {
        $site = $this->userSite($request);
        $this->authorizeBlock($site, $block);

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
        $site = $this->userSite($request);
        $this->authorizeBlock($site, $block);

        $block->update(['is_active' => ! $block->is_active]);

        return response()->json(['data' => $this->blockPayload($block->fresh())]);
    }

    /**
     * @return array<string, mixed>
     */
    private function blockPayload(Block $block): array
    {
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
        $site = $request->user()->site;
        abort_if($site === null, 404);

        return $site;
    }

    private function authorizeBlock(Site $site, Block $block): void
    {
        abort_if((int) $block->site_id !== (int) $site->id, 403);
    }

    /**
     * @param  array<string, mixed>  $props
     */
    private function validatePropsForType(BlockSchemaRegistry $registry, string $type, array $props): void
    {
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
                        ? ['required', 'array']
                        : ['sometimes', 'nullable', 'array'];
                    break;
                default:
                    $rules[$path] = ['sometimes', 'nullable'];
            }
        }

        Validator::make(['props' => $props], $rules)->validate();
    }
}
