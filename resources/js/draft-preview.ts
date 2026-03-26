/**
 * Barra de publicar + reordenacion por arrastre en /draft/@slug (solo dueno).
 * El arrastre solo se inicia desde el asa (.draft-block-wrap__handle), no desde el contenido.
 */

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

function setMessage(el: HTMLElement | null, text = '', isError = false): void {
    if (!el) return;
    el.textContent = text;
    el.style.color = isError ? '#b91c1c' : '#166534';
}

function getOrderedIds(container: HTMLElement): number[] {
    return Array.from(container.querySelectorAll<HTMLElement>('[data-block-id]'))
        .map((el) => Number(el.dataset.blockId));
}

function init(): void {
    const toolbar = document.getElementById('draft-edit-toolbar') as HTMLElement | null;
    const container = document.getElementById('draft-blocks') as HTMLElement | null;
    if (!toolbar || !container) return;

    const btnPublish = document.getElementById('draft-edit-publish') as HTMLButtonElement | null;
    const msgEl = document.getElementById('draft-edit-message') as HTMLElement | null;
    const publicUrl = toolbar.dataset.publicUrl || '/';
    const canPublish = toolbar.dataset.canPublish === '1';

    function setPublishDisabled(disabled: boolean): void {
        if (!btnPublish) return;
        btnPublish.disabled = disabled || !canPublish;
    }

    btnPublish?.addEventListener('click', async () => {
        if (!canPublish || btnPublish.disabled) return;
        btnPublish.disabled = true;
        setMessage(msgEl, '');
        try {
            const res = await apiJson('/api/site/publish', { method: 'POST', body: '{}' });
            if (!res.ok) {
                if (res.status === 419) throw new Error('Sesion expirada. Recarga la pagina.');
                if (res.status === 401) throw new Error('Debes iniciar sesion.');
                if (res.status === 403) throw new Error('No tienes permiso para publicar.');
                throw new Error('No se pudo publicar.');
            }
            window.location.href = publicUrl;
        } catch (e: unknown) {
            const message = e instanceof Error ? e.message : 'Error al publicar.';
            setMessage(msgEl, message, true);
        } finally {
            setPublishDisabled(false);
        }
    });

    let draggingId: number | null = null;
    const wraps = container.querySelectorAll<HTMLElement>('.draft-block-wrap[data-block-id]');
    const handles = container.querySelectorAll<HTMLElement>('.draft-block-wrap__handle');

    handles.forEach((handle) => {
        handle.addEventListener('dragstart', (e: DragEvent) => {
            const wrap = handle.closest<HTMLElement>('[data-block-id]');
            if (!wrap || !e.dataTransfer) return;
            draggingId = Number(wrap.dataset.blockId);
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', String(draggingId));
            wrap.classList.add('draft-block--dragging');
        });

        handle.addEventListener('dragend', () => {
            const wrap = handle.closest<HTMLElement>('[data-block-id]');
            draggingId = null;
            if (wrap) wrap.classList.remove('draft-block--dragging');
            wraps.forEach((w) => w.classList.remove('draft-block--over'));
        });
    });

    wraps.forEach((el) => {
        el.addEventListener('dragover', (e: DragEvent) => {
            if (draggingId == null) return;
            const targetId = Number(el.dataset.blockId);
            if (targetId === draggingId) return;
            e.preventDefault();
            wraps.forEach((w) => w.classList.remove('draft-block--over'));
            el.classList.add('draft-block--over');
        });

        el.addEventListener('dragleave', () => {
            el.classList.remove('draft-block--over');
        });

        el.addEventListener('drop', async (e: DragEvent) => {
            e.preventDefault();
            const sourceId = Number(e.dataTransfer?.getData('text/plain') || draggingId);
            const targetId = Number(el.dataset.blockId);
            wraps.forEach((w) => w.classList.remove('draft-block--over'));
            if (!sourceId || sourceId === targetId) return;

            const ids = getOrderedIds(container);
            const from = ids.indexOf(sourceId);
            const to = ids.indexOf(targetId);
            if (from === -1 || to === -1) return;

            const next = [...ids];
            const [moved] = next.splice(from, 1);
            next.splice(to, 0, moved);

            const rows = next.map((id, order) => ({ id, order }));
            setMessage(msgEl, '');
            try {
                const res = await apiJson('/api/blocks/reorder', {
                    method: 'POST',
                    body: JSON.stringify({ blocks: rows }),
                });
                if (!res.ok) {
                    if (res.status === 419) throw new Error('Sesion expirada. Recarga la pagina.');
                    if (res.status === 403) throw new Error('No tienes permiso para reordenar.');
                    throw new Error('No se pudo reordenar.');
                }
                window.location.reload();
            } catch (err: unknown) {
                const message = err instanceof Error ? err.message : 'Error al reordenar.';
                setMessage(msgEl, message, true);
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', init);
