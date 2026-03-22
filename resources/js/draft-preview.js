/**
 * Barra de publicar + reordenación por arrastre en /draft/@slug (solo dueño).
 * El arrastre solo se inicia desde el asa (.draft-block-wrap__handle), no desde el contenido
 * (imágenes, iframes de música/vídeo, etc.).
 */

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

function setMessage(el, text, isError) {
    if (!el) return
    el.textContent = text
    el.style.color = isError ? '#b91c1c' : '#166534'
}

function getOrderedIds(container) {
    return Array.from(container.querySelectorAll('[data-block-id]')).map((el) => Number(el.dataset.blockId))
}

function init() {
    const toolbar = document.getElementById('draft-edit-toolbar')
    const container = document.getElementById('draft-blocks')
    if (!toolbar || !container) return

    const btnPublish = document.getElementById('draft-edit-publish')
    const msgEl = document.getElementById('draft-edit-message')
    const publicUrl = toolbar.dataset.publicUrl || '/'
    const canPublish = toolbar.dataset.canPublish === '1'

    function setPublishDisabled(disabled) {
        if (!btnPublish) return
        btnPublish.disabled = disabled || !canPublish
    }

    btnPublish?.addEventListener('click', async () => {
        if (!canPublish || btnPublish.disabled) return
        btnPublish.disabled = true
        setMessage(msgEl, '')
        try {
            const res = await apiJson('/api/site/publish', { method: 'POST', body: '{}' })
            if (!res.ok) {
                if (res.status === 419) throw new Error('Sesión expirada. Recarga la página.')
                if (res.status === 401) throw new Error('Debes iniciar sesión.')
                if (res.status === 403) throw new Error('No tienes permiso para publicar.')
                throw new Error('No se pudo publicar.')
            }
            window.location.href = publicUrl
        } catch (e) {
            setMessage(msgEl, e?.message || 'Error al publicar.', true)
        } finally {
            setPublishDisabled(false)
        }
    })

    let draggingId = null
    const wraps = container.querySelectorAll('.draft-block-wrap[data-block-id]')
    const handles = container.querySelectorAll('.draft-block-wrap__handle')

    handles.forEach((handle) => {
        handle.addEventListener('dragstart', (e) => {
            const wrap = handle.closest('[data-block-id]')
            if (!wrap) return
            draggingId = Number(wrap.dataset.blockId)
            e.dataTransfer.effectAllowed = 'move'
            e.dataTransfer.setData('text/plain', String(draggingId))
            wrap.classList.add('draft-block--dragging')
        })

        handle.addEventListener('dragend', () => {
            const wrap = handle.closest('[data-block-id]')
            draggingId = null
            if (wrap) wrap.classList.remove('draft-block--dragging')
            wraps.forEach((w) => w.classList.remove('draft-block--over'))
        })
    })

    wraps.forEach((el) => {
        el.addEventListener('dragover', (e) => {
            if (draggingId == null) return
            const targetId = Number(el.dataset.blockId)
            if (targetId === draggingId) return
            e.preventDefault()
            wraps.forEach((w) => w.classList.remove('draft-block--over'))
            el.classList.add('draft-block--over')
        })

        el.addEventListener('dragleave', () => {
            el.classList.remove('draft-block--over')
        })

        el.addEventListener('drop', async (e) => {
            e.preventDefault()
            const sourceId = Number(e.dataTransfer.getData('text/plain') || draggingId)
            const targetId = Number(el.dataset.blockId)
            wraps.forEach((w) => w.classList.remove('draft-block--over'))
            if (!sourceId || sourceId === targetId) return

            const ids = getOrderedIds(container)
            const from = ids.indexOf(sourceId)
            const to = ids.indexOf(targetId)
            if (from === -1 || to === -1) return

            const next = [...ids]
            const [moved] = next.splice(from, 1)
            next.splice(to, 0, moved)

            const rows = next.map((id, order) => ({ id, order }))
            setMessage(msgEl, '')
            try {
                const res = await apiJson('/api/blocks/reorder', {
                    method: 'POST',
                    body: JSON.stringify({ blocks: rows }),
                })
                if (!res.ok) {
                    if (res.status === 419) throw new Error('Sesión expirada. Recarga la página.')
                    if (res.status === 403) throw new Error('No tienes permiso para reordenar.')
                    throw new Error('No se pudo reordenar.')
                }
                window.location.reload()
            } catch (err) {
                setMessage(msgEl, err?.message || 'Error al reordenar.', true)
            }
        })
    })
}

document.addEventListener('DOMContentLoaded', init)
