import { describe, it, expect } from 'vitest'

describe('useBlocks (scaffold)', () => {
  it('module can be imported', async () => {
    const mod = await import('../../composables/useBlocks.js')
    expect(typeof mod.useBlocks).toBe('function')
  })
})
