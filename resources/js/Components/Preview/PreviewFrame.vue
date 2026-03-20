<script setup>
import { computed } from 'vue'

const props = defineProps({
    blocks: { type: Array, required: true },
    site: { type: Object, required: true },
})

const visibleBlocks = computed(() =>
    [...props.blocks]
        .filter((b) => b.is_active)
        .sort((a, b) => (a.order ?? 0) - (b.order ?? 0)),
)
</script>

<template>
    <div
        class="preview-frame w-full min-w-0 max-w-full overflow-x-auto break-words text-gray-900 [&_img]:max-w-full [&_img]:h-auto [&_img]:rounded-md"
    >
        <template v-for="b in visibleBlocks" :key="b.id">
            <div v-if="b.type === 'text'" class="mb-3 text-base leading-relaxed sm:text-sm" :data-block-type="b.type">
                {{ b.props?.content }}
            </div>
            <div v-else-if="b.type === 'image'" class="mb-3" :data-block-type="b.type">
                <img class="block max-h-[60vh] w-auto object-contain" :src="b.props?.url" :alt="b.props?.alt || ''" />
            </div>
        </template>
    </div>
</template>
