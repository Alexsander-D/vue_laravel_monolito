<script setup>
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import ActionMessage from "@/Components/ActionMessage.vue";
import FormSection from "@/Components/FormSection.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: "",
    password: "",
    password_confirmation: "",
});

const updatePassword = () => {
    form.put(route("user-password.update"), {
        errorBag: "updatePassword",
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset("password", "password_confirmation");
                passwordInput.value.focus();
            }

            if (form.errors.current_password) {
                form.reset("current_password");
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <FormSection @submitted="updatePassword">
        <template #title> Atualizar senha </template>

        <template #description>
            Certifique-se de que sua conta esteja usando uma senha longa e aleatória para
            permanecer segura.
        </template>

        <template #form>


            <div class="grid sm:grid-cols-12 gap-2 sm:gap-6 mt-8">
                <div class="col-span-2">
                    <label class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
                        for="current_password">
                        Senha Atual
                    </label>
                </div>


                <div class="col-span-9">
                    <div class="flex items-center gap-5">
                        <TextInput id="current_password" ref="currentPasswordInput" v-model="form.current_password"
                            type="password" class="mt-1 block w-full" autocomplete="current-password" />
                        <InputError :message="form.errors.current_password" class="mt-2" />
                    </div>
                </div>

                <div class="col-span-2">
                    <label class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
                        for="password">
                        Nova Senha
                    </label>
                </div>

                <div class="col-span-9">
                    <div class="flex items-center gap-5">
                        <TextInput id="password" ref="passwordInput" v-model="form.password" type="password"
                            class="mt-1 block w-full" autocomplete="new-password" />
                        <InputError :message="form.errors.password" class="mt-2" />
                    </div>
                </div>

                <div class="col-span-2">
                    <label class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
                        for="password_confirmation">
                        Confirme a Nova Senha
                    </label>
                </div>

                <div class="col-span-9">
                    <div class="flex items-center gap-5">
                        <TextInput id="password_confirmation" v-model="form.password_confirmation" type="password"
                            class="mt-1 block w-full" autocomplete="new-password" />
                        <InputError :message="form.errors.password_confirmation" class="mt-2" />
                    </div>
                </div>

            </div>
        </template>

        <template #actions>
            <ActionMessage :on="form.recentlySuccessful" class="me-3"> Salvo. </ActionMessage>

            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Salvar
            </PrimaryButton>
        </template>
    </FormSection>
</template>
