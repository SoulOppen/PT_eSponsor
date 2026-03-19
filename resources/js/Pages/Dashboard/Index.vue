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
            out[f.key] = []
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

const { sortedBlocks, addBlock, removeBlock, toggleBlock, duplicateBlock, updateBlock } =
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
</script>

<template>
    <Head title="Editor de página" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Editor de página</h2>
                <div class="flex flex-wrap items-center gap-3">
                    <span v-if="isDirty" class="text-sm text-amber-600">Cambios sin publicar</span>
                    <a
                        :href="publicUrl"
                        target="_blank"
                        rel="noopener"
                        class="text-sm text-gray-600 underline hover:text-gray-900"
                    >
                        Ver público ↗
                    </a>
                    <Link
                        href="/dashboard/settings"
                        class="text-sm text-gray-600 underline hover:text-gray-900"
                    >
                        Ajustes del sitio
                    </Link>
                    <button
                        type="button"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        data-action="publish"
                        @click="handlePublish"
                    >
                        Publicar
                    </button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-10 sm:px-6 lg:px-8">
                <section>
                    <h3 class="mb-3 text-lg font-medium text-gray-900">Añadir bloque</h3>
                    <BlockCatalog :schemas="blockSchemas" @select="handleAddType" />
                </section>

                <div class="grid gap-10 lg:grid-cols-2">
                    <section>
                        <h3 class="mb-3 text-lg font-medium text-gray-900">Tus bloques</h3>
                        <BlockList
                            :blocks="sortedBlocks"
                            :block-schemas="blockSchemas"
                            @delete="handleDelete"
                            @toggle="handleToggle"
                            @duplicate="handleDuplicate"
                            @update-props="handleUpdateProps"
                        />
                    </section>
                    <section>
                        <h3 class="mb-3 text-lg font-medium text-gray-900">Vista previa</h3>
                        <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4">
                            <PreviewFrame :blocks="sortedBlocks" :site="site" />
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
