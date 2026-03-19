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
    <div class="preview-frame">
        <template v-for="b in visibleBlocks" :key="b.id">
            <div v-if="b.type === 'text'" :data-block-type="b.type">
                {{ b.props?.content }}
            </div>
            <div v-else-if="b.type === 'image'" :data-block-type="b.type">
                <img :src="b.props?.url" :alt="b.props?.alt || ''" />
            </div>
        </template>
    </div>
</template>
