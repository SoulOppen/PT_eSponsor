<script setup>
import { ref } from 'vue'
import BlockCard from './BlockCard.vue'
import BlockEditor from './BlockEditor.vue'

defineProps({
    blocks: { type: Array, required: true },
    blockSchemas: { type: Object, required: true },
})

const emit = defineEmits(['delete', 'toggle', 'duplicate', 'update-props'])

const expandedId = ref(null)

function toggleExpand(id) {
    expandedId.value = expandedId.value === id ? null : id
}
</script>

<template>
    <div class="block-list space-y-3 sm:space-y-4">
        <div
            v-for="block in blocks"
            :key="block.id"
            class="rounded-lg border border-gray-200 bg-white p-3 shadow-sm sm:p-4"
        >
            <BlockCard
                :block="block"
                @delete="emit('delete', block.id)"
                @toggle="emit('toggle', block.id)"
                @duplicate="emit('duplicate', block.id)"
            />
            <button
                v-if="blockSchemas[block.type]?.fields?.length"
                type="button"
                class="mt-3 flex min-h-11 w-full touch-manipulation items-center justify-center rounded-lg text-sm font-medium text-indigo-600 ring-1 ring-indigo-200 active:bg-indigo-50 sm:mt-2 sm:w-auto sm:justify-start sm:bg-transparent sm:px-0 sm:py-2 sm:ring-0"
                data-action="expand-editor"
                @click="toggleExpand(block.id)"
            >
                {{ expandedId === block.id ? 'Ocultar editor' : 'Editar contenido' }}
            </button>
            <BlockEditor
                v-if="expandedId === block.id && blockSchemas[block.type]"
                class="mt-4 w-full min-w-0"
                :schema="blockSchemas[block.type]"
                :model-value="block.props || {}"
                @update:model-value="emit('update-props', block.id, $event)"
            />
        </div>
        <p v-if="!blocks.length" class="text-sm text-gray-500">Aún no hay bloques. Añade uno desde el catálogo.</p>
    </div>
</template>
