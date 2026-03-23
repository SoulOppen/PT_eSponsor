<script setup>
import { ref } from 'vue'
import BlockCard from './BlockCard.vue'
import BlockEditor from './BlockEditor.vue'

const props = defineProps({
    blocks: { type: Array, required: true },
    blockSchemas: { type: Object, required: true },
    disabled: { type: Boolean, default: false },
})

const emit = defineEmits(['delete', 'toggle', 'duplicate', 'update-props', 'reorder'])

const expandedId = ref(null)
const draggingId = ref(null)
const dragOverId = ref(null)

function toggleExpand(id) {
    expandedId.value = expandedId.value === id ? null : id
}

function move(block, delta) {
    const ids = props.blocks.map((b) => b.id)
    const i = ids.indexOf(block.id)
    const j = i + delta
    if (i === -1 || j < 0 || j >= ids.length) return
    const next = [...ids]
    ;[next[i], next[j]] = [next[j], next[i]]
    emit('reorder', next)
}

function onDragStart(id, event) {
    if (props.disabled) return
    draggingId.value = id
    event.dataTransfer.effectAllowed = 'move'
    event.dataTransfer.setData('text/plain', String(id))
}

function onDragOver(id, event) {
    if (draggingId.value === null || draggingId.value === id) return
    event.preventDefault()
    dragOverId.value = id
}

function onDrop(targetId, event) {
    if (props.disabled) return
    event.preventDefault()
    const sourceId = Number(event.dataTransfer.getData('text/plain') || draggingId.value)
    if (!sourceId || sourceId === targetId) {
        dragOverId.value = null
        return
    }
    const ids = props.blocks.map((b) => b.id)
    const from = ids.indexOf(sourceId)
    const to = ids.indexOf(targetId)
    if (from === -1 || to === -1) {
        dragOverId.value = null
        return
    }
    const next = [...ids]
    const [moved] = next.splice(from, 1)
    next.splice(to, 0, moved)
    dragOverId.value = null
    emit('reorder', next)
}

function onDragEnd() {
    draggingId.value = null
    dragOverId.value = null
}
</script>

<template>
    <div class="block-list space-y-3 sm:space-y-4" :inert="disabled">
        <div
            v-for="block in blocks"
            :key="block.id"
            :draggable="!disabled"
            class="rounded-lg border border-gray-200 bg-white p-3 shadow-sm transition sm:p-4"
            :class="{
                'opacity-70': draggingId === block.id,
                'ring-2 ring-indigo-300': dragOverId === block.id,
            }"
            @dragstart="onDragStart(block.id, $event)"
            @dragover="onDragOver(block.id, $event)"
            @drop="onDrop(block.id, $event)"
            @dragend="onDragEnd"
        >
            <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-start sm:justify-between">
                <BlockCard
                    class="min-w-0 flex-1"
                    :block="block"
                    :disabled="disabled"
                    @delete="emit('delete', block.id)"
                    @toggle="emit('toggle', block.id)"
                    @duplicate="emit('duplicate', block.id)"
                />
                <div class="flex w-full shrink-0 gap-2 sm:w-auto">
                    <span
                        class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-500 sm:min-h-0 sm:min-w-0 sm:px-3 sm:py-2"
                        title="Arrastra para mover"
                        aria-label="Arrastra para mover"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M7 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 6a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm-1.5 7.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM16 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm-1.5 7.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Zm1.5 4.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" />
                        </svg>
                    </span>
                    <button
                        type="button"
                        class="min-h-11 flex-1 touch-manipulation rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 active:bg-gray-50 disabled:opacity-40 sm:flex-none"
                        data-action="move-up"
                        :disabled="disabled || blocks[0]?.id === block.id"
                        @click="move(block, -1)"
                    >
                        Subir
                    </button>
                    <button
                        type="button"
                        class="min-h-11 flex-1 touch-manipulation rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 active:bg-gray-50 disabled:opacity-40 sm:flex-none"
                        data-action="move-down"
                        :disabled="disabled || blocks[blocks.length - 1]?.id === block.id"
                        @click="move(block, 1)"
                    >
                        Bajar
                    </button>
                </div>
            </div>
            <button
                v-if="blockSchemas[block.type]?.fields?.length"
                type="button"
                class="mt-3 flex min-h-11 w-full touch-manipulation items-center justify-center rounded-lg text-sm font-medium text-indigo-600 ring-1 ring-indigo-200 active:bg-indigo-50 enabled:cursor-pointer disabled:cursor-not-allowed disabled:opacity-50 sm:mt-2 sm:w-auto sm:justify-start sm:bg-transparent sm:px-0 sm:py-2 sm:ring-0"
                data-action="expand-editor"
                :disabled="disabled"
                @click="toggleExpand(block.id)"
            >
                {{ expandedId === block.id ? 'Ocultar editor' : 'Editar contenido' }}
            </button>
            <BlockEditor
                v-if="expandedId === block.id && blockSchemas[block.type]"
                class="mt-4 w-full min-w-0"
                :schema="blockSchemas[block.type]"
                :model-value="block.props || {}"
                :disabled="disabled"
                @update:model-value="emit('update-props', block.id, $event)"
            />
        </div>
        <p v-if="!blocks.length" class="text-sm text-gray-500">Aún no hay bloques. Añade uno desde el catálogo.</p>
    </div>
</template>
