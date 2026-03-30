<script setup>
import { computed } from 'vue'

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

const socialItems = computed(() => {
    const raw = blockProps.links ?? blockProps.items ?? []
    if (!Array.isArray(raw)) return []
    return raw.filter((item) => item && item.url)
})

function socialLabel(item) {
    if (item.network === 'otra' && item.custom_network) return item.custom_network
    if (item.network && NETWORK_LABELS[item.network]) return NETWORK_LABELS[item.network]
    return item.label || item.url
}

function socialNetwork(item) {
    return item?.network && typeof item.network === 'string' ? item.network : 'other'
}

function socialHref(item) {
    const raw = String(item?.url ?? '').trim()
    if (!raw) return '#'
    if (/^https?:\/\//i.test(raw)) return raw
    if (raw.startsWith('//')) return `https:${raw}`
    if (/^[a-z][a-z0-9+.-]*:/i.test(raw)) return raw
    if (raw.startsWith('/')) return raw
    return `https://${raw}`
}
</script>

<template>
    <nav
        v-if="socialItems.length"
        class="block-social mb-3 text-sm"
        aria-label="Redes sociales"
    >
        <a
            v-for="(item, index) in socialItems"
            :key="index"
            class="block-social__link"
            :data-network="socialNetwork(item)"
            :href="socialHref(item)"
            target="_blank"
            rel="noopener noreferrer nofollow"
        >
            {{ socialLabel(item) }}
        </a>
    </nav>
</template>
