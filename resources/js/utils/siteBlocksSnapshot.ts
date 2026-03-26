/**
 * Must match App\Support\SitePublishState::snapshot (PHP) for dashboard behavior.
 */

export type SnapshotRow = {
    id: number;
    order?: number;
    type?: string;
    props?: Record<string, unknown> | unknown[];
    is_published?: boolean;
    is_active?: boolean;
};

export function normalizeProps(props: unknown): unknown {
    if (props === null || typeof props !== 'object') {
        return props;
    }
    if (Array.isArray(props)) {
        return props.map((item) =>
            typeof item === 'object' && item !== null ? normalizeProps(item) : item,
        );
    }

    const source = props as Record<string, unknown>;
    const out: Record<string, unknown> = {};
    for (const k of Object.keys(source).sort()) {
        const v = source[k];
        out[k] = typeof v === 'object' && v !== null ? normalizeProps(v) : v;
    }
    return out;
}

export function siteBlocksSnapshot(blocks: SnapshotRow[]): string {
    const active = [...blocks]
        .filter((b) => b.is_active)
        .sort((a, b) => (a.order ?? 0) - (b.order ?? 0) || a.id - b.id);

    const payload = active.map((b) => ({
        id: Number(b.id),
        order: Number(b.order ?? 0),
        t: typeof b.type === 'string' && b.type !== '' ? b.type : 'text',
        p: normalizeProps(b.props && typeof b.props === 'object' ? b.props : {}),
        pub: !!b.is_published,
    }));

    return JSON.stringify(payload);
}
