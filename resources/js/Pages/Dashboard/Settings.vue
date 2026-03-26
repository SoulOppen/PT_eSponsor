<script setup>
import Modal from '@/Components/Modal.vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue'

const props = defineProps({
    site: {
        type: Object,
        required: true,
    },
})

/** Normalize nullable values to text for stable dirty checks. */
function asText(value) {
    if (value === null || value === undefined) return ''
    return String(value)
}

const form = reactive({
    name: asText(props.site.name),
    slug: asText(props.site.slug),
    bio: asText(props.site.bio),
})

watch(
    () => props.site,
    (s) => {
        if (!s) return
        form.name = asText(s.name)
        form.slug = asText(s.slug)
        form.bio = asText(s.bio)
    },
    { deep: true },
)

const avatarFile = ref(null)
const avatarInput = ref(null)
const selectedAvatarPreview = ref('')
const removeAvatar = ref(false)
const saving = ref(false)
const message = ref('')
const confirmRestoreAllOpen = ref(false)

const avatarPreviewUrl = computed(() => {
    const raw = props.site?.avatar_url
    if (!raw) return ''
    if (String(raw).startsWith('http://') || String(raw).startsWith('https://')) {
        return String(raw)
    }
    if (String(raw).startsWith('/')) {
        return String(raw)
    }
    if (String(raw).startsWith('avatars/')) {
        return `/storage/${raw}`
    }
    return String(raw)
})

const currentAvatarDisplay = computed(() => avatarPreviewUrl.value || '')
const nextAvatarDisplay = computed(() => {
    if (selectedAvatarPreview.value) return selectedAvatarPreview.value
    if (removeAvatar.value) return ''
    return avatarPreviewUrl.value || ''
})

const savedName = computed(() => asText(props.site?.name))
const savedSlug = computed(() => asText(props.site?.slug))
const savedBio = computed(() => asText(props.site?.bio))

const nameDirty = computed(() => asText(form.name) !== savedName.value)
const slugDirty = computed(() => asText(form.slug) !== savedSlug.value)
const bioDirty = computed(() => asText(form.bio) !== savedBio.value)
const avatarDirty = computed(
    () => !!avatarFile.value || (!!avatarPreviewUrl.value && removeAvatar.value),
)

const hasPendingChanges = computed(
    () => nameDirty.value || slugDirty.value || bioDirty.value || avatarDirty.value,
)

const canRemoveAvatar = computed(
    () => (!!avatarPreviewUrl.value && !removeAvatar.value) || !!avatarFile.value,
)

const canUndoAvatarRemove = computed(() => !!avatarPreviewUrl.value && removeAvatar.value)

function resetName() {
    form.name = asText(savedName.value)
}

function resetSlug() {
    form.slug = asText(savedSlug.value)
}

function resetBio() {
    form.bio = asText(savedBio.value)
}

function resetAvatar() {
    if (selectedAvatarPreview.value) {
        URL.revokeObjectURL(selectedAvatarPreview.value)
        selectedAvatarPreview.value = ''
    }
    avatarFile.value = null
    removeAvatar.value = false
    if (avatarInput.value) {
        avatarInput.value.value = ''
    }
}

function resetAll() {
    resetName()
    resetSlug()
    resetBio()
    resetAvatar()
}

function openConfirmRestoreAllDialog() {
    confirmRestoreAllOpen.value = true
}

function closeConfirmRestoreAllDialog() {
    confirmRestoreAllOpen.value = false
}

function confirmRestoreAllFromDialog() {
    resetAll()
    closeConfirmRestoreAllDialog()
}

function onAvatarChange(event) {
    const file = event.target.files?.[0]
    if (selectedAvatarPreview.value) {
        URL.revokeObjectURL(selectedAvatarPreview.value)
        selectedAvatarPreview.value = ''
    }
    avatarFile.value = file || null
    if (avatarFile.value) {
        selectedAvatarPreview.value = URL.createObjectURL(avatarFile.value)
        removeAvatar.value = false
    }
}

function onRemoveAvatar() {
    if (selectedAvatarPreview.value) {
        URL.revokeObjectURL(selectedAvatarPreview.value)
        selectedAvatarPreview.value = ''
    }
    avatarFile.value = null
    if (avatarInput.value) {
        avatarInput.value.value = ''
    }
    removeAvatar.value = !!avatarPreviewUrl.value
}

function undoRemoveAvatar() {
    removeAvatar.value = false
}

onBeforeUnmount(() => {
    if (selectedAvatarPreview.value) {
        URL.revokeObjectURL(selectedAvatarPreview.value)
    }
})

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
    <Head title="Ajustes del sitio" />

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
                        <div class="mb-2 flex min-h-10 items-center justify-between gap-3">
                            <label class="min-w-0 flex-1 text-sm font-medium text-gray-700" for="site-name"
                                >Nombre público</label
                            >
                            <div
                                class="flex min-h-10 w-[7.75rem] shrink-0 items-center justify-end sm:w-[8.25rem]"
                            >
                                <button
                                    v-if="nameDirty"
                                    type="button"
                                    class="min-h-10 w-full text-xs font-medium text-indigo-600 underline decoration-indigo-300 underline-offset-2 hover:text-indigo-800"
                                    @click="resetName"
                                >
                                    Restaurar
                                </button>
                            </div>
                        </div>
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
                        <div class="mb-2 flex min-h-10 items-center justify-between gap-3">
                            <label class="min-w-0 flex-1 text-sm font-medium text-gray-700" for="site-slug"
                                >Slug (URL)</label
                            >
                            <div
                                class="flex min-h-10 w-[7.75rem] shrink-0 items-center justify-end sm:w-[8.25rem]"
                            >
                                <button
                                    v-if="slugDirty"
                                    type="button"
                                    class="min-h-10 w-full text-xs font-medium text-indigo-600 underline decoration-indigo-300 underline-offset-2 hover:text-indigo-800"
                                    @click="resetSlug"
                                >
                                    Restaurar
                                </button>
                            </div>
                        </div>
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
                        <div class="mb-2 flex min-h-10 items-center justify-between gap-3">
                            <label class="min-w-0 flex-1 text-sm font-medium text-gray-700" for="site-bio">Bio</label>
                            <div
                                class="flex min-h-10 w-[7.75rem] shrink-0 items-center justify-end sm:w-[8.25rem]"
                            >
                                <button
                                    v-if="bioDirty"
                                    type="button"
                                    class="min-h-10 w-full text-xs font-medium text-indigo-600 underline decoration-indigo-300 underline-offset-2 hover:text-indigo-800"
                                    @click="resetBio"
                                >
                                    Restaurar
                                </button>
                            </div>
                        </div>
                        <textarea
                            id="site-bio"
                            v-model="form.bio"
                            name="bio"
                            rows="4"
                            class="min-h-[7rem] w-full touch-manipulation rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <p class="mt-1.5 text-xs text-gray-500">
                            Esta bio es el texto que ves bajo el nombre en tu página pública y también se usa como
                            <span class="font-medium">descripción meta (SEO)</span> en <code class="text-gray-600">/@{{ form.slug || 'tu-slug' }}</code>
                            (si la dejas vacía, se usa el texto por defecto de la plataforma).
                        </p>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700" for="site-avatar">Avatar</label>
                        <div
                            class="mb-3 flex min-h-[5.5rem] items-center justify-center gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3"
                        >
                            <div class="min-h-16 min-w-[4.5rem] text-center">
                                <div
                                    class="mx-auto flex h-16 w-16 items-center justify-center overflow-hidden rounded-full ring-1 ring-gray-300"
                                >
                                    <img
                                        v-if="currentAvatarDisplay"
                                        :src="currentAvatarDisplay"
                                        alt="Avatar actual"
                                        class="h-full w-full object-cover"
                                    />
                                    <span v-else class="px-2 text-[10px] font-semibold text-gray-500">No avatar</span>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Actual</p>
                            </div>

                            <span class="min-h-11 shrink-0 px-2 text-lg font-bold leading-none text-gray-400">→</span>

                            <div class="min-h-16 min-w-[4.5rem] text-center">
                                <div
                                    class="mx-auto flex h-16 w-16 items-center justify-center overflow-hidden rounded-full ring-1 ring-indigo-300"
                                >
                                    <img
                                        v-if="nextAvatarDisplay"
                                        :src="nextAvatarDisplay"
                                        alt="Avatar nuevo"
                                        class="h-full w-full object-cover"
                                    />
                                    <span v-else class="px-2 text-[10px] font-semibold text-gray-500">No avatar</span>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Nuevo</p>
                            </div>
                        </div>

                        <input
                            id="site-avatar"
                            ref="avatarInput"
                            name="avatar"
                            type="file"
                            accept="image/*"
                            class="min-h-11 w-full touch-manipulation text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-indigo-700"
                            @change="onAvatarChange"
                        />
                        <div class="mt-4 flex min-h-11 flex-wrap items-center gap-2 sm:gap-3">
                            <button
                                type="button"
                                class="inline-flex min-h-11 w-[14rem] items-center justify-center rounded-md border border-red-200 bg-red-50 px-3 text-sm font-medium text-red-700 hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-50 sm:w-[15rem]"
                                :disabled="!canRemoveAvatar"
                                @click="onRemoveAvatar"
                            >
                                Borrar avatar
                            </button>
                            <button
                                type="button"
                                class="inline-flex min-h-11 w-[14rem] items-center justify-center rounded-md border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 sm:w-[15rem]"
                                :disabled="!canUndoAvatarRemove"
                                @click="undoRemoveAvatar"
                            >
                                Deshacer borrado
                            </button>
                        </div>
                    </div>
                    <div class="min-h-6">
                        <p v-if="message" class="text-sm text-gray-600">{{ message }}</p>
                    </div>
                    <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                        <button
                            type="submit"
                            class="min-h-11 w-full touch-manipulation rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow active:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 sm:w-auto sm:py-3"
                            data-action="save-settings"
                            :disabled="saving"
                        >
                            {{ saving ? 'Guardando…' : 'Guardar' }}
                        </button>
                        <button
                            type="button"
                            data-action="restore-all"
                            class="min-h-11 w-full rounded-md border border-amber-300 bg-white px-4 py-2.5 text-sm font-medium text-amber-900 hover:bg-amber-50 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto"
                            :disabled="!hasPendingChanges"
                            @click="openConfirmRestoreAllDialog"
                        >
                            Restaurar todo
                        </button>
                    </div>
                </form>

                <Modal :show="confirmRestoreAllOpen" max-width="md" @close="closeConfirmRestoreAllDialog">
                    <div class="p-5 sm:p-6">
                        <h3 class="text-base font-semibold text-gray-900">¿Restaurar todos los valores?</h3>
                        <p class="mt-2 text-sm text-gray-600">
                            Se descartarán los cambios sin guardar en nombre, slug, bio y avatar, volviendo a lo que
                            está en el servidor.
                        </p>
                        <div class="mt-6 flex min-h-11 flex-col-reverse gap-2 sm:flex-row sm:justify-end sm:gap-3">
                            <button
                                type="button"
                                class="min-h-11 w-full rounded-md border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 sm:w-auto"
                                @click="closeConfirmRestoreAllDialog"
                            >
                                Cancelar
                            </button>
                            <button
                                type="button"
                                class="min-h-11 w-full rounded-md bg-amber-600 px-4 text-sm font-semibold text-white hover:bg-amber-700 sm:w-auto"
                                @click="confirmRestoreAllFromDialog"
                            >
                                Sí, restaurar todo
                            </button>
                        </div>
                    </div>
                </Modal>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
