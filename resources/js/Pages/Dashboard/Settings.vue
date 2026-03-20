<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Link } from '@inertiajs/vue3'
import { computed, reactive, ref, watch } from 'vue'

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
const removeAvatar = ref(false)
const saving = ref(false)
const message = ref('')

const avatarPreviewUrl = computed(() => {
    const raw = props.site?.avatar_url
    if (!raw) return ''
    if (String(raw).startsWith('http://') || String(raw).startsWith('https://')) {
        return String(raw)
    }
    if (String(raw).startsWith('/')) {
        return String(raw)
    }
    // Backward compatibility when DB has relative storage paths.
    if (String(raw).startsWith('avatars/')) {
        return `/storage/${raw}`
    }
    return String(raw)
})

function onAvatarChange(event) {
    const file = event.target.files?.[0]
    avatarFile.value = file || null
    if (avatarFile.value) {
        removeAvatar.value = false
    }
}

function onRemoveAvatar() {
    avatarFile.value = null
    removeAvatar.value = true
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
            body.append('remove_avatar', removeAvatar.value ? '1' : '0')
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
                    remove_avatar: removeAvatar.value,
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
            <div class="flex w-full flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-lg font-semibold leading-snug text-gray-800 sm:text-xl">Ajustes del sitio</h2>
                <Link
                    href="/dashboard"
                    class="inline-flex min-h-11 touch-manipulation items-center justify-center rounded-lg border border-indigo-200 bg-indigo-50 px-4 text-sm font-medium text-indigo-700 active:bg-indigo-100 sm:min-h-0 sm:border-0 sm:bg-transparent sm:px-0 sm:py-1"
                >
                    ← Volver al editor
                </Link>
            </div>
        </template>

        <div class="py-6 sm:py-8">
            <div class="app-main-padding mx-auto max-w-2xl">
                <form
                    class="space-y-5 rounded-lg border border-gray-100 bg-white p-4 shadow-sm sm:space-y-6 sm:border-0 sm:p-6 sm:shadow"
                    @submit.prevent="saveProfile"
                >
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700" for="site-name"
                            >Nombre público</label
                        >
                        <input
                            id="site-name"
                            v-model="form.name"
                            name="name"
                            type="text"
                            class="min-h-11 w-full touch-manipulation rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700" for="site-slug"
                            >Slug (URL)</label
                        >
                        <input
                            id="site-slug"
                            v-model="form.slug"
                            name="slug"
                            type="text"
                            class="min-h-11 w-full touch-manipulation rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            pattern="[a-z0-9\-]+"
                            required
                            autocapitalize="none"
                            autocomplete="off"
                        />
                        <p class="mt-1.5 text-xs text-gray-500">Solo minúsculas, números y guiones.</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700" for="site-bio">Bio</label>
                        <textarea
                            id="site-bio"
                            v-model="form.bio"
                            name="bio"
                            rows="4"
                            class="min-h-[7rem] w-full touch-manipulation rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700" for="site-avatar"
                            >Avatar</label
                        >
                        <input
                            id="site-avatar"
                            name="avatar"
                            type="file"
                            accept="image/*"
                            class="min-h-11 w-full touch-manipulation text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-indigo-700"
                            @change="onAvatarChange"
                        />
                        <img
                            v-if="avatarPreviewUrl && !removeAvatar"
                            :src="avatarPreviewUrl"
                            alt="Avatar del sitio"
                            class="mt-3 h-24 w-24 rounded-full object-cover ring-1 ring-gray-200"
                        />
                        <button
                            v-if="(avatarPreviewUrl && !removeAvatar) || avatarFile"
                            type="button"
                            class="mt-2 inline-flex min-h-10 items-center rounded-md border border-red-200 bg-red-50 px-3 text-sm font-medium text-red-700 hover:bg-red-100"
                            @click="onRemoveAvatar"
                        >
                            Borrar avatar
                        </button>
                    </div>
                    <p v-if="message" class="text-sm text-gray-600">{{ message }}</p>
                    <button
                        type="submit"
                        class="min-h-11 w-full touch-manipulation rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow active:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 sm:w-auto sm:py-3"
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
