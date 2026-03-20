<script setup>
const { props: blockProps } = defineProps({
    props: { type: Object, required: true },
})
</script>

<template>
    <figure v-if="blockProps.url" class="block-image mb-3">
        <img
            class="block max-h-[60vh] w-auto object-contain"
            :src="blockProps.url"
            :alt="blockProps.alt || ''"
            loading="lazy"
        />
    </figure>
</template>
