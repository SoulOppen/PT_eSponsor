<script setup>
import { computed } from 'vue'

const { props: blockProps } = defineProps({
    props: { type: Object, required: true },
})

const linkItems = computed(() => {
    const raw = blockProps.items ?? []
    if (!Array.isArray(raw)) return []
    return raw
        .map((item) => {
            const url = String(item?.url ?? '').trim()
            if (!url) return null
            return {
                label: item?.label || url,
                href: url,
            }
        })
        .filter(Boolean)
})
</script>

<template>
    <section
        class="block-links"
        :style="{
            backgroundColor: blockProps.color || '#ffffff',
            color: blockProps.text_color || '#000000',
        }"
    >
        <h2 class="block-links__title">{{ blockProps.title }}</h2>
        <nav class="block-links__nav">
            <a v-for="(item, index) in linkItems" :key="index" :href="item.href" class="block-links__link">
                {{ item.label }}
            </a>
        </nav>
    </section>
</template>
