import { normalizeProps } from './siteBlocksSnapshot';

export type BaselineRow = {
    id: number;
    order: number;
    t?: string;
    p: Record<string, unknown>;
    pub: boolean;
};

type BaselineRowLoose = {
    id?: unknown;
    order?: unknown;
    t?: unknown;
    p?: unknown;
    pub?: unknown;
};

export function toSnapshotJsonString(snapshot: unknown): string | null {
    if (snapshot === null || snapshot === undefined) {
        return null;
    }
    if (typeof snapshot === 'string') {
        const t = snapshot.trim();
        return t === '' ? null : snapshot;
    }
    if (typeof snapshot === 'object') {
        try {
            return JSON.stringify(snapshot);
        } catch {
            return null;
        }
    }
    return null;
}

export function parsePublishedBlocksSnapshot(snapshot: unknown): BaselineRowLoose[] | null {
    if (snapshot === null || snapshot === undefined || snapshot === '') {
        return null;
    }
    if (Array.isArray(snapshot)) {
        return snapshot as BaselineRowLoose[];
    }
    const str = toSnapshotJsonString(snapshot);
    if (str === null) {
        return null;
    }
    try {
        const rows = JSON.parse(str) as unknown;
        return Array.isArray(rows) ? (rows as BaselineRowLoose[]) : null;
    } catch {
        return null;
    }
}

export function canonicalSnapshotString(snapshot: unknown): string | null {
    const str = toSnapshotJsonString(snapshot);
    if (str === null) {
        return null;
    }
    try {
        const rows = JSON.parse(str) as unknown;
        if (!Array.isArray(rows)) {
            return null;
        }
        const sorted = [...rows as BaselineRowLoose[]].sort(
            (a, b) => (Number(a.order ?? 0) - Number(b.order ?? 0)) || (Number(a.id) - Number(b.id)),
        );
        const payload: BaselineRow[] = sorted.map((row) => ({
            id: Number(row.id),
            order: Number(row.order ?? 0),
            t: typeof row.t === 'string' && row.t !== '' ? row.t : 'text',
            p: normalizeProps(row.p && typeof row.p === 'object' ? row.p : {}) as Record<string, unknown>,
            pub: !!row.pub,
        }));
        return JSON.stringify(payload);
    } catch {
        return null;
    }
}

/** @deprecated Keep for backwards compatibility in tests. */
export function normalizeSnapshotString(s: unknown): string | null {
    return canonicalSnapshotString(s);
}

export function currentMatchesPublishedBaseline(currentSnapshot: unknown, baselineSnapshot: unknown): boolean {
    const b = canonicalSnapshotString(baselineSnapshot);
    if (b === null) {
        return true;
    }
    const c = canonicalSnapshotString(currentSnapshot);
    if (c === null) {
        return false;
    }
    return c === b;
}

export function orderFingerprintFromEditorBlocks(
    blocks: Array<{ id: number; order?: number; is_active?: boolean }>,
): string {
    const sorted = [...blocks]
        .filter((b) => b && b.is_active)
        .sort(
            (a, b) =>
                (Number(a.order ?? 0) - Number(b.order ?? 0)) || (Number(a.id) - Number(b.id)),
        );
    return sorted.map((b) => `${Number(b.id)}:${Number(b.order ?? 0)}`).join('|');
}

export function orderFingerprintFromBaselineRows(
    rows: Array<{ id?: number; order?: number; pub?: boolean }> | null,
): string | null {
    if (rows === null || rows === undefined) {
        return null;
    }
    const sorted = [...rows]
        .filter((r) => r != null && r.id != null && r.pub !== false)
        .sort(
            (a, b) =>
                (Number(a.order ?? 0) - Number(b.order ?? 0)) || (Number(a.id) - Number(b.id)),
        );
    return sorted.map((r) => `${Number(r.id)}:${Number(r.order ?? 0)}`).join('|');
}

export function orderFingerprintFromBaselineSnapshot(snapshot: unknown): string | null {
    const rows = parsePublishedBlocksSnapshot(snapshot);
    if (rows === null) {
        return null;
    }
    return orderFingerprintFromBaselineRows(rows as Array<{ id?: number; order?: number; pub?: boolean }>);
}

export function orderMatchesPublishedBaseline(editorBlocks: Array<{ id: number; order?: number; is_active?: boolean }>, baselineSnapshot: unknown): boolean {
    const fpB = orderFingerprintFromBaselineSnapshot(baselineSnapshot);
    if (fpB === null) {
        return true;
    }
    const fpC = orderFingerprintFromEditorBlocks(editorBlocks);
    return fpC === fpB;
}
