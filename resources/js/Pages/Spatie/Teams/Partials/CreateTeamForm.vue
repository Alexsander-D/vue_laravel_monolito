<script setup>
import { useForm } from '@inertiajs/vue3';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const form = useForm({
    name: '',
});

const createTeam = () => {
    form.post(route('teams.store'), {
        errorBag: 'createTeam',
        preserveScroll: true,
    });
};
</script>

<template>
    <FormSection @submitted="createTeam">
        <template #title>
            Criar novo time
        </template>

        <template #description>
            Crie uma nova equipe para colaborar com outras pessoas em projetos.
        </template>

        <template #form>
            <div class="grid sm:grid-cols-12 gap-2 sm:gap-6 mt-8">
                <div class="col-span-2">
                    <label class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200">
                        Responsável pelo time
                    </label>
                </div>

                <div class="col-span-9">
                    <div class="flex items-center gap-5">
                        <img class="object-cover w-12 h-12 rounded-full" :src="$page.props.auth.user.profile_photo_url"
                            :alt="$page.props.auth.user.name">

                        <div class="ms-4 leading-tight">
                            <div class="text-gray-900 dark:text-white">{{ $page.props.auth.user.name }}</div>
                            <div class="text-sm text-gray-700 dark:text-gray-300">
                                {{ $page.props.auth.user.email }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-span-2">
                    <label class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200">
                        Nome do time
                    </label>
                </div>

                <div class="col-span-9">
                    <div class="flex items-center gap-5">
                        <TextInput id="name" v-model="form.name" type="text" class="block w-full mt-1" autofocus />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>
                </div>

            </div>

        </template>

        <template #actions>
            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Criar
            </PrimaryButton>
        </template>
    </FormSection>
</template>
