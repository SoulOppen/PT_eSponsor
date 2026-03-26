<script setup>
import { computed, onBeforeUnmount } from 'vue'

const props = defineProps({
    field: { type: Object, required: true },
    modelValue: { type: [String, Number, Boolean, Array, Object, null], default: null },
    disabled: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

const controlClass =
    'mt-1 w-full min-w-0 touch-manipulation rounded-md border border-gray-300 px-3 py-2 text-base text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:min-h-0 sm:text-sm'

const textLikeClass = `${controlClass} min-h-11 sm:h-10`
const textareaClass = `${controlClass} min-h-28 resize-y sm:min-h-24`
const colorClass =
    'mt-1 h-11 w-full max-w-full min-w-0 touch-manipulation cursor-pointer rounded-md border border-gray-300 bg-white px-1 py-1 sm:h-10'
const TEXT_INPUT_DEBOUNCE_MS = 500
const URL_INPUT_DEBOUNCE_MS = 300
const textInputTimers = new Map()

const repeaterRows = computed(() => (Array.isArray(props.modelValue) ? props.modelValue : []))

function onInput(e) {
    emit('update:modelValue', e.target.value)
}

function clearInputTimer(key) {
    const t = textInputTimers.get(key)
    if (t == null) return
    clearTimeout(t)
    textInputTimers.delete(key)
}

function emitTextValueDebounced(key, value) {
    clearInputTimer(key)
    const timer = setTimeout(() => {
        emit('update:modelValue', value)
        textInputTimers.delete(key)
    }, TEXT_INPUT_DEBOUNCE_MS)
    textInputTimers.set(key, timer)
}

function onTextInput(e) {
    emitTextValueDebounced(`root:${props.field.key}`, e.target.value)
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

function updateSubfieldTextDebounced(rowIndex, subKey, value) {
    const key = `rep:${props.field.key}:${rowIndex}:${subKey}`
    clearInputTimer(key)
    const timer = setTimeout(() => {
        const next = repeaterRows.value.map((row, i) =>
            i === rowIndex ? { ...row, [subKey]: value } : { ...row },
        )
        emitRows(next)
        textInputTimers.delete(key)
    }, TEXT_INPUT_DEBOUNCE_MS)
    textInputTimers.set(key, timer)
}

function updateSubfieldUrlDebounced(rowIndex, subKey, value) {
    const key = `rep-url:${props.field.key}:${rowIndex}:${subKey}`
    clearInputTimer(key)
    const timer = setTimeout(() => {
        const next = repeaterRows.value.map((row, i) =>
            i === rowIndex ? { ...row, [subKey]: value } : { ...row },
        )
        emitRows(next)
        textInputTimers.delete(key)
    }, URL_INPUT_DEBOUNCE_MS)
    textInputTimers.set(key, timer)
}

function shouldShowSubfield(row, sub) {
    if (sub.key !== 'custom_network') return true
    return row?.network === 'otra'
}

function shouldStripProtocolForRowUrl(fieldKey, subKey) {
    return (fieldKey === 'links' || fieldKey === 'items') && subKey === 'url'
}

function stripProtocolForEditing(value) {
    const raw = String(value ?? '').trim()
    return raw.replace(/^(https?:\/\/)/i, '')
}

function rowUrlDisplayValue(fieldKey, subKey, value) {
    if (!shouldStripProtocolForRowUrl(fieldKey, subKey)) return value ?? ''
    return stripProtocolForEditing(value)
}

function normalizeRowUrlInput(fieldKey, subKey, value) {
    if (!shouldStripProtocolForRowUrl(fieldKey, subKey)) return value
    return stripProtocolForEditing(value)
}

onBeforeUnmount(() => {
    for (const timer of textInputTimers.values()) {
        clearTimeout(timer)
    }
    textInputTimers.clear()
})
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
                <template v-for="sub in field.subfields || []" :key="sub.key">
                <div
                    v-if="shouldShowSubfield(row, sub)"
                    class="mb-3 last:mb-0"
                >
                    <label class="mb-1 block text-xs font-medium text-gray-600">{{ sub.label }}</label>
                    <input
                        v-if="sub.type === 'text'"
                        type="text"
                        :class="textLikeClass"
                        :name="`${field.key}.${rowIndex}.${sub.key}`"
                        :value="row[sub.key] ?? ''"
                        :disabled="disabled"
                        @input="updateSubfieldTextDebounced(rowIndex, sub.key, $event.target.value)"
                    />
                    <input
                        v-else-if="sub.type === 'url'"
                        type="url"
                        :class="textLikeClass"
                        :name="`${field.key}.${rowIndex}.${sub.key}`"
                        :value="rowUrlDisplayValue(field.key, sub.key, row[sub.key])"
                        :disabled="disabled"
                        @input="
                            updateSubfieldUrlDebounced(
                                rowIndex,
                                sub.key,
                                normalizeRowUrlInput(field.key, sub.key, $event.target.value),
                            )
                        "
                    />
                    <select
                        v-else-if="sub.type === 'select'"
                        :class="`${textLikeClass} bg-white`"
                        :value="row[sub.key] ?? ''"
                        :disabled="disabled"
                        @change="updateSubfield(rowIndex, sub.key, $event.target.value)"
                    >
                        <option v-for="opt in sub.options || []" :key="opt" :value="opt">
                            {{ opt }}
                        </option>
                    </select>
                    <textarea
                        v-else-if="sub.type === 'textarea'"
                        :class="textareaClass"
                        :value="row[sub.key] ?? ''"
                        :disabled="disabled"
                        @input="updateSubfieldTextDebounced(rowIndex, sub.key, $event.target.value)"
                    />
                    <input
                        v-else
                        type="text"
                        :class="textLikeClass"
                        :value="row[sub.key] ?? ''"
                        :disabled="disabled"
                        @input="updateSubfieldTextDebounced(rowIndex, sub.key, $event.target.value)"
                    />
                </div>
                </template>


                <button
                    type="button"
                    class="min-h-9 w-full touch-manipulation rounded-md text-sm font-medium text-red-700 ring-1 ring-red-200 active:bg-red-50 enabled:cursor-pointer disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto sm:px-3"
                    data-action="repeater-remove"
                    :disabled="disabled"
                    @click="removeRepeaterRow(rowIndex)"
                >
                    Quitar fila
                </button>
            </div>
            <button
                type="button"
                class="min-h-11 w-full touch-manipulation rounded-lg bg-indigo-600 px-3 text-sm font-semibold text-white active:bg-indigo-700 enabled:cursor-pointer disabled:cursor-not-allowed disabled:opacity-50"
                data-action="repeater-add"
                :disabled="disabled"
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
            :disabled="disabled"
            @input="onTextInput"
        />
        <textarea
            v-else-if="field.type === 'textarea'"
            :class="textareaClass"
            :name="field.key"
            :value="modelValue ?? ''"
            :disabled="disabled"
            @input="onTextInput"
        />
        <input
            v-else-if="field.type === 'url'"
            type="url"
            :class="textLikeClass"
            inputmode="url"
            autocomplete="url"
            :name="field.key"
            :value="modelValue ?? ''"
            :disabled="disabled"
            @input="onTextInput"
        />
        <input
            v-else-if="field.type === 'color'"
            type="color"
            :class="colorClass"
            :name="field.key"
            :value="modelValue || '#000000'"
            :disabled="disabled"
            @input="onInput"
        />
        <select
            v-else-if="field.type === 'select'"
            :class="`${textLikeClass} bg-white`"
            :name="field.key"
            :value="modelValue ?? ''"
            :disabled="disabled"
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
            :disabled="disabled"
            @input="onInput"
        />
    </div>
</template>
