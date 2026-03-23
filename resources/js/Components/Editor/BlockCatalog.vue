<script setup>
defineProps({
    schemas: { type: Object, required: true },
    counts: { type: Object, default: () => ({}) },
    disabled: { type: Boolean, default: false },
})

defineEmits(['select'])
</script>

<template>
    <!-- Mobile-first: 2 columnas en móvil, más en pantallas grandes -->
    <div
        class="block-catalog grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4"
        :inert="disabled"
    >
        <button
            v-for="(schema, type) in schemas"
            :key="type"
            type="button"
            :data-block-type="type"
            :disabled="disabled"
            class="relative min-h-11 touch-manipulation rounded-lg border border-gray-200 bg-white px-3 py-3 pr-10 text-left text-sm font-medium leading-tight text-gray-800 shadow-sm active:border-indigo-300 active:bg-indigo-50 enabled:cursor-pointer disabled:cursor-not-allowed disabled:opacity-50 sm:min-h-12 sm:px-4 sm:pr-12 sm:text-base"
            @click="$emit('select', type)"
        >
            {{ schema.label }}
            <span
                class="pointer-events-none absolute right-2 top-2 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-sky-200 px-1.5 text-[11px] font-bold text-sky-900 sm:right-3 sm:top-3"
            >
                {{ counts[type] || 0 }}
            </span>
        </button>
    </div>
</template>
