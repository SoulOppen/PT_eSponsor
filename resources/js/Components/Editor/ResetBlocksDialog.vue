<script setup>
import { computed } from 'vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
    show: { type: Boolean, default: false },
    /** deleteAll: vaciar todo. pruneUnpublished: quitar solo bloques no publicados */
    variant: { type: String, default: 'deleteAll' },
    count: { type: Number, default: 0 },
    loading: { type: Boolean, default: false },
})

const emit = defineEmits(['close', 'confirm'])

const title = computed(() =>
    props.variant === 'pruneUnpublished'
        ? 'Volver a la versión publicada'
        : 'Eliminar todos mis bloques',
)

const confirmLabel = computed(() => {
    if (props.loading) {
        return props.variant === 'pruneUnpublished' ? 'Quitando borradores…' : 'Eliminando…'
    }
    return props.variant === 'pruneUnpublished' ? 'Sí, volver a lo publicado' : 'Sí, eliminar todo'
})

const isPrune = computed(() => props.variant === 'pruneUnpublished')
</script>

<template>
    <Modal :show="show" max-width="md" @close="emit('close')">
        <div class="p-5 sm:p-6">
            <div class="flex items-start gap-3">
                <div
                    class="mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                    :class="isPrune ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-600'"
                    aria-hidden="true"
                >
                    <svg v-if="isPrune" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path
                            fill-rule="evenodd"
                            d="M4 2a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v16a1 1 0 0 1-1.447.894L10 15.618l-4.553 2.276A1 1 0 0 1 4 17V2Zm2 1v12.382l3.553-1.776a1 1 0 0 1 .894 0L14 15.382V3H6Z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    <svg v-else class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path
                            fill-rule="evenodd"
                            d="M9.401 2.03a1 1 0 0 1 1.798 0l7 14A1 1 0 0 1 17.3 17H2.7a1 1 0 0 1-.899-1.47l7-14ZM10 7a1 1 0 0 0-1 1v3a1 1 0 1 0 2 0V8a1 1 0 0 0-1-1Zm0 8a1.25 1.25 0 1 0 0-2.5A1.25 1.25 0 0 0 10 15Z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </div>
                <div class="min-w-0">
                    <h4 class="text-base font-semibold text-gray-900">{{ title }}</h4>
                    <p v-if="isPrune" class="mt-1 text-sm text-gray-600">
                        Se eliminarán
                        <strong>{{ count }}</strong>
                        bloque(s) que aún no están en tu página pública (borradores). Los bloques ya publicados se
                        mantienen y
                        <strong>vuelven al orden</strong>
                        de la última publicación.
                    </p>
                    <p v-else class="mt-1 text-sm text-gray-600">
                        Vas a eliminar
                        <strong>{{ count }}</strong>
                        bloque(s). Esta acción no se puede deshacer.
                    </p>
                </div>
            </div>

            <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    class="min-h-10 rounded-md border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    :disabled="loading"
                    @click="emit('close')"
                >
                    Cancelar
                </button>
                <button
                    type="button"
                    class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md px-4 text-sm font-semibold text-white disabled:opacity-60"
                    :class="isPrune ? 'bg-amber-600 hover:bg-amber-700' : 'bg-red-600 hover:bg-red-700'"
                    :disabled="loading"
                    @click="emit('confirm')"
                >
                    <svg
                        v-if="!isPrune"
                        class="h-4 w-4"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                        aria-hidden="true"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M8.5 2a1 1 0 0 0-1 1V4H5a1 1 0 1 0 0 2h.278l.58 9.29A2 2 0 0 0 7.854 17h4.292a2 2 0 0 0 1.996-1.71l.58-9.29H15a1 1 0 1 0 0-2h-2.5V3a1 1 0 0 0-1-1h-3Z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    {{ confirmLabel }}
                </button>
            </div>
        </div>
    </Modal>
</template>
