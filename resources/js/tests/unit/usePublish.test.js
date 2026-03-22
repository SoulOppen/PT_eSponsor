import { describe, it, expect, vi, beforeEach } from 'vitest'
import { usePublish } from '../../composables/usePublish.js'

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

    it('resetDirty sets isDirty to false', () => {
        const { isDirty, markDirty, resetDirty } = usePublish()
        markDirty()
        resetDirty()
        expect(isDirty.value).toBe(false)
    })

    it('publish calls the API and resets isDirty', async () => {
        fetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) })
        const { isDirty, markDirty, publish } = usePublish()
        markDirty()
        await publish()
        expect(isDirty.value).toBe(false)
        expect(fetch).toHaveBeenCalledWith('/api/site/publish', expect.any(Object))
    })

    it('publish does not reset isDirty when response is not ok', async () => {
        fetch.mockResolvedValueOnce({ ok: false, status: 500 })
        const { isDirty, markDirty, publish } = usePublish()
        markDirty()
        await expect(publish()).rejects.toThrow('No se pudo publicar.')
        expect(isDirty.value).toBe(true)
    })
})
