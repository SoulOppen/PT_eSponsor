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
        <div class="flex items-center gap-2 sm:justify-end">
            <button
                type="button"
                data-action="delete"
                title="Eliminar bloque"
                aria-label="Eliminar bloque"
                class="inline-flex min-h-11 min-w-11 touch-manipulation items-center justify-center rounded-lg border border-red-200 bg-red-50 px-3 text-red-800 active:bg-red-100 sm:min-h-0 sm:min-w-0 sm:px-3 sm:py-2"
                @click="$emit('delete')"
            >
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path
                        fill-rule="evenodd"
                        d="M8.5 2a1 1 0 0 0-1 1V4H5a1 1 0 1 0 0 2h.278l.58 9.29A2 2 0 0 0 7.854 17h4.292a2 2 0 0 0 1.996-1.71l.58-9.29H15a1 1 0 1 0 0-2h-2.5V3a1 1 0 0 0-1-1h-3ZM9.5 4v-.5h1V4h-1Zm-1 3a.75.75 0 0 1 .75.75v6a.75.75 0 0 1-1.5 0v-6A.75.75 0 0 1 8.5 7Zm3 .75a.75.75 0 0 0-1.5 0v6a.75.75 0 0 0 1.5 0v-6Z"
                        clip-rule="evenodd"
                    />
                </svg>
            </button>
            <button
                type="button"
                data-action="toggle"
                :title="block.is_active ? 'Ocultar bloque' : 'Activar bloque'"
                :aria-label="block.is_active ? 'Ocultar bloque' : 'Activar bloque'"
                class="inline-flex min-h-11 min-w-11 touch-manipulation items-center justify-center rounded-lg border border-gray-200 bg-gray-50 px-3 text-gray-800 active:bg-gray-100 sm:min-h-0 sm:min-w-0 sm:px-3 sm:py-2"
                @click="$emit('toggle')"
            >
                <svg v-if="block.is_active" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M10 3c4.405 0 8.05 2.87 9.21 6.842a1 1 0 0 1 0 .316C18.05 14.13 14.405 17 10 17c-4.405 0-8.05-2.87-9.21-6.842a1 1 0 0 1 0-.316C1.95 5.87 5.595 3 10 3Zm0 3.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Z" />
                </svg>
                <svg v-else class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M4.707 3.293a1 1 0 0 0-1.414 1.414l11 11a1 1 0 0 0 1.414-1.414l-1.321-1.322c2.251-1.09 3.913-3.042 4.824-6.16a1 1 0 0 0 0-.316C18.05 2.87 14.405 0 10 0 7.93 0 6.03.62 4.45 1.73L4.707 3.293Z" />
                    <path d="M2.1 6.01A13.88 13.88 0 0 0 .79 9.842a1 1 0 0 0 0 .316C1.95 14.13 5.595 17 10 17c1.54 0 2.99-.35 4.274-.99l-2.02-2.02A3.5 3.5 0 0 1 6.01 7.746L2.1 6.01Z" />
                </svg>
            </button>
            <button
                type="button"
                data-action="duplicate"
                title="Duplicar bloque"
                aria-label="Duplicar bloque"
                class="inline-flex min-h-11 min-w-11 touch-manipulation items-center justify-center rounded-lg border border-indigo-200 bg-indigo-50 px-3 text-indigo-800 active:bg-indigo-100 sm:min-h-0 sm:min-w-0 sm:px-3 sm:py-2"
                @click="$emit('duplicate')"
            >
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M6 2a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H6Z" />
                    <path d="M3 6a1 1 0 0 1 1 1v8h8a1 1 0 1 1 0 2H4a2 2 0 0 1-2-2V7a1 1 0 0 1 1-1Z" />
                </svg>
            </button>
        </div>
    </div>
</template>
