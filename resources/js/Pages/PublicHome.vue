<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue'
import { Head, Link } from '@inertiajs/vue3'
</script>

<template>
    <Head title="PT eSponsor" />

    <div class="min-h-screen bg-gray-50 text-gray-900">
        <main class="mx-auto flex min-h-screen w-full max-w-3xl flex-col items-center justify-center px-10 py-6 sm:px-12 sm:py-8 text-center">
            <ApplicationLogo class="h-24 w-24" />
            <h1 class="mt-6 text-3xl font-bold sm:text-4xl">PT eSponsor</h1>
            <p class="mt-3 max-w-xl text-base text-gray-600">
                Crea tu pagina publica con bloques, personaliza tu perfil y publica en minutos.
            </p>

            <div class="mt-8 flex w-full max-w-sm flex-col gap-3 sm:flex-row sm:justify-center">
                <Link
                    :href="route('login')"
                    class="inline-flex min-h-11 items-center justify-center rounded-lg border border-gray-300 px-6 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
                >
                    Iniciar sesion
                </Link>
                <Link
                    :href="route('register')"
                    class="inline-flex min-h-11 items-center justify-center rounded-lg bg-indigo-600 px-6 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                >
                    Crear cuenta
                </Link>
            </div>
        </main>
    </div>
</template>

