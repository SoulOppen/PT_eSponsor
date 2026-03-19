<script setup>
defineProps({
    site: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <div>
        <input name="name" type="text" :value="site.name" readonly />
        <input name="slug" type="text" :value="site.slug" readonly />
        <textarea name="bio" :value="site.bio" readonly />
        <img v-if="site.avatar_url" :src="site.avatar_url" alt="" />
    </div>
</template>
