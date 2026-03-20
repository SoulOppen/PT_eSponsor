<script setup>
import { computed } from 'vue'

const props = defineProps({
    field: { type: Object, required: true },
    modelValue: { type: [String, Number, Boolean, Array, Object, null], default: null },
})

const emit = defineEmits(['update:modelValue'])

const controlClass =
    'mt-1 w-full min-w-0 touch-manipulation rounded-md border border-gray-300 px-3 py-2 text-base text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:min-h-0 sm:text-sm'

const textLikeClass = `${controlClass} min-h-11 sm:h-10`
const textareaClass = `${controlClass} min-h-28 resize-y sm:min-h-24`
const colorClass =
    'mt-1 h-11 w-full max-w-full min-w-0 touch-manipulation cursor-pointer rounded-md border border-gray-300 bg-white px-1 py-1 sm:h-10'

const repeaterRows = computed(() => (Array.isArray(props.modelValue) ? props.modelValue : []))

function onInput(e) {
    emit('update:modelValue', e.target.value)
}

function emptyRowFromSubfields() {
    const row = {}
    for (const s of props.field.subfields || []) {
        row[s.key] = ''
    }
    return row
}

function emitRows(next) {
    emit('update:modelValue', next)
}

function addRepeaterRow() {
    emitRows([...repeaterRows.value, emptyRowFromSubfields()])
}

function removeRepeaterRow(index) {
    const next = repeaterRows.value.filter((_, i) => i !== index)
    emitRows(next)
}

function updateSubfield(rowIndex, subKey, value) {
    const next = repeaterRows.value.map((row, i) =>
        i === rowIndex ? { ...row, [subKey]: value } : { ...row },
    )
    emitRows(next)
}
</script>

<template>
    <div class="field-renderer w-full min-w-0">
        <label v-if="field.label" class="mb-1 block text-sm font-medium text-gray-700">{{ field.label }}</label>

        <div v-if="field.type === 'repeater'" class="space-y-3 rounded-md border border-gray-200 bg-gray-50 p-3">
            <div
                v-for="(row, rowIndex) in repeaterRows"
                :key="rowIndex"
                class="rounded-md border border-gray-200 bg-white p-3 shadow-sm"
            >
                <div v-for="sub in field.subfields || []" :key="sub.key" class="mb-3 last:mb-0">
                    <label class="mb-1 block text-xs font-medium text-gray-600">{{ sub.label }}</label>
                    <input
                        v-if="sub.type === 'text' || sub.type === 'url'"
                        :type="sub.type === 'url' ? 'url' : 'text'"
                        :class="textLikeClass"
                        :name="`${field.key}.${rowIndex}.${sub.key}`"
                        :value="row[sub.key] ?? ''"
                        @input="updateSubfield(rowIndex, sub.key, $event.target.value)"
                    />
                    <textarea
                        v-else-if="sub.type === 'textarea'"
                        :class="textareaClass"
                        :value="row[sub.key] ?? ''"
                        @input="updateSubfield(rowIndex, sub.key, $event.target.value)"
                    />
                </div>


                <button
                    type="button"
                    class="min-h-9 w-full touch-manipulation rounded-md text-sm font-medium text-red-700 ring-1 ring-red-200 active:bg-red-50 sm:w-auto sm:px-3"
                    data-action="repeater-remove"
                    @click="removeRepeaterRow(rowIndex)"
                >
                    Quitar fila
                </button>
            </div>
            <button
                type="button"
                class="min-h-11 w-full touch-manipulation rounded-lg bg-indigo-600 px-3 text-sm font-semibold text-white active:bg-indigo-700"
                data-action="repeater-add"
                @click="addRepeaterRow"
            >
                Añadir fila
            </button>
        </div>

        <input
            v-else-if="field.type === 'text'"
            type="text"
            :class="textLikeClass"
            :name="field.key"
            :value="modelValue ?? ''"
            @input="onInput"
        />
        <textarea
            v-else-if="field.type === 'textarea'"
            :class="textareaClass"
            :name="field.key"
            :value="modelValue ?? ''"
            @input="onInput"
        />
        <input
            v-else-if="field.type === 'url'"
            type="url"
            :class="textLikeClass"
            inputmode="url"
            autocomplete="url"
            :name="field.key"
            :value="modelValue ?? ''"
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
            :value="modelValue ?? ''"
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
            :value="modelValue ?? ''"
            @input="onInput"
        />
    </div>
</template>
