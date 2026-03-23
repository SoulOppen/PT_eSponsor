import { normalizeProps } from './siteBlocksSnapshot'

/**
 * Convierte lo que venga del servidor / Inertia en JSON string estable.
 * Inertia a veces entrega `published_blocks_snapshot` como string; si en el futuro llegara como array, también sirve.
 *
 * @param {unknown} snapshot
 * @returns {string|null}
 */
export function toSnapshotJsonString(snapshot) {
    if (snapshot === null || snapshot === undefined) {
        return null
    }
    if (typeof snapshot === 'string') {
        const t = snapshot.trim()
        return t === '' ? null : snapshot
    }
    if (typeof snapshot === 'object') {
        try {
            return JSON.stringify(snapshot)
        } catch {
            return null
        }
    }
    return null
}

/**
 * @param {unknown} snapshot
 * @returns {Array<{ id: number, order: number, t?: string, p: object, pub: boolean }>|null}
 */
export function parsePublishedBlocksSnapshot(snapshot) {
    if (snapshot === null || snapshot === undefined || snapshot === '') {
        return null
    }
    if (Array.isArray(snapshot)) {
        return snapshot
    }
    const str = toSnapshotJsonString(snapshot)
    if (str === null) {
        return null
    }
    try {
        const rows = JSON.parse(str)
        return Array.isArray(rows) ? rows : null
    } catch {
        return null
    }
}

/**
 * Misma forma canónica que `siteBlocksSnapshot()` y `SitePublishState::snapshot` (PHP):
 * orden por order+id, `p` con claves ordenadas, tipos fijos.
 *
 * @param {unknown} snapshot string JSON o ya parseado
 * @returns {string|null}
 */
export function canonicalSnapshotString(snapshot) {
    const str = toSnapshotJsonString(snapshot)
    if (str === null) {
        return null
    }
    try {
        const rows = JSON.parse(str)
        if (!Array.isArray(rows)) {
            return null
        }
        const sorted = [...rows].sort(
            (a, b) => (Number(a.order ?? 0) - Number(b.order ?? 0)) || (Number(a.id) - Number(b.id)),
        )
        const payload = sorted.map((row) => ({
            id: Number(row.id),
            order: Number(row.order ?? 0),
            t: typeof row.t === 'string' && row.t !== '' ? row.t : 'text',
            p: normalizeProps(row.p && typeof row.p === 'object' ? row.p : {}),
            pub: !!row.pub,
        }))
        return JSON.stringify(payload)
    } catch {
        return null
    }
}

/** @deprecated Usar canonicalSnapshotString; se mantiene por tests. */
export function normalizeSnapshotString(s) {
    return canonicalSnapshotString(s)
}

/**
 * ¿El snapshot actual del editor coincide con `published_blocks_snapshot`?
 * Sin línea base guardada (null/vacío) no hay desviación estructural que comparar.
 */
export function currentMatchesPublishedBaseline(currentSnapshot, baselineSnapshot) {
    const b = canonicalSnapshotString(baselineSnapshot)
    if (b === null) {
        return true
    }
    const c = canonicalSnapshotString(currentSnapshot)
    if (c === null) {
        return false
    }
    return c === b
}

/**
 * Huella `id:order` de bloques activos en el editor (subir/bajar, arrastre).
 * @param {Array<{ id: number, order?: number, is_active?: boolean }>} blocks
 */
export function orderFingerprintFromEditorBlocks(blocks) {
    const sorted = [...blocks]
        .filter((b) => b && b.is_active)
        .sort(
            (a, b) =>
                (Number(a.order ?? 0) - Number(b.order ?? 0)) || (Number(a.id) - Number(b.id)),
        )
    return sorted.map((b) => `${Number(b.id)}:${Number(b.order ?? 0)}`).join('|')
}

/**
 * Huella `id:order` desde filas del snapshot publicado (excluye `pub: false`).
 * @param {Array<{ id: number, order?: number, pub?: boolean }>|null} rows
 */
export function orderFingerprintFromBaselineRows(rows) {
    if (rows === null || rows === undefined) {
        return null
    }
    const sorted = [...rows]
        .filter((r) => r != null && r.id != null && r.pub !== false)
        .sort(
            (a, b) =>
                (Number(a.order ?? 0) - Number(b.order ?? 0)) || (Number(a.id) - Number(b.id)),
        )
    return sorted.map((r) => `${Number(r.id)}:${Number(r.order ?? 0)}`).join('|')
}

export function orderFingerprintFromBaselineSnapshot(snapshot) {
    const rows = parsePublishedBlocksSnapshot(snapshot)
    if (rows === null) {
        return null
    }
    return orderFingerprintFromBaselineRows(rows)
}

/**
 * ¿El orden (y presencia) de bloques activos coincide con la línea base publicada?
 * Sin baseline guardada → true (no se compara).
 */
export function orderMatchesPublishedBaseline(editorBlocks, baselineSnapshot) {
    const fpB = orderFingerprintFromBaselineSnapshot(baselineSnapshot)
    if (fpB === null) {
        return true
    }
    const fpC = orderFingerprintFromEditorBlocks(editorBlocks)
    return fpC === fpB
}
