<script setup>
defineProps({
    block: { type: Object, required: true },
})

defineEmits(['delete', 'toggle', 'duplicate'])
</script>

<template>
    <div class="block-card flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
        <div class="flex min-w-0 flex-wrap items-center gap-2">
            <span class="font-mono text-sm font-medium capitalize text-gray-900">{{ block.type }}</span>
            <span v-if="!block.is_active" data-inactive class="rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-600"
                >Oculto</span
            >
        </div>
        <!-- Botones táctiles: grid 2 cols en móvil, fila en desktop -->
        <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:justify-end">
            <button
                type="button"
                data-action="delete"
                class="min-h-11 touch-manipulation rounded-lg border border-red-200 bg-red-50 px-3 text-sm font-medium text-red-800 active:bg-red-100 sm:min-h-0 sm:px-3 sm:py-2"
                @click="$emit('delete')"
            >
                Eliminar
            </button>
            <button
                type="button"
                data-action="toggle"
                class="min-h-11 touch-manipulation rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm font-medium text-gray-800 active:bg-gray-100 sm:min-h-0 sm:px-3 sm:py-2"
                @click="$emit('toggle')"
            >
                Activo
            </button>
            <button
                type="button"
                data-action="duplicate"
                class="col-span-2 min-h-11 touch-manipulation rounded-lg border border-indigo-200 bg-indigo-50 px-3 text-sm font-medium text-indigo-800 active:bg-indigo-100 sm:col-span-1 sm:min-h-0 sm:px-3 sm:py-2"
                @click="$emit('duplicate')"
            >
                Duplicar
            </button>
        </div>
    </div>
</template>
