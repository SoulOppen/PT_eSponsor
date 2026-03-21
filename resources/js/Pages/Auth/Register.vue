<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { watch, ref } from 'vue';

const form = useForm({
    name: '',
    site_name: '',
    slug: '',
    bio: '',
    avatar: null,
    email: '',
    password: '',
    password_confirmation: '',
});

const slugTouched = ref(false);

const slugify = (value) =>
    String(value || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .slice(0, 60);

watch(
    () => form.name,
    (name) => {
        if (slugTouched.value) return;
        form.slug = slugify(name);
    },
);

const onSlugInput = (event) => {
    slugTouched.value = true;
    form.slug = slugify(event.target.value);
};

const onAvatarChange = (event) => {
    form.avatar = event.target.files?.[0] || null;
};

const submit = () => {
    form.post(route('register'), {
        forceFormData: true,
        transform: (data) => {
            const next = { ...data }
            if (!next.avatar) {
                delete next.avatar
            }
            return next
        },
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Register" />

        <form @submit.prevent="submit" class="space-y-8">
            <!-- 1. Cuenta: identidad y acceso -->
            <div class="space-y-4">
                <h3 class="text-sm font-semibold text-gray-900">Tu cuenta</h3>

                <div>
                    <InputLabel for="name" value="Nombre" />

                    <TextInput
                        id="name"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.name"
                        required
                        autofocus
                        autocomplete="name"
                    />

                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div>
                    <InputLabel for="email" value="Correo electrónico" />

                    <TextInput
                        id="email"
                        type="email"
                        class="mt-1 block w-full"
                        v-model="form.email"
                        required
                        autocomplete="username"
                    />

                    <InputError class="mt-2" :message="form.errors.email" />
                </div>
            </div>

            <!-- 2. Seguridad -->
            <div class="space-y-4 border-t border-gray-200 pt-6">
                <h3 class="text-sm font-semibold text-gray-900">Contraseña</h3>

                <div>
                    <InputLabel for="password" value="Contraseña" />

                    <TextInput
                        id="password"
                        type="password"
                        class="mt-1 block w-full"
                        v-model="form.password"
                        required
                        autocomplete="new-password"
                    />

                    <InputError class="mt-2" :message="form.errors.password" />
                </div>

                <div>
                    <InputLabel
                        for="password_confirmation"
                        value="Confirmar contraseña"
                    />

                    <TextInput
                        id="password_confirmation"
                        type="password"
                        class="mt-1 block w-full"
                        v-model="form.password_confirmation"
                        required
                        autocomplete="new-password"
                    />

                    <InputError
                        class="mt-2"
                        :message="form.errors.password_confirmation"
                    />
                </div>
            </div>

            <!-- 3. Página pública (URL y contenido visible) -->
            <div class="space-y-4 border-t border-gray-200 pt-6">
                <h3 class="text-sm font-semibold text-gray-900">Tu página pública</h3>
                <p class="text-xs text-gray-500">
                    Título y URL de tu página; el slug se sugiere desde tu nombre y puedes ajustarlo.
                </p>

                <div>
                    <InputLabel for="site_name" value="Nombre público del sitio (opcional)" />

                    <TextInput
                        id="site_name"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.site_name"
                        autocomplete="organization"
                    />

                    <p class="mt-1 text-xs text-gray-500">
                        Si lo dejas vacío, se usará tu nombre como título visible.
                    </p>
                    <InputError class="mt-2" :message="form.errors.site_name" />
                </div>

                <div>
                    <InputLabel for="slug" value="Slug (URL) — obligatorio" />

                    <TextInput
                        id="slug"
                        type="text"
                        class="mt-1 block w-full"
                        :model-value="form.slug"
                        required
                        autocomplete="off"
                        @input="onSlugInput"
                    />

                    <p class="mt-1 text-xs text-gray-500">
                        Tu página será <code class="rounded bg-gray-100 px-1">/@{{ form.slug || 'tu-slug' }}</code>. Solo minúsculas, números y guiones.
                    </p>
                    <InputError class="mt-2" :message="form.errors.slug" />
                </div>

                <div>
                    <InputLabel for="bio" value="Bio (opcional)" />
                    <textarea
                        id="bio"
                        v-model="form.bio"
                        rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <InputError class="mt-2" :message="form.errors.bio" />
                </div>

                <div>
                    <InputLabel for="avatar" value="Avatar (opcional)" />
                    <input
                        id="avatar"
                        type="file"
                        accept="image/*"
                        class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:font-medium file:text-indigo-700"
                        @change="onAvatarChange"
                    />
                    <InputError class="mt-2" :message="form.errors.avatar" />
                </div>
            </div>

            <div class="flex items-center justify-end border-t border-gray-200 pt-6">
                <Link
                    :href="route('login')"
                    class="inline-flex min-h-11 items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Ir a login
                </Link>

                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Register
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>

