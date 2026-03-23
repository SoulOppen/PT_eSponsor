/**
 * Debe coincidir con App\Support\SitePublishState::snapshot (PHP) para el dashboard.
 */
export function normalizeProps(props) {
    if (props === null || typeof props !== 'object') {
        return props
    }
    if (Array.isArray(props)) {
        return props.map((item) =>
            typeof item === 'object' && item !== null ? normalizeProps(item) : item,
        )
    }
    const out = {}
    for (const k of Object.keys(props).sort()) {
        const v = props[k]
        out[k] = typeof v === 'object' && v !== null ? normalizeProps(v) : v
    }
    return out
}

/**
 * @param {Array<{ id: number, order?: number, props?: object, is_published?: boolean, is_active?: boolean }>} blocks
 */
export function siteBlocksSnapshot(blocks) {
    const active = [...blocks]
        .filter((b) => b.is_active)
        .sort((a, b) => (a.order ?? 0) - (b.order ?? 0) || a.id - b.id)

    const payload = active.map((b) => ({
        id: Number(b.id),
        order: Number(b.order ?? 0),
        t: typeof b.type === 'string' && b.type !== '' ? b.type : 'text',
        p: normalizeProps(b.props && typeof b.props === 'object' ? b.props : {}),
        pub: !!b.is_published,
    }))

    return JSON.stringify(payload)
}
