<script setup>
const props = defineProps({
    field: { type: Object, required: true },
    modelValue: { type: [String, Number, Boolean, null], default: '' },
})

const emit = defineEmits(['update:modelValue'])

function onInput(e) {
    emit('update:modelValue', e.target.value)
}
</script>

<template>
    <div class="field-renderer">
        <label v-if="field.label" class="sr-only">{{ field.label }}</label>
        <input
            v-if="field.type === 'text'"
            type="text"
            :name="field.key"
            :value="modelValue"
            @input="onInput"
        />
        <textarea
            v-else-if="field.type === 'textarea'"
            :name="field.key"
            :value="modelValue"
            @input="onInput"
        />
        <input
            v-else-if="field.type === 'url'"
            type="url"
            :name="field.key"
            :value="modelValue"
            @input="onInput"
        />
        <input
            v-else-if="field.type === 'color'"
            type="color"
            :name="field.key"
            :value="modelValue || '#000000'"
            @input="onInput"
        />
        <select
            v-else-if="field.type === 'select'"
            :name="field.key"
            :value="modelValue"
            @change="onInput"
        >
            <option v-for="opt in field.options || []" :key="opt" :value="opt">
                {{ opt }}
            </option>
        </select>
        <input
            v-else
            type="text"
            :name="field.key"
            :value="modelValue"
            @input="onInput"
        />
    </div>
</template>
