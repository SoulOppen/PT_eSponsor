<script setup>
const { props: blockProps } = defineProps({
    props: { type: Object, required: true },
})
</script>

<template>
    <div
        v-if="blockProps.content"
        class="block-text mb-3 text-base leading-relaxed sm:text-sm"
        :style="{ textAlign: blockProps.align || 'left' }"
    >
        {{ blockProps.content }}
    </div>
</template>
