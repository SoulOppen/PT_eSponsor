import { ref, computed } from 'vue'

function readXsrfToken() {
    if (typeof document === 'undefined') return ''
    const m = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/)
    return m ? decodeURIComponent(m[1]) : ''
}

async function apiJson(url, options = {}) {
    const headers = {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        ...options.headers,
    }
    const token = readXsrfToken()
    if (token) headers['X-XSRF-TOKEN'] = token
    return fetch(url, { credentials: 'same-origin', ...options, headers })
}

export function useBlocks(initialBlocks = []) {
    const blocks = ref(initialBlocks.map((b) => ({ ...b })))

    const sortedBlocks = computed(() =>
        [...blocks.value].sort((a, b) => (a.order ?? 0) - (b.order ?? 0)),
    )

    async function addBlock(type, props) {
        const res = await apiJson('/api/blocks', {
            method: 'POST',
            body: JSON.stringify({ type, props }),
        })
        if (!res.ok) return
        const json = await res.json()
        if (json.data) blocks.value.push(json.data)
    }

    async function removeBlock(id) {
        const res = await apiJson(`/api/blocks/${id}`, { method: 'DELETE' })
        if (!res.ok) return
        blocks.value = blocks.value.filter((b) => b.id !== id)
    }

    async function toggleBlock(id) {
        const res = await apiJson(`/api/blocks/${id}/toggle`, {
            method: 'PATCH',
            body: '{}',
        })
        if (!res.ok) return
        const json = await res.json()
        const idx = blocks.value.findIndex((b) => b.id === id)
        if (idx !== -1 && json.data) blocks.value[idx] = { ...blocks.value[idx], ...json.data }
    }

    async function duplicateBlock(id) {
        const res = await apiJson(`/api/blocks/${id}/duplicate`, {
            method: 'POST',
            body: '{}',
        })
        if (!res.ok) return
        const json = await res.json()
        if (json.data) blocks.value.push(json.data)
    }

    async function updateBlock(id, props) {
        const res = await apiJson(`/api/blocks/${id}`, {
            method: 'PUT',
            body: JSON.stringify({ props }),
        })
        if (!res.ok) return
        const json = await res.json()
        const idx = blocks.value.findIndex((b) => b.id === id)
        if (idx !== -1 && json.data) blocks.value[idx] = { ...blocks.value[idx], ...json.data }
    }

    /**
     * @param {number[]} orderedIds Block ids in desired order (index = order)
     */
    async function reorderBlocks(orderedIds) {
        const rows = orderedIds.map((id, order) => ({ id: Number(id), order }))
        const res = await apiJson('/api/blocks/reorder', {
            method: 'POST',
            body: JSON.stringify({ blocks: rows }),
        })
        if (!res.ok) return
        const orderById = Object.fromEntries(orderedIds.map((id, i) => [Number(id), i]))
        blocks.value = blocks.value.map((b) => ({
            ...b,
            order: orderById[b.id] ?? b.order,
        }))
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
    }
}
