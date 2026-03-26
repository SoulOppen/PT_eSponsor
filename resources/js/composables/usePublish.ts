import { ref, type Ref } from 'vue';

function readXsrfToken(): string {
    if (typeof document === 'undefined') return '';
    const m = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
    return m ? decodeURIComponent(m[1]) : '';
}

type PublishResult = Record<string, unknown>;

export function usePublish(): {
    isDirty: Ref<boolean>;
    markDirty: () => void;
    resetDirty: () => void;
    publish: () => Promise<PublishResult>;
} {
    const isDirty = ref(false);

    function markDirty(): void {
        isDirty.value = true;
    }

    function resetDirty(): void {
        isDirty.value = false;
    }

    async function publish(): Promise<PublishResult> {
        const headers = new Headers();
        headers.set('Accept', 'application/json');
        headers.set('Content-Type', 'application/json');

        const token = readXsrfToken();
        if (token) headers.set('X-XSRF-TOKEN', token);

        const res = await fetch('/api/site/publish', {
            method: 'POST',
            credentials: 'same-origin',
            headers,
            body: '{}',
        });
        if (!res.ok) {
            throw new Error('No se pudo publicar.');
        }
        isDirty.value = false;
        try {
            return (await res.json()) as PublishResult;
        } catch {
            return {};
        }
    }

    return { isDirty, markDirty, resetDirty, publish };
}
