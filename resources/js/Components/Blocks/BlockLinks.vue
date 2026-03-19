<script setup>
const { props: blockProps } = defineProps({
    props: { type: Object, required: true },
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
        <h2>{{ blockProps.title }}</h2>
        <nav>
            <a v-for="(item, index) in blockProps.items || []" :key="index" :href="item.url">
                {{ item.label }}
            </a>
        </nav>
    </section>
</template>
