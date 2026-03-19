<script setup>
defineProps({
    block: { type: Object, required: true },
})

defineEmits(['delete', 'toggle', 'duplicate'])
</script>

<template>
    <div class="block-card">
        <span>{{ block.type }}</span>
        <span v-if="!block.is_active" data-inactive>Oculto</span>
        <button type="button" data-action="delete" @click="$emit('delete')">Eliminar</button>
        <button type="button" data-action="toggle" @click="$emit('toggle')">Activo</button>
        <button type="button" data-action="duplicate" @click="$emit('duplicate')">Duplicar</button>
    </div>
</template>
