import { describe, it, expect, vi, beforeEach } from 'vitest'
import { useBlocks } from '../../composables/useBlocks.js'

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

    it('addBlock throws validation error message', async () => {
        fetch.mockResolvedValueOnce({
            ok: false,
            status: 422,
            json: async () => ({ errors: { 'props.content': ['Contenido requerido'] } }),
        })
        const { addBlock } = useBlocks([])
        await expect(addBlock('text', {})).rejects.toThrow('Contenido requerido')
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
        const { blocks, toggleBlock } = useBlocks([
            { id: 1, is_active: true, type: 'text', props: {}, order: 0 },
        ])
        await toggleBlock(1)
        expect(blocks.value[0].is_active).toBe(false)
    })

    it('reorderBlocks updates order via API', async () => {
        fetch.mockResolvedValueOnce({ ok: true, json: async () => ({ ok: true }) })
        const { blocks, reorderBlocks } = useBlocks([
            { id: 1, type: 'text', props: {}, order: 0 },
            { id: 2, type: 'text', props: {}, order: 1 },
        ])
        await reorderBlocks([2, 1])
        expect(fetch).toHaveBeenCalledWith(
            '/api/blocks/reorder',
            expect.objectContaining({
                method: 'POST',
            }),
        )
        expect(blocks.value.find((b) => b.id === 2).order).toBe(0)
        expect(blocks.value.find((b) => b.id === 1).order).toBe(1)
    })

    it('destroyAllBlocks clears local state on success', async () => {
        fetch.mockResolvedValueOnce({ ok: true, json: async () => ({ ok: true }) })
        const { blocks, destroyAllBlocks } = useBlocks([
            { id: 1, type: 'text', props: {}, order: 0 },
        ])
        await destroyAllBlocks()
        expect(blocks.value).toHaveLength(0)
        expect(fetch).toHaveBeenCalledWith('/api/blocks/all', expect.objectContaining({ method: 'DELETE' }))
    })

    it('pruneUnpublishedBlocks refetches blocks after prune', async () => {
        fetch
            .mockResolvedValueOnce({
                ok: true,
                json: async () => ({ ok: true, published_blocks_snapshot: '[]' }),
            })
            .mockResolvedValueOnce({
                ok: true,
                json: async () => ({
                    data: [
                        { id: 2, type: 'text', props: { content: 'x' }, order: 0, is_published: true, is_active: true },
                    ],
                }),
            })
        const { blocks, pruneUnpublishedBlocks } = useBlocks([
            { id: 1, type: 'text', props: {}, order: 0, is_published: false },
            { id: 2, type: 'text', props: {}, order: 1, is_published: true },
        ])
        await pruneUnpublishedBlocks()
        expect(blocks.value).toHaveLength(1)
        expect(blocks.value[0].id).toBe(2)
        expect(blocks.value[0].order).toBe(0)
        expect(fetch).toHaveBeenCalledWith('/api/blocks', expect.any(Object))
    })
})
