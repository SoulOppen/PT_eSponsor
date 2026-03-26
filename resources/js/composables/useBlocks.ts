import { computed, ref, type Ref } from 'vue';

type BlockProps = Record<string, unknown>;

export interface SiteBlock {
    id: number;
    type: string;
    props: BlockProps;
    order: number;
    is_active?: boolean;
    is_published?: boolean;
}

type ReorderRow = { id: number; order: number };
type ApiBody = Record<string, unknown>;

function readXsrfToken(): string {
    if (typeof document === 'undefined') return '';
    const m = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
    return m ? decodeURIComponent(m[1]) : '';
}

async function apiJson(url: string, options: RequestInit = {}): Promise<Response> {
    const headers = new Headers(options.headers ?? {});
    headers.set('Accept', 'application/json');
    headers.set('Content-Type', 'application/json');
    const token = readXsrfToken();
    if (token) headers.set('X-XSRF-TOKEN', token);
    return fetch(url, { credentials: 'same-origin', ...options, headers });
}

/**
 * Build a readable API error message from status code and optional JSON body.
 */
async function readApiError(res: Response, fallback: string): Promise<string> {
    if (res.status === 419) return 'Tu sesion expiro. Recarga la pagina e inicia sesion de nuevo.';
    if (res.status === 401) return 'Debes iniciar sesion para continuar.';
    if (res.status === 403) return 'No tienes permisos para esta accion.';

    try {
        const json = (await res.json()) as { errors?: Record<string, unknown>; message?: unknown };
        if (json?.errors && typeof json.errors === 'object') {
            const firstError = Object.values(json.errors)[0];
            if (Array.isArray(firstError) && firstError[0]) return String(firstError[0]);
        }
        if (json?.message) return String(json.message);
    } catch {
        // Ignore malformed/non-JSON responses and keep fallback.
    }

    return fallback;
}

export function useBlocks(initialBlocks: SiteBlock[] = []): {
    blocks: Ref<SiteBlock[]>;
    sortedBlocks: Readonly<Ref<SiteBlock[]>>;
    addBlock: (type: string, props: BlockProps) => Promise<void>;
    removeBlock: (id: number) => Promise<void>;
    toggleBlock: (id: number) => Promise<void>;
    duplicateBlock: (id: number) => Promise<void>;
    updateBlock: (id: number, props: BlockProps) => Promise<void>;
    reorderBlocks: (orderedIds: number[]) => Promise<void>;
    destroyAllBlocks: () => Promise<ApiBody>;
    pruneUnpublishedBlocks: () => Promise<ApiBody>;
} {
    const blocks = ref(initialBlocks.map((b) => ({ ...b })));

    const sortedBlocks = computed(() =>
        [...blocks.value].sort((a, b) => (a.order ?? 0) - (b.order ?? 0)),
    );

    async function addBlock(type: string, props: BlockProps): Promise<void> {
        const res = await apiJson('/api/blocks', {
            method: 'POST',
            body: JSON.stringify({ type, props }),
        });
        if (!res.ok) {
            throw new Error(await readApiError(res, 'No se pudo crear el bloque.'));
        }
        const json = (await res.json()) as { data?: SiteBlock };
        if (json.data) blocks.value.push(json.data);
    }

    async function removeBlock(id: number): Promise<void> {
        const res = await apiJson(`/api/blocks/${id}`, { method: 'DELETE' });
        if (!res.ok) {
            throw new Error(await readApiError(res, 'No se pudo eliminar el bloque.'));
        }
        blocks.value = blocks.value.filter((b) => b.id !== id);
    }

    async function toggleBlock(id: number): Promise<void> {
        const res = await apiJson(`/api/blocks/${id}/toggle`, {
            method: 'PATCH',
            body: '{}',
        });
        if (!res.ok) {
            throw new Error(await readApiError(res, 'No se pudo cambiar el estado del bloque.'));
        }
        const json = (await res.json()) as { data?: SiteBlock };
        const idx = blocks.value.findIndex((b) => b.id === id);
        if (idx !== -1 && json.data) blocks.value[idx] = { ...blocks.value[idx], ...json.data };
    }

    async function duplicateBlock(id: number): Promise<void> {
        const res = await apiJson(`/api/blocks/${id}/duplicate`, {
            method: 'POST',
            body: '{}',
        });
        if (!res.ok) {
            throw new Error(await readApiError(res, 'No se pudo duplicar el bloque.'));
        }
        const json = (await res.json()) as { data?: SiteBlock };
        if (json.data) blocks.value.push(json.data);
    }

    async function updateBlock(id: number, props: BlockProps): Promise<void> {
        const res = await apiJson(`/api/blocks/${id}`, {
            method: 'PUT',
            body: JSON.stringify({ props }),
        });
        if (!res.ok) {
            throw new Error(await readApiError(res, 'No se pudo guardar el bloque.'));
        }
        const json = (await res.json()) as { data?: SiteBlock };
        const idx = blocks.value.findIndex((b) => b.id === id);
        if (idx !== -1 && json.data) blocks.value[idx] = { ...blocks.value[idx], ...json.data };
    }

    async function reorderBlocks(orderedIds: number[]): Promise<void> {
        const rows: ReorderRow[] = orderedIds.map((id, order) => ({ id: Number(id), order }));
        const orderById: Record<number, number> = Object.fromEntries(
            orderedIds.map((id, i) => [Number(id), i]),
        );
        const prev = blocks.value.map((b) => ({
            ...b,
            props: { ...(b.props || {}) },
        }));

        blocks.value = blocks.value.map((b) => ({
            ...b,
            order: orderById[Number(b.id)] ?? b.order,
        }));

        const res = await apiJson('/api/blocks/reorder', {
            method: 'POST',
            body: JSON.stringify({ blocks: rows }),
        });
        if (!res.ok) {
            blocks.value = prev;
            throw new Error(await readApiError(res, 'No se pudo reordenar los bloques.'));
        }
    }

    async function destroyAllBlocks(): Promise<ApiBody> {
        const res = await apiJson('/api/blocks/all', { method: 'DELETE' });
        if (!res.ok) {
            throw new Error(await readApiError(res, 'No se pudieron eliminar los bloques.'));
        }
        blocks.value = [];
        try {
            return (await res.json()) as ApiBody;
        } catch {
            return {};
        }
    }

    async function pruneUnpublishedBlocks(): Promise<ApiBody> {
        const res = await apiJson('/api/blocks/unpublished', { method: 'DELETE' });
        if (!res.ok) {
            throw new Error(await readApiError(res, 'No se pudieron quitar los borradores.'));
        }
        let body: ApiBody = {};
        try {
            body = (await res.json()) as ApiBody;
        } catch {
            body = {};
        }
        const listRes = await apiJson('/api/blocks');
        if (listRes.ok) {
            try {
                const listJson = (await listRes.json()) as { data?: SiteBlock[] };
                if (Array.isArray(listJson.data)) {
                    blocks.value = listJson.data.map((b) => ({
                        ...b,
                        props: { ...(b.props || {}) },
                    }));
                }
            } catch {
                blocks.value = blocks.value.filter((b) => b.is_published);
            }
        } else {
            blocks.value = blocks.value.filter((b) => b.is_published);
        }
        return body;
    }

    return {
        blocks,
        sortedBlocks,
        addBlock,
        removeBlock,
        toggleBlock,
        duplicateBlock,
        updateBlock,
        reorderBlocks,
        destroyAllBlocks,
        pruneUnpublishedBlocks,
    };
}
