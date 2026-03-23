import { describe, it, expect } from 'vitest'
import {
    canonicalSnapshotString,
    currentMatchesPublishedBaseline,
    normalizeSnapshotString,
    orderFingerprintFromEditorBlocks,
    orderMatchesPublishedBaseline,
    parsePublishedBlocksSnapshot,
    toSnapshotJsonString,
} from '@/utils/publishedBaseline'

describe('publishedBaseline', () => {
    it('parsePublishedBlocksSnapshot: null/empty → null', () => {
        expect(parsePublishedBlocksSnapshot(null)).toBeNull()
        expect(parsePublishedBlocksSnapshot(undefined)).toBeNull()
        expect(parsePublishedBlocksSnapshot('')).toBeNull()
    })

    it('parsePublishedBlocksSnapshot: [] → array vacío', () => {
        expect(parsePublishedBlocksSnapshot('[]')).toEqual([])
    })

    it('parsePublishedBlocksSnapshot acepta array ya parseado (Inertia)', () => {
        const rows = [{ id: 1, order: 0, p: {}, pub: true }]
        expect(parsePublishedBlocksSnapshot(rows)).toEqual(rows)
    })

    it('toSnapshotJsonString serializa arrays', () => {
        expect(toSnapshotJsonString([{ id: 1, order: 0, p: {}, pub: true }])).toBe(
            '[{"id":1,"order":0,"p":{},"pub":true}]',
        )
    })

    it('normalizeSnapshotString iguala espacios distintos', () => {
        const a = '[{"id":1,"order":0,"p":{},"pub":true}]'
        const b = '[ { "id" : 1, "order" : 0, "p" : {}, "pub" : true } ]'
        expect(normalizeSnapshotString(a)).toBe(normalizeSnapshotString(b))
    })

    it('currentMatchesPublishedBaseline: sin línea base → coincide (no compara estructura)', () => {
        expect(currentMatchesPublishedBaseline('[]', null)).toBe(true)
        expect(currentMatchesPublishedBaseline('[{"x":1}]', undefined)).toBe(true)
    })

    it('currentMatchesPublishedBaseline: igual canónico → true', () => {
        const row = '{"id":1,"order":0,"p":{},"pub":true}'
        expect(currentMatchesPublishedBaseline(`[${row}]`, `[${row}]`)).toBe(true)
    })

    it('currentMatchesPublishedBaseline: distinto → false', () => {
        const a = '[{"id":1,"order":0,"p":{},"pub":true}]'
        const b = '[{"id":2,"order":0,"p":{},"pub":true}]'
        expect(currentMatchesPublishedBaseline(a, b)).toBe(false)
    })

    it('currentMatchesPublishedBaseline: string vs array equivalente', () => {
        const row = { id: 1, order: 0, p: {}, pub: true }
        const str = canonicalSnapshotString([row])
        expect(currentMatchesPublishedBaseline(str, [row])).toBe(true)
    })

    it('orderMatchesPublishedBaseline detecta intercambio de orden', () => {
        const baseline = [
            { id: 1, order: 0, p: {}, pub: true },
            { id: 2, order: 1, p: {}, pub: true },
        ]
        const editorOk = [
            { id: 1, is_active: true, order: 0, is_published: true },
            { id: 2, is_active: true, order: 1, is_published: true },
        ]
        const editorSwapped = [
            { id: 1, is_active: true, order: 1, is_published: true },
            { id: 2, is_active: true, order: 0, is_published: true },
        ]
        expect(orderMatchesPublishedBaseline(editorOk, JSON.stringify(baseline))).toBe(true)
        expect(orderMatchesPublishedBaseline(editorSwapped, JSON.stringify(baseline))).toBe(false)
        expect(orderFingerprintFromEditorBlocks(editorSwapped)).toBe('2:0|1:1')
    })
})
