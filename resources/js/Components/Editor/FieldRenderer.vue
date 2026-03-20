<script setup>
const props = defineProps({
    field: { type: Object, required: true },
    modelValue: { type: [String, Number, Boolean, null], default: '' },
})

const emit = defineEmits(['update:modelValue'])

const controlClass =
    'mt-1 w-full min-w-0 touch-manipulation rounded-md border border-gray-300 px-3 py-2 text-base text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:min-h-0 sm:text-sm'

const textLikeClass = `${controlClass} min-h-11 sm:h-10`
const textareaClass = `${controlClass} min-h-28 resize-y sm:min-h-24`
const colorClass =
    'mt-1 h-11 w-full max-w-full min-w-0 touch-manipulation cursor-pointer rounded-md border border-gray-300 bg-white px-1 py-1 sm:h-10'

function onInput(e) {
    emit('update:modelValue', e.target.value)
}
</script>

<template>
    <div class="field-renderer w-full min-w-0">
        <label v-if="field.label" class="mb-1 block text-sm font-medium text-gray-700">{{ field.label }}</label>
        <input
            v-if="field.type === 'text'"
            type="text"
            :class="textLikeClass"
            :name="field.key"
            :value="modelValue"
            @input="onInput"
        />
        <textarea
            v-else-if="field.type === 'textarea'"
            :class="textareaClass"
            :name="field.key"
            :value="modelValue"
            @input="onInput"
        />
        <input
            v-else-if="field.type === 'url'"
            type="url"
            :class="textLikeClass"
            inputmode="url"
            autocomplete="url"
            :name="field.key"
            :value="modelValue"
            @input="onInput"
        />
        <input
            v-else-if="field.type === 'color'"
            type="color"
            :class="colorClass"
            :name="field.key"
            :value="modelValue || '#000000'"
            @input="onInput"
        />
        <select
            v-else-if="field.type === 'select'"
            :class="`${textLikeClass} bg-white`"
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
            :class="textLikeClass"
            :name="field.key"
            :value="modelValue"
            @input="onInput"
        />
    </div>
</template>
