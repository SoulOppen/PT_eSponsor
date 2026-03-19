import { ref } from 'vue'

function readXsrfToken() {
    if (typeof document === 'undefined') return ''
    const m = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/)
    return m ? decodeURIComponent(m[1]) : ''
}

export function usePublish() {
    const isDirty = ref(false)

    function markDirty() {
        isDirty.value = true
    }

    async function publish() {
        const headers = {
            Accept: 'application/json',
            'Content-Type': 'application/json',
        }
        const token = readXsrfToken()
        if (token) headers['X-XSRF-TOKEN'] = token

        await fetch('/api/site/publish', {
            method: 'POST',
            credentials: 'same-origin',
            headers,
            body: '{}',
        })
        isDirty.value = false
    }

    return { isDirty, markDirty, publish }
}
