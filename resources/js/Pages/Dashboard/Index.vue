<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import BlockCatalog from '@/Components/Editor/BlockCatalog.vue'
import BlockList from '@/Components/Editor/BlockList.vue'
import PreviewFrame from '@/Components/Preview/PreviewFrame.vue'
import { useBlocks } from '@/composables/useBlocks'
import { usePublish } from '@/composables/usePublish'
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'

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
                row[s.key] = s.type === 'url' ? 'https://example.com' : 'Nuevo'
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

const publicUrl = computed(() => `/@${props.site.slug}`)

async function handleAddType(type) {
    await addBlock(type, defaultPropsForType(type))
    markDirty()
}

async function handleDelete(id) {
    await removeBlock(id)
    markDirty()
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
                    <BlockCatalog :schemas="blockSchemas" @select="handleAddType" />
                </section>

                <!-- Una columna por defecto; dos desde lg -->
                <div class="grid gap-8 lg:grid-cols-2 lg:gap-10">
                    <section class="min-w-0">
                        <h3 class="mb-3 text-base font-semibold text-gray-900 sm:text-lg">Tus bloques</h3>
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
                    <section class="min-w-0">
                        <h3 class="mb-3 text-base font-semibold text-gray-900 sm:text-lg">Vista previa</h3>
                        <div class="overflow-x-auto rounded-lg border border-dashed border-gray-300 bg-gray-50 p-3 sm:p-4">
                            <PreviewFrame :blocks="sortedBlocks" :site="site" />
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
