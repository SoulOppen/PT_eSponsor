<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import BlockCatalog from '@/Components/Editor/BlockCatalog.vue'
import BlockList from '@/Components/Editor/BlockList.vue'
import ResetBlocksDialog from '@/Components/Editor/ResetBlocksDialog.vue'
import PreviewFrame from '@/Components/Preview/PreviewFrame.vue'
import { useBlocks } from '@/composables/useBlocks'
import { usePublish } from '@/composables/usePublish'
import { Head, Link } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const props = defineProps({
    site: { type: Object, required: true },
    blocks: { type: Array, required: true },
    blockSchemas: { type: Object, required: true },
})

function defaultPropsForType(type) {
    const schema = props.blockSchemas[type]
    if (!schema?.fields) return {}
    const out = {}
    for (const f of schema.fields) {
        if (f.type === 'select' && f.options?.length) {
            out[f.key] = f.options[0]
        } else if (f.type === 'color') {
            out[f.key] = f.default || '#000000'
        } else if (f.type === 'repeater') {
            const row = {}
            for (const s of f.subfields || []) {
                if (s.type === 'url') {
                    row[s.key] = 'https://example.com'
                } else if (s.type === 'select' && s.options?.length) {
                    row[s.key] = s.options[0]
                } else {
                    row[s.key] = 'Nuevo'
                }
            }
            out[f.key] = f.required ? [row] : []
        } else if (type === 'music' && f.key === 'url') {
            out[f.key] = 'https://open.spotify.com/track/6rqhFgbbKwnb9MLmUQDhG6'
        } else if (type === 'text' && f.key === 'content') {
            out[f.key] = 'Nuevo texto'
        } else if (type === 'links' && f.key === 'title') {
            out[f.key] = 'Mis enlaces'
        } else if (type === 'video' && f.key === 'url') {
            out[f.key] = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'
        } else if (type === 'image' && f.key === 'url') {
            out[f.key] = 'https://placehold.co/600x400/png'
        } else {
            out[f.key] = ''
        }
    }
    return out
}

const initialBlocks = props.blocks.map((b) => ({
    ...b,
    props: { ...(b.props || {}) },
}))

const { sortedBlocks, addBlock, removeBlock, toggleBlock, duplicateBlock, updateBlock, reorderBlocks } =
    useBlocks(initialBlocks)
const { isDirty, markDirty, publish } = usePublish()
const addBlockError = ref('')
const resetError = ref('')
const resettingBlocks = ref(false)
const showResetDialog = ref(false)
const mobilePanel = ref('blocks')

const publicUrl = computed(() => `/@${props.site.slug}`)
const blockCount = computed(() => sortedBlocks.value.length)
const hasBlocks = computed(() => blockCount.value > 0)
const blockTypeCounts = computed(() => {
    const counts = {}
    for (const block of sortedBlocks.value) {
        const type = block?.type
        if (!type) continue
        counts[type] = (counts[type] || 0) + 1
    }
    return counts
})

async function handleAddType(type) {
    addBlockError.value = ''
    try {
        await addBlock(type, defaultPropsForType(type))
        markDirty()
    } catch (error) {
        addBlockError.value = error?.message || 'No se pudo crear el bloque.'
    }
}

async function handleDelete(id) {
    resetError.value = ''
    try {
        await removeBlock(id)
        markDirty()
    } catch (error) {
        resetError.value = error?.message || 'No se pudo eliminar el bloque.'
    }
}

async function handleToggle(id) {
    await toggleBlock(id)
    markDirty()
}

async function handleDuplicate(id) {
    await duplicateBlock(id)
    markDirty()
}

async function handleUpdateProps(id, newProps) {
    await updateBlock(id, newProps)
    markDirty()
}

async function handlePublish() {
    await publish()
}

async function handleReorder(orderedIds) {
    await reorderBlocks(orderedIds)
    markDirty()
}

async function handleResetBlocks() {
    if (!sortedBlocks.value.length || resettingBlocks.value) return
    showResetDialog.value = false
    resetError.value = ''
    resettingBlocks.value = true

    try {
        const ids = sortedBlocks.value.map((b) => b.id)
        for (const id of ids) {
            await removeBlock(id)
        }
        markDirty()
    } catch (error) {
        resetError.value = error?.message || 'No se pudieron eliminar todos los bloques.'
    } finally {
        resettingBlocks.value = false
    }
}

function openResetDialog() {
    if (!hasBlocks.value || resettingBlocks.value) return
    showResetDialog.value = true
}
</script>

<template>
    <Head title="Editor de página" />

    <AuthenticatedLayout>
        <template #header>
            <!-- Mobile-first: columna en pantallas estrechas, fila desde sm -->
            <div class="flex w-full flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <h2 class="text-lg font-semibold leading-snug text-gray-800 sm:text-xl">
                    Editor de página
                </h2>
                <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:flex-wrap sm:items-center sm:justify-end sm:gap-3">
                    <p
                        v-if="isDirty"
                        class="order-first rounded-md bg-amber-50 px-3 py-2 text-center text-sm text-amber-800 sm:order-none sm:bg-transparent sm:px-0 sm:py-0"
                    >
                        Cambios sin publicar
                    </p>
                    <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:gap-2">
                        <a
                            :href="publicUrl"
                            target="_blank"
                            rel="noopener"
                            class="flex min-h-11 touch-manipulation items-center justify-center rounded-lg border border-gray-200 bg-white px-4 text-center text-sm font-medium text-gray-700 shadow-sm active:bg-gray-50 sm:min-h-0 sm:border-0 sm:bg-transparent sm:px-2 sm:py-2 sm:shadow-none sm:underline"
                        >
                            Ver público ↗
                        </a>
                        <Link
                            href="/dashboard/settings"
                            class="flex min-h-11 touch-manipulation items-center justify-center rounded-lg border border-gray-200 bg-white px-4 text-center text-sm font-medium text-gray-700 shadow-sm active:bg-gray-50 sm:min-h-0 sm:border-0 sm:bg-transparent sm:px-2 sm:py-2 sm:shadow-none sm:underline"
                        >
                            Ajustes del sitio
                        </Link>
                    </div>
                    <button
                        type="button"
                        class="min-h-11 w-full touch-manipulation rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow active:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto sm:py-2"
                        data-action="publish"
                        @click="handlePublish"
                    >
                        Publicar
                    </button>
                </div>
            </div>
        </template>

        <!-- py-6 móvil primero; más aire en tablet+ -->
        <div class="py-6 sm:py-8">
            <div class="app-main-padding mx-auto max-w-7xl space-y-8 sm:space-y-10">
                <section>
                    <h3 class="mb-3 text-base font-semibold text-gray-900 sm:text-lg">Añadir bloque</h3>
                    <p
                        v-if="addBlockError"
                        class="mb-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
                        data-action="add-block-error"
                    >
                        {{ addBlockError }}
                    </p>
                    <BlockCatalog :schemas="blockSchemas" :counts="blockTypeCounts" @select="handleAddType" />
                </section>

                <div class="mb-3 grid grid-cols-2 gap-2 rounded-lg bg-gray-100 p-1 lg:hidden">
                    <button
                        type="button"
                        class="min-h-11 rounded-md px-3 text-sm font-medium transition"
                        :class="mobilePanel === 'blocks' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600'"
                        @click="mobilePanel = 'blocks'"
                    >
                        Bloques
                    </button>
                    <button
                        type="button"
                        class="min-h-11 rounded-md px-3 text-sm font-medium transition"
                        :class="mobilePanel === 'preview' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600'"
                        @click="mobilePanel = 'preview'"
                    >
                        Vista previa
                    </button>
                </div>

                <!-- Una columna por defecto; dos desde lg -->
                <div class="grid gap-8 lg:grid-cols-2 lg:gap-10">
                    <section
                        class="min-w-0 rounded-lg p-3 sm:p-4"
                        :class="[
                            { hidden: mobilePanel !== 'blocks', 'lg:block': true },
                            hasBlocks ? 'bg-blue-50 ring-1 ring-blue-200' : 'bg-transparent',
                        ]"
                    >
                        <div class="mb-3 flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-semibold text-gray-900 sm:text-lg">Tus bloques</h3>
                                <span
                                    class="inline-flex h-6 w-10 items-center justify-center rounded-full bg-sky-200 text-xs font-bold text-sky-900"
                                    :class="hasBlocks ? 'opacity-100' : 'opacity-0'"
                                    aria-live="polite"
                                >
                                    {{ blockCount }}
                                </span>
                            </div>
                            <button
                                type="button"
                                class="inline-flex min-h-10 items-center gap-2 rounded-md border border-red-200 bg-white px-3 text-sm font-medium text-red-700 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="!hasBlocks || resettingBlocks"
                                data-action="reset-blocks"
                                @click="openResetDialog"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path
                                        fill-rule="evenodd"
                                        d="M8.5 2a1 1 0 0 0-1 1V4H5a1 1 0 1 0 0 2h.278l.58 9.29A2 2 0 0 0 7.854 17h4.292a2 2 0 0 0 1.996-1.71l.58-9.29H15a1 1 0 1 0 0-2h-2.5V3a1 1 0 0 0-1-1h-3Z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                {{ resettingBlocks ? 'Reseteando...' : 'Reset' }}
                            </button>
                        </div>
                        <p
                            v-if="resetError"
                            class="mb-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
                        >
                            {{ resetError }}
                        </p>
                        <BlockList
                            :blocks="sortedBlocks"
                            :block-schemas="blockSchemas"
                            @delete="handleDelete"
                            @toggle="handleToggle"
                            @duplicate="handleDuplicate"
                            @update-props="handleUpdateProps"
                            @reorder="handleReorder"
                        />
                    </section>
                    <section class="min-w-0" :class="{ hidden: mobilePanel !== 'preview', 'lg:block': true }">
                        <h3 class="mb-3 text-base font-semibold text-gray-900 sm:text-lg">Vista previa</h3>
                        <div class="overflow-x-auto rounded-lg border border-dashed border-gray-300 bg-gray-50 p-3 sm:p-4">
                            <PreviewFrame :blocks="sortedBlocks" :site="site" />
                        </div>
                    </section>
                </div>
            </div>
        </div>

        <ResetBlocksDialog
            :show="showResetDialog"
            :count="blockCount"
            :loading="resettingBlocks"
            @close="showResetDialog = false"
            @confirm="handleResetBlocks"
        />
    </AuthenticatedLayout>
</template>
