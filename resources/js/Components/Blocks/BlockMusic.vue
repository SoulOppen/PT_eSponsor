<script setup>
import { computed } from 'vue'

const { props: blockProps } = defineProps({
    props: { type: Object, required: true },
})

const iframeSrc = computed(() => {
    const platform = String(blockProps.platform || '').toLowerCase()
    const url = blockProps.url || ''

    if (platform === 'spotify') {
        const m = url.match(/track\/([a-zA-Z0-9]+)/)
        if (m) {
            return `https://open.spotify.com/embed/track/${m[1]}`
        }

        return url.includes('embed') ? url : url.replace('open.spotify.com/track/', 'open.spotify.com/embed/track/')
    }

    if (platform === 'bandcamp') {
        return `https://bandcamp.com/EmbeddedPlayer/size=large/bgcol=ffffff/linkcol=0687f5/track=0/${encodeURIComponent(url)}`
    }

    return url
})
</script>

<template>
    <div class="block-music">
        <iframe
            :src="iframeSrc"
            width="100%"
            height="380"
            style="border: 0"
            allow="encrypted-media"
        />
    </div>
</template>
