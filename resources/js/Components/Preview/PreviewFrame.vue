<script setup>
import { computed } from 'vue'
import BlockText from '@/Components/Blocks/BlockText.vue'
import BlockImage from '@/Components/Blocks/BlockImage.vue'
import BlockLinks from '@/Components/Blocks/BlockLinks.vue'
import BlockVideo from '@/Components/Blocks/BlockVideo.vue'
import BlockSocial from '@/Components/Blocks/BlockSocial.vue'
import BlockMusic from '@/Components/Blocks/BlockMusic.vue'

const BlockByType = {
    text: BlockText,
    image: BlockImage,
    links: BlockLinks,
    video: BlockVideo,
    social: BlockSocial,
    music: BlockMusic,
}

const props = defineProps({
    blocks: { type: Array, required: true },
    site: { type: Object, required: true },
})

const visibleBlocks = computed(() =>
    [...props.blocks]
        .filter((b) => b.is_active)
        .sort((a, b) => (a.order ?? 0) - (b.order ?? 0)),
)

function rendererFor(type) {
    return BlockByType[type] ?? null
}
</script>

<template>
    <div
        class="preview-frame w-full min-w-0 max-w-full overflow-x-auto break-words text-gray-900 [&_iframe]:max-w-full [&_img]:max-w-full [&_img]:h-auto [&_img]:rounded-md"
    >
        <header v-if="site?.name || site?.bio || site?.avatar_url" class="mb-4 border-b border-gray-200 pb-4">
            <div class="flex items-start gap-3">
                <img
                    v-if="site?.avatar_url"
                    :src="site.avatar_url"
                    alt="Avatar del sitio"
                    class="h-12 w-12 shrink-0 rounded-full object-cover ring-1 ring-gray-200"
                />
                <div class="min-w-0">
                    <h4 v-if="site?.name" class="truncate text-lg font-semibold text-gray-900">
                        {{ site.name }}
                    </h4>
                    <p v-if="site?.bio" class="mt-1 text-sm text-gray-600">
                        {{ site.bio }}
                    </p>
                </div>
            </div>
        </header>

        <template v-for="b in visibleBlocks" :key="b.id">
            <component
                :is="rendererFor(b.type)"
                v-if="rendererFor(b.type)"
                :props="b.props || {}"
                :data-block-type="b.type"
            />
        </template>
    </div>
</template>
