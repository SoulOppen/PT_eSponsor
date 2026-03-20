<script setup>
const { props: blockProps } = defineProps({
    props: { type: Object, required: true },
})
</script>

<template>
    <nav
        v-if="(blockProps.links || []).length"
        class="block-social mb-3 flex flex-wrap gap-3 text-sm"
        aria-label="Redes sociales"
    >
        <a
            v-for="(item, index) in blockProps.links || []"
            :key="index"
            class="font-medium text-indigo-600 underline"
            :href="item.url"
            rel="noopener noreferrer"
        >
            {{ item.label || item.url }}
        </a>
    </nav>
</template>
