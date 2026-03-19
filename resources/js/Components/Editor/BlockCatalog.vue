<script setup>
defineProps({
    schemas: { type: Object, required: true },
})

defineEmits(['select'])
</script>

<template>
    <div class="block-catalog">
        <button
            v-for="(schema, type) in schemas"
            :key="type"
            type="button"
            :data-block-type="type"
            @click="$emit('select', type)"
        >
            {{ schema.label }}
        </button>
    </div>
</template>
