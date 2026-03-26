<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import BlockCatalog from '@/Components/Editor/BlockCatalog.vue'
import BlockList from '@/Components/Editor/BlockList.vue'
import ResetBlocksDialog from '@/Components/Editor/ResetBlocksDialog.vue'
import PreviewFrame from '@/Components/Preview/PreviewFrame.vue'
import { useBlocks } from '@/composables/useBlocks'
import { usePublish } from '@/composables/usePublish'
import { siteBlocksSnapshot } from '@/utils/siteBlocksSnapshot'
import {
    currentMatchesPublishedBaseline,
    orderMatchesPublishedBaseline,
    parsePublishedBlocksSnapshot,
    toSnapshotJsonString,
} from '@/utils/publishedBaseline'
import { Head, Link } from '@inertiajs/vue3'
import { computed, onBeforeUnmount, ref, watch } from 'vue'

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

const {
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
} = useBlocks(initialBlocks)
const { isDirty, markDirty, resetDirty, publish } = usePublish()
/** JSON de última publicación; se actualiza al publicar, restaurar, vaciar o al cambiar `props.site` (Inertia). */
const publishedSnapshot = ref(toSnapshotJsonString(props.site?.published_blocks_snapshot))

watch(
    () => props.site?.published_blocks_snapshot,
    (snap) => {
        publishedSnapshot.value = toSnapshotJsonString(snap)
    },
)
/** Filas parseadas del snapshot publicado (orden, ids, props, pub). Se recalcula con `publishedSnapshot`. */
const publishedBaselineRows = computed(() => parsePublishedBlocksSnapshot(publishedSnapshot.value))
/** Snapshot canónico de bloques activos actuales (misma regla que el servidor). */
const currentSnapshotString = computed(() => siteBlocksSnapshot(sortedBlocks.value))
const addBlockError = ref('')
const publishError = ref('')
const publishing = ref(false)
const resetError = ref('')
const resettingBlocks = ref(false)
/** No permitir editar mientras publica o restaura/vacía bloques en servidor. */
const editorBusy = computed(() => publishing.value || resettingBlocks.value)
/** Diálogo masivo: eliminar todo vs quitar solo borradores (no publicados) */
const bulkDialog = ref({ show: false, variant: 'deleteAll' })
const mobilePanel = ref('blocks')

const publicUrl = computed(() => `/@${props.site.slug}`)
/** Vista previa a pantalla completa (requiere sesión; mismo origen que el dashboard). */
const draftUrl = computed(() => `/draft/@${props.site.slug}`)
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

/** Bloques activos que aún no están en la versión pública (p. ej. al abrir el editor sin tocar nada). */
const hasUnpublishedActive = computed(() =>
    sortedBlocks.value.some((b) => b.is_active && !b.is_published),
)

/**
 * Contenido (props/pub) o secuencia id:order de bloques activos difiere de la última publicación.
 * Incluye subir/bajar y arrastre (misma huella que el snapshot del servidor).
 */
const structuralPending = computed(
    () =>
        !currentMatchesPublishedBaseline(currentSnapshotString.value, publishedSnapshot.value) ||
        !orderMatchesPublishedBaseline(sortedBlocks.value, publishedSnapshot.value),
)

/** Puede publicar: edición local, activos sin publicar o estado estructural distinto al snapshot. */
const canPublish = computed(
    () => isDirty.value || hasUnpublishedActive.value || structuralPending.value,
)

/** Bloques con is_published = false (borradores; el backend los elimina al «volver a lo publicado»). */
const unpublishedCount = computed(() => sortedBlocks.value.filter((b) => !b.is_published).length)
const hasUnpublishedDrafts = computed(() => unpublishedCount.value > 0)
/**
 * Lista/orden/props de bloques activos igual que `published_blocks_snapshot` y sin borradores.
 * Al publicar se recarga el snapshot → suele quedar en true y se desactiva «Volver a lo publicado».
 */
const isAlignedWithPublishedSnapshot = computed(
    () =>
        currentMatchesPublishedBaseline(currentSnapshotString.value, publishedSnapshot.value) &&
        orderMatchesPublishedBaseline(sortedBlocks.value, publishedSnapshot.value) &&
        !hasUnpublishedDrafts.value,
)
/** Solo tiene sentido restaurar si hay borradores o el editor difiere de la línea base publicada. */
const canPruneToPublished = computed(() => !isAlignedWithPublishedSnapshot.value)
const propsSaveTimers = new Map()
const PROPS_SAVE_DEBOUNCE_MS = 500

function extractSocialUrls(rawProps) {
    const list = rawProps?.links ?? rawProps?.items ?? []
    if (!Array.isArray(list)) return []
    return list
        .map((row) => String(row?.url ?? '').trim())
        .filter((url) => url.length > 0)
}

function logSavedSocialUrls(blockId, rawProps, source) {
    const urls = extractSocialUrls(rawProps)
    if (!urls.length) return
    console.log(`[social:url:saved][${source}] block=${blockId}`, urls)
}

async function handleAddType(type) {
    if (editorBusy.value) return
    addBlockError.value = ''
    try {
        await addBlock(type, defaultPropsForType(type))
        markDirty()
    } catch (error) {
        addBlockError.value = error?.message || 'No se pudo crear el bloque.'
    }
}

async function handleDelete(id) {
    if (editorBusy.value) return
    resetError.value = ''
    try {
        await removeBlock(id)
        markDirty()
    } catch (error) {
        resetError.value = error?.message || 'No se pudo eliminar el bloque.'
    }
}

async function handleToggle(id) {
    if (editorBusy.value) return
    await toggleBlock(id)
    markDirty()
}

async function handleDuplicate(id) {
    if (editorBusy.value) return
    await duplicateBlock(id)
    markDirty()
}

function upsertLocalBlockProps(id, newProps) {
    const idx = blocks.value.findIndex((b) => Number(b.id) === Number(id))
    if (idx === -1) return
    blocks.value[idx] = {
        ...blocks.value[idx],
        props: { ...(newProps || {}) },
    }
}

function clearPropsSaveTimer(id) {
    const current = propsSaveTimers.get(id)
    if (current == null) return
    clearTimeout(current)
    propsSaveTimers.delete(id)
}

function schedulePropsSave(id, newProps) {
    clearPropsSaveTimer(id)
    const timer = setTimeout(async () => {
        try {
            await updateBlock(id, newProps)
            logSavedSocialUrls(id, newProps, 'debounced')
        } catch (error) {
            resetError.value = error?.message || 'No se pudo guardar el bloque.'
        } finally {
            propsSaveTimers.delete(id)
        }
    }, PROPS_SAVE_DEBOUNCE_MS)
    propsSaveTimers.set(id, timer)
}

async function flushPendingPropsSaves() {
    const pendingIds = Array.from(propsSaveTimers.keys())
    if (!pendingIds.length) return

    for (const id of pendingIds) {
        clearPropsSaveTimer(id)
    }

    await Promise.all(
        pendingIds.map(async (id) => {
            const block = blocks.value.find((b) => Number(b.id) === Number(id))
            if (!block) return
            await updateBlock(id, block.props || {})
            logSavedSocialUrls(id, block.props || {}, 'flush-before-publish')
        }),
    )
}

function handleUpdateProps(id, newProps) {
    if (editorBusy.value) return
    upsertLocalBlockProps(id, newProps)
    markDirty()
    schedulePropsSave(id, newProps)
}

onBeforeUnmount(() => {
    for (const timer of propsSaveTimers.values()) {
        clearTimeout(timer)
    }
    propsSaveTimers.clear()
})

async function handlePublish() {
    if (!canPublish.value || editorBusy.value) return
    publishError.value = ''
    publishing.value = true
    try {
        // Ensure debounced prop updates are persisted before publishing snapshot.
        await flushPendingPropsSaves()
        const data = await publish()
        blocks.value = blocks.value.map((b) => (b.is_active ? { ...b, is_published: true } : b))
        if (data?.published_blocks_snapshot != null && data?.published_blocks_snapshot !== '') {
            publishedSnapshot.value = toSnapshotJsonString(data.published_blocks_snapshot)
        }
    } catch (error) {
        publishError.value = error?.message || 'No se pudo publicar.'
    } finally {
        publishing.value = false
    }
}

async function handleReorder(orderedIds) {
    if (editorBusy.value) return
    try {
        await reorderBlocks(orderedIds)
    } catch (error) {
        resetError.value = error?.message || 'No se pudo reordenar.'
    }
}

function closeBulkDialog() {
    bulkDialog.value = { ...bulkDialog.value, show: false }
}

/**
 * @param {'deleteAll' | 'pruneUnpublished'} variant
 */
function openBulkDialog(variant) {
    if (editorBusy.value) return
    if (variant === 'deleteAll' && !hasBlocks.value) return
    if (variant === 'pruneUnpublished' && !canPruneToPublished.value) return
    bulkDialog.value = { show: true, variant }
}

async function handleBulkConfirm() {
    const variant = bulkDialog.value.variant
    bulkDialog.value = { ...bulkDialog.value, show: false }
    resetError.value = ''
    resettingBlocks.value = true

    try {
        if (variant === 'deleteAll') {
            const data = await destroyAllBlocks()
            if (data?.published_blocks_snapshot != null && data?.published_blocks_snapshot !== '') {
                publishedSnapshot.value = toSnapshotJsonString(data.published_blocks_snapshot)
            }
        } else {
            const data = await pruneUnpublishedBlocks()
            if (data?.published_blocks_snapshot != null && data?.published_blocks_snapshot !== '') {
                publishedSnapshot.value = toSnapshotJsonString(data.published_blocks_snapshot)
            }
        }
        resetDirty()
    } catch (error) {
        resetError.value = error?.message || 'No se pudo completar la acción.'
    } finally {
        resettingBlocks.value = false
    }
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
                        v-if="canPublish"
                        class="order-first rounded-md bg-amber-50 px-3 py-2 text-center text-sm text-amber-800 sm:order-none sm:bg-transparent sm:px-0 sm:py-0"
                    >
                        Cambios sin publicar
                    </p>
                    <p
                        v-if="publishError"
                        class="order-first rounded-md border border-red-200 bg-red-50 px-3 py-2 text-center text-sm text-red-700 sm:order-none sm:text-left"
                    >
                        {{ publishError }}
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
                        <a
                            :href="draftUrl"
                            target="_blank"
                            rel="noopener"
                            class="flex min-h-11 touch-manipulation items-center justify-center rounded-lg border border-gray-200 bg-white px-4 text-center text-sm font-medium text-gray-700 shadow-sm active:bg-gray-50 sm:min-h-0 sm:border-0 sm:bg-transparent sm:px-2 sm:py-2 sm:shadow-none sm:underline"
                            title="Página completa con borradores (solo usuarios conectados)"
                        >
                            Vista previa borrador ↗
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
                        class="min-h-11 w-full touch-manipulation rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 enabled:active:bg-indigo-700 sm:w-auto sm:py-2 disabled:cursor-not-allowed disabled:opacity-50"
                        data-action="publish"
                        :disabled="!canPublish || editorBusy"
                        @click="handlePublish"
                    >
                        {{ publishing ? 'Publicando…' : 'Publicar' }}
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
                    <BlockCatalog
                        :schemas="blockSchemas"
                        :counts="blockTypeCounts"
                        :disabled="editorBusy"
                        @select="handleAddType"
                    />
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
                            <div class="flex flex-wrap items-center justify-end gap-2">
                                <button
                                    type="button"
                                    class="inline-flex min-h-10 items-center gap-1.5 rounded-md border border-amber-200 bg-white px-3 text-sm font-medium text-amber-900 hover:bg-amber-50 disabled:cursor-not-allowed disabled:opacity-50"
                                    :disabled="!canPruneToPublished || editorBusy"
                                    data-action="prune-unpublished-blocks"
                                    :title="
                                        'Línea base publicada: ' +
                                        (publishedBaselineRows == null
                                            ? 'sin snapshot'
                                            : publishedBaselineRows.length + ' bloque(s)') +
                                        '. Restaura lista, orden y contenido de bloques a esa publicación. Si coinciden y no hay borradores, este botón se desactiva.'
                                    "
                                    @click="openBulkDialog('pruneUnpublished')"
                                >
                                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path
                                            fill-rule="evenodd"
                                            d="M4 2a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v16a1 1 0 0 1-1.447.894L10 15.618l-4.553 2.276A1 1 0 0 1 4 17V2Zm2 1v12.382l3.553-1.776a1 1 0 0 1 .894 0L14 15.382V3H6Z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    Volver a lo publicado
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex min-h-10 items-center gap-2 rounded-md border border-red-200 bg-white px-3 text-sm font-medium text-red-700 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50"
                                    :disabled="!hasBlocks || editorBusy"
                                    data-action="delete-all-blocks"
                                    @click="openBulkDialog('deleteAll')"
                                >
                                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path
                                            fill-rule="evenodd"
                                            d="M8.5 2a1 1 0 0 0-1 1V4H5a1 1 0 1 0 0 2h.278l.58 9.29A2 2 0 0 0 7.854 17h4.292a2 2 0 0 0 1.996-1.71l.58-9.29H15a1 1 0 1 0 0-2h-2.5V3a1 1 0 0 0-1-1h-3Z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    Eliminar todos mis bloques
                                </button>
                            </div>
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
                            :disabled="editorBusy"
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
            :show="bulkDialog.show"
            :variant="bulkDialog.variant"
            :count="bulkDialog.variant === 'deleteAll' ? blockCount : unpublishedCount"
            :loading="resettingBlocks"
            @close="closeBulkDialog"
            @confirm="handleBulkConfirm"
        />
    </AuthenticatedLayout>
</template>
