<script setup>
import { ref, defineProps } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import ActionSection from '@/Components/ActionSection.vue';
import Checkbox from '@/Components/Checkbox.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import DialogModal from '@/Components/DialogModal.vue';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SectionBorder from '@/Components/SectionBorder.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    tokens: Array,
    availablePermissions: Array,
    defaultPermissions: Array,
});

const createApiTokenForm = useForm({
    name: '',
    permissions: props.defaultPermissions,
});

const updateApiTokenForm = useForm({
    permissions: [],
});

const deleteApiTokenForm = useForm({});

const displayingToken = ref(false);
const managingPermissionsFor = ref(null);
const apiTokenBeingDeleted = ref(null);

const createApiToken = () => {
    createApiTokenForm.post(route('api-tokens.store'), {
        preserveScroll: true,
        onSuccess: () => {
            displayingToken.value = true;
            createApiTokenForm.reset();
        },
    });
};

const manageApiTokenPermissions = (token) => {
    updateApiTokenForm.permissions = token.abilities;
    managingPermissionsFor.value = token;
};

const updateApiToken = () => {
    updateApiTokenForm.put(route('api-tokens.update', managingPermissionsFor.value), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => (managingPermissionsFor.value = null),
    });
};

const confirmApiTokenDeletion = (token) => {
    apiTokenBeingDeleted.value = token;
};

const deleteApiToken = () => {
    deleteApiTokenForm.delete(route('api-tokens.destroy', apiTokenBeingDeleted.value), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => (apiTokenBeingDeleted.value = null),
    });
};
</script>

<template>
    <!-- Generate API Token -->
    <FormSection @submitted="createApiToken">
        <template #title>
            Criar token de API
        </template>

        <template #description>
            Os tokens de API permitem que serviços de terceiros sejam autenticados em nosso aplicativo em seu nome.
        </template>

        <template #form>
            <!-- Token Name -->
            <div class="grid sm:grid-cols-12 gap-2 sm:gap-6 mt-8">

                <div class="col-span-2">

                    <label class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
                        for="name">
                        Nome
                    </label>
                </div>

                <div class="col-span-9">
                    <div class="flex items-center gap-5">
                        <TextInput id="name" v-model="createApiTokenForm.name" type="text" class="mt-1 block w-full"
                            autofocus />
                        <InputError :message="createApiTokenForm.errors.name" class="mt-2" />
                    </div>
                </div>

                <!-- Token Permissions -->
                <div class="col-span-2" v-if="availablePermissions.length > 0">

                    <label class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
                        for="permissions">
                        Permissões
                    </label>
                </div>

                <div class="col-span-9">
                    <div class="flex items-center gap-5">
                        <div v-for="permission in availablePermissions" :key="permission">
                            <label class="flex items-center mt-2.5">
                                <Checkbox v-model:checked="createApiTokenForm.permissions" :value="permission" />
                                <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">{{ permission }}</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

        </template>

        <template #actions>
            <ActionMessage :on="createApiTokenForm.recentlySuccessful" class="me-3">
                Criado.
            </ActionMessage>

            <PrimaryButton :class="{ 'opacity-25': createApiTokenForm.processing }"
                :disabled="createApiTokenForm.processing">
                Criar
            </PrimaryButton>
        </template>
    </FormSection>

    <div v-if="tokens.length > 0">
        <SectionBorder />

        <!-- Manage API Tokens -->
        <div class="mt-10 md:mt-3">
            <ActionSection>
                <template #title>
                    Gerenciar tokens de API
                </template>

                <template #description>
                    Você pode excluir qualquer um dos seus tokens existentes se eles não forem mais necessários.
                </template>

                <!-- API Token List -->
                <template #content>
                    <div class="space-y-6">
                        <div v-for="token in tokens" :key="token.id" class="flex items-center justify-between">
                            <div class="break-all dark:text-white">
                                {{ token.name }}
                            </div>

                            <div class="flex items-center ms-2">
                                <div v-if="token.last_used_ago" class="text-sm text-gray-400">
                                    Usado por último {{ token.last_used_ago }}
                                </div>

                                <button v-if="availablePermissions.length > 0"
                                    class="cursor-pointer ms-6 text-sm text-gray-400 underline"
                                    @click="manageApiTokenPermissions(token)">
                                    Permissões
                                </button>

                                <button class="cursor-pointer ms-6 text-sm text-red-500"
                                    @click="confirmApiTokenDeletion(token)">
                                    Deletar
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </ActionSection>
        </div>
    </div>

    <!-- Token Value Modal -->
    <DialogModal :show="displayingToken" @close="displayingToken = false">
        <template #title>
            API Token
        </template>

        <template #content>
            <div>
                Please copy your new API token. For your security, it won't be shown again.
            </div>

            <div v-if="$page.props.jetstream.flash.token"
                class="mt-4 bg-gray-100 dark:bg-gray-900 px-4 py-2 rounded font-mono text-sm text-gray-500 break-all">
                {{ $page.props.jetstream.flash.token }}
            </div>
        </template>

        <template #footer>
            <SecondaryButton @click="displayingToken = false">
                Close
            </SecondaryButton>
        </template>
    </DialogModal>

    <!-- API Token Permissions Modal -->
    <DialogModal :show="managingPermissionsFor != null" @close="managingPermissionsFor = null">
        <template #title>
            API Token Permissions
        </template>

        <template #content>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="permission in availablePermissions" :key="permission">
                    <label class="flex items-center">
                        <Checkbox v-model:checked="updateApiTokenForm.permissions" :value="permission" />
                        <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">{{ permission }}</span>
                    </label>
                </div>
            </div>
        </template>

        <template #footer>
            <SecondaryButton @click="managingPermissionsFor = null">
                Cancel
            </SecondaryButton>

            <PrimaryButton class="ms-3" :class="{ 'opacity-25': updateApiTokenForm.processing }"
                :disabled="updateApiTokenForm.processing" @click="updateApiToken">
                Save
            </PrimaryButton>
        </template>
    </DialogModal>

    <!-- Delete Token Confirmation Modal -->
    <ConfirmationModal :show="apiTokenBeingDeleted != null" @close="apiTokenBeingDeleted = null">
        <template #title>
            Delete API Token
        </template>

        <template #content>
            Are you sure you would like to delete this API token?
        </template>

        <template #footer>
            <SecondaryButton @click="apiTokenBeingDeleted = null">
                Cancel
            </SecondaryButton>

            <DangerButton class="ms-3" :class="{ 'opacity-25': deleteApiTokenForm.processing }"
                :disabled="deleteApiTokenForm.processing" @click="deleteApiToken">
                Delete
            </DangerButton>
        </template>
    </ConfirmationModal>
</template>
