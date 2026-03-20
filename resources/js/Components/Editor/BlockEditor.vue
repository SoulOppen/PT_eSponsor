<script setup>
import { computed } from 'vue'
import FieldRenderer from './FieldRenderer.vue'

const props = defineProps({
    schema: { type: Object, required: true },
    modelValue: { type: Object, required: true },
})

const emit = defineEmits(['update:modelValue'])

const fields = computed(() => props.schema.fields || [])

function updateKey(key, val) {
    emit('update:modelValue', { ...props.modelValue, [key]: val })
}

function valueForField(f) {
    const mv = props.modelValue || {}
    if (Object.prototype.hasOwnProperty.call(mv, f.key)) {
        return mv[f.key]
    }
    if (f.type === 'repeater') {
        return []
    }
    if (f.type === 'select' && f.options?.length) {
        return f.options[0]
    }
    if (f.type === 'color') {
        return f.default || '#000000'
    }
    return ''
}
</script>

<template>
    <div class="block-editor w-full min-w-0 space-y-4">
        <FieldRenderer
            v-for="f in fields"
            :key="f.key"
            :field="f"
            :model-value="valueForField(f)"
            @update:model-value="(v) => updateKey(f.key, v)"
        />
    </div>
</template>
