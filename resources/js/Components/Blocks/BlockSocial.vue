<script setup>
const NETWORK_LABELS = {
    instagram: 'Instagram',
    tiktok: 'TikTok',
    youtube: 'YouTube',
    facebook: 'Facebook',
    x: 'X',
}

const { props: blockProps } = defineProps({
    props: { type: Object, required: true },
})

function socialLabel(item) {
    if (item.network === 'otra' && item.custom_network) return item.custom_network
    if (item.network && NETWORK_LABELS[item.network]) return NETWORK_LABELS[item.network]
    return item.label || item.url
}
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
            {{ socialLabel(item) }}
        </a>
    </nav>
</template>
