<script setup>
import { Head, Link } from '@inertiajs/vue3'

defineProps({
    canLogin: { type: Boolean },
    canRegister: { type: Boolean },
})
</script>

<template>
    <Head title="Inicio" />

    <div class="min-h-screen bg-gray-50 text-gray-900">
        <div class="mx-auto flex min-h-screen w-full max-w-3xl flex-col justify-center px-6 py-12">
            <header class="mb-8">
                <h1 class="text-3xl font-bold sm:text-4xl">PT eSponsor</h1>
                <p class="mt-3 text-base text-gray-600">
                    Crea y publica tu pagina personal en minutos.
                </p>
            </header>

            <main class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <p class="text-sm text-gray-700">
                    Gestiona tus bloques desde el panel y publica tu perfil con una URL unica.
                </p>

                <div v-if="canLogin" class="flex flex-col gap-2 sm:flex-row">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="inline-flex min-h-11 items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white"
                    >
                        Ir al dashboard
                    </Link>

                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="inline-flex min-h-11 items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-800"
                        >
                            Iniciar sesion
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="inline-flex min-h-11 items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white"
                        >
                            Crear cuenta
                        </Link>
                    </template>
                </div>
            </main>
        </div>
    </div>
</template>
