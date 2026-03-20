<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0"
    >
        <div class="flex flex-col items-center gap-3">
            <ApplicationLogo class="h-20 w-20 fill-current text-gray-500" />
        </div>

        <div
            class="mt-6 w-full overflow-hidden bg-white px-8 py-3 sm:px-10 sm:py-4 shadow-md sm:max-w-md sm:rounded-lg"
        >
            <slot />
        </div>
    </div>
</template>

