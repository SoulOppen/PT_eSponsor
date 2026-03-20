<script setup>
defineProps({
    schemas: { type: Object, required: true },
})

defineEmits(['select'])
</script>

<template>
    <!-- Mobile-first: 2 columnas en móvil, más en pantallas grandes -->
    <div class="block-catalog grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4">
        <button
            v-for="(schema, type) in schemas"
            :key="type"
            type="button"
            :data-block-type="type"
            class="min-h-11 touch-manipulation rounded-lg border border-gray-200 bg-white px-3 py-3 text-left text-sm font-medium leading-tight text-gray-800 shadow-sm active:border-indigo-300 active:bg-indigo-50 sm:min-h-12 sm:px-4 sm:text-base"
            @click="$emit('select', type)"
        >
            {{ schema.label }}
        </button>
    </div>
</template>
