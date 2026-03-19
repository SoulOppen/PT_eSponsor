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
})
