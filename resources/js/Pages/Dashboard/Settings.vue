<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Link } from '@inertiajs/vue3'
import { reactive, ref, watch } from 'vue'

const props = defineProps({
    site: {
        type: Object,
        required: true,
    },
})

const form = reactive({
    name: props.site.name || '',
    slug: props.site.slug || '',
    bio: props.site.bio || '',
})

watch(
    () => props.site,
    (s) => {
        if (!s) return
        form.name = s.name || ''
        form.slug = s.slug || ''
        form.bio = s.bio || ''
    },
    { deep: true },
)

const avatarFile = ref(null)
const saving = ref(false)
const message = ref('')

function onAvatarChange(event) {
    const file = event.target.files?.[0]
    avatarFile.value = file || null
}

function readXsrfToken() {
    const m = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/)
    return m ? decodeURIComponent(m[1]) : ''
}

async function saveProfile() {
    saving.value = true
    message.value = ''
    const token = readXsrfToken()

    try {
        let res
        if (avatarFile.value) {
            const body = new FormData()
            body.append('name', form.name)
            body.append('slug', form.slug)
            body.append('bio', form.bio)
            body.append('avatar', avatarFile.value)
            res = await fetch('/api/profile', {
                method: 'PATCH',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': token,
                },
                body,
            })
        } else {
            res = await fetch('/api/profile', {
                method: 'PATCH',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': token,
                },
                body: JSON.stringify({
                    name: form.name,
                    slug: form.slug,
                    bio: form.bio,
                }),
            })
        }

        if (!res.ok) {
            message.value = 'No se pudo guardar. Revisa los datos.'
            return
        }

        message.value = 'Cambios guardados.'
        window.location.reload()
    } finally {
        saving.value = false
    }
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Ajustes del sitio</h2>
                <Link href="/dashboard" class="text-sm text-indigo-600 hover:text-indigo-800">
                    ← Volver al editor
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <form class="space-y-6 rounded-lg bg-white p-6 shadow" @submit.prevent="saveProfile">
                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="site-name">Nombre público</label>
                        <input
                            id="site-name"
                            v-model="form.name"
                            name="name"
                            type="text"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="site-slug">Slug (URL)</label>
                        <input
                            id="site-slug"
                            v-model="form.slug"
                            name="slug"
                            type="text"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            pattern="[a-z0-9\-]+"
                            required
                        />
                        <p class="mt-1 text-xs text-gray-500">Solo minúsculas, números y guiones.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="site-bio">Bio</label>
                        <textarea
                            id="site-bio"
                            v-model="form.bio"
                            name="bio"
                            rows="4"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="site-avatar">Avatar</label>
                        <input
                            id="site-avatar"
                            name="avatar"
                            type="file"
                            accept="image/*"
                            class="mt-1 block w-full text-sm text-gray-500"
                            @change="onAvatarChange"
                        />
                        <img v-if="site.avatar_url" :src="site.avatar_url" alt="" class="mt-2 h-24 w-24 rounded-full object-cover" />
                    </div>
                    <p v-if="message" class="text-sm text-gray-600">{{ message }}</p>
                    <button
                        type="submit"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-indigo-700 disabled:opacity-50"
                        data-action="save-settings"
                        :disabled="saving"
                    >
                        {{ saving ? 'Guardando…' : 'Guardar' }}
                    </button>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
