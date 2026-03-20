<script setup>
import Modal from '@/Components/Modal.vue'

defineProps({
    show: { type: Boolean, default: false },
    count: { type: Number, default: 0 },
    loading: { type: Boolean, default: false },
})

const emit = defineEmits(['close', 'confirm'])
</script>

<template>
    <Modal :show="show" max-width="md" @close="emit('close')">
        <div class="p-5 sm:p-6">
            <div class="flex items-start gap-3">
                <div
                    class="mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600"
                    aria-hidden="true"
                >
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path
                            fill-rule="evenodd"
                            d="M9.401 2.03a1 1 0 0 1 1.798 0l7 14A1 1 0 0 1 17.3 17H2.7a1 1 0 0 1-.899-1.47l7-14ZM10 7a1 1 0 0 0-1 1v3a1 1 0 1 0 2 0V8a1 1 0 0 0-1-1Zm0 8a1.25 1.25 0 1 0 0-2.5A1.25 1.25 0 0 0 10 15Z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </div>
                <div class="min-w-0">
                    <h4 class="text-base font-semibold text-gray-900">Confirmar reset de bloques</h4>
                    <p class="mt-1 text-sm text-gray-600">
                        Vas a eliminar <strong>{{ count }}</strong> bloque(s). Esta acción no se puede deshacer.
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
                    class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md bg-red-600 px-4 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-60"
                    :disabled="loading"
                    @click="emit('confirm')"
                >
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path
                            fill-rule="evenodd"
                            d="M8.5 2a1 1 0 0 0-1 1V4H5a1 1 0 1 0 0 2h.278l.58 9.29A2 2 0 0 0 7.854 17h4.292a2 2 0 0 0 1.996-1.71l.58-9.29H15a1 1 0 1 0 0-2h-2.5V3a1 1 0 0 0-1-1h-3Z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    {{ loading ? 'Eliminando...' : 'Sí, eliminar todo' }}
                </button>
            </div>
        </div>
    </Modal>
</template>
