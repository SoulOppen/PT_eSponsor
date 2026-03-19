<script setup>
import { computed } from 'vue'

const { props: blockProps } = defineProps({
    props: { type: Object, required: true },
})

const isTikTok = computed(() => (blockProps.url || '').includes('tiktok.com'))

const youtubeEmbedSrc = computed(() => {
    const u = blockProps.url || ''
    let id = null
    const watch = u.match(/[?&]v=([^&]+)/)
    if (watch) {
        id = watch[1]
    }
    const short = u.match(/youtu\.be\/([^?]+)/)
    if (short) {
        id = short[1]
    }
    if (id) {
        return `https://www.youtube.com/embed/${id}`
    }

    return u
})
</script>

<template>
    <div class="block-video">
        <div v-if="isTikTok" data-tiktok class="block-video-tiktok">
            <iframe :src="blockProps.url" title="TikTok" width="100%" height="480" style="border: 0" />
        </div>
        <iframe
            v-else
            :src="youtubeEmbedSrc"
            title="Video"
            width="100%"
            height="480"
            style="border: 0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; picture-in-picture"
        />
    </div>
</template>
