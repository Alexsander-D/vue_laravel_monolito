<script setup>
import { ref, defineProps } from "vue";
import { Inertia } from "@inertiajs/inertia";
import { useForm } from "@inertiajs/vue3";
import ActionMessage from "@/Components/ActionMessage.vue";
import FormSection from "@/Components/FormSection.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import Datatable from "@/Components/Datatable.vue";

const permissionInput = ref(null);

const form = useForm({
  permissionInput: "",
});

const CreateNewPermissonFrom = () => {
  form.put(route("permissions.store"), {
    errorBag: "CreateNewPermissonFrom",
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
      Inertia.reload({ only: ["permissions"] });
    },
    onError: () => {
      if (form.errors.permissionInput) {
        form.reset("permissionInput");
        permissionInput.value.focus();
      }
    },
  });
};

const props = defineProps({
  permissions: {
    type: Array,
    required: true,
  },
});

const tableHeaders = ref([{ name: "Permissões" }]);
const tableData = props.permissions.map((permission) => ({
  name: permission.name,
}));
const tableId = ref("CreateNewPermission");
</script>

<template>
  <FormSection @submitted="CreateNewPermissonFrom">
    <template #title> Criar Nova Permissões </template>

    <template #description> Crie novas permissões para seu time. </template>

    <template #form>
      <!-- Formulário -->
      <div class="grid grid-cols-12 gap-2 sm:gap-6 mt-8 items-center">
        <!-- Label para o campo de nova função -->
        <div class="col-span-3 sm:col-span-2">
          <label
            class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
            for="current_password"
          >
            Nova Permissão
          </label>
        </div>

        <!-- Campo de entrada para a nova função -->
        <div class="col-span-9 sm:col-span-8">
          <div class="flex flex-col gap-2">
            <TextInput
              id="permissionInput"
              ref="permissionInput"
              rules="required|max:10|regex:/^[A-Z][a-zA-Z]*$/"
              v-model="form.permissionInput"
              type="text"
              class="mt-1 block w-full"
              placeholder="Após criar uma permissão para o time não será possível voltar atrás"
              autocomplete="off"
            />
            <!-- Mensagem de erro abaixo do campo de entrada -->
            <InputError :message="form.errors.permissionInput" />
          </div>
        </div>

        <!-- Botão de salvar -->
        <div class="col-span-12 sm:col-span-12 flex justify-end mt-4 sm:mt-2">
          <PrimaryButton
            :class="{ 'opacity-25': form.processing }"
            :disabled="form.processing"
          >
            Salvar
          </PrimaryButton>
        </div>

        <!-- Mensagem de sucesso -->
        <div class="col-span-12 sm:col-span-12 flex justify-end mt-2">
          <ActionMessage :on="form.recentlySuccessful"> Salvo. </ActionMessage>
        </div>
      </div>
      <!-- Fim do Formulário -->
      <Datatable :thead="tableHeaders" :tbody="tableData" :id="tableId" />
    </template>
  </FormSection>
</template>
