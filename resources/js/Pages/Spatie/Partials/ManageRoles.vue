<script setup>
import { computed, ref, defineProps } from "vue";
import { Inertia } from "@inertiajs/inertia";
import { useForm } from "@inertiajs/vue3";
import ActionMessage from "@/Components/ActionMessage.vue";
import FormSection from "@/Components/FormSection.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import Datatable from "@/Components/Datatable.vue";

const roleInput = ref(null);

const form = useForm({
  roleInput: "",
});

const submitRoleForm = () => {
  form.put(route("roles.store"), {
    errorBag: "submitRoleForm",
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
      Inertia.reload({ only: ["roles"] });
    },
    onError: () => {
      if (form.errors.roleInput) {
        form.reset("roleInput");
        roleInput.value.focus();
      }
    },
  });
};

const props = defineProps({
  roles: Array,
});

const tableHeaders = ref([{ name: "Funções" }]);
const tableData = computed(() => {
  return props.roles.map((role) => ({
    name: role.name,
  }));
});
const tableId = ref("ManageRoles");
</script>

<template>
  <FormSection @submitted="submitRoleForm">
    <template #title> Gerenciar Funções </template>

    <template #description>
      Crie funções para seu time e atribua permissões a elas.
    </template>

    <template #form>
      <!-- Formulário -->
      <div class="grid grid-cols-12 gap-2 sm:gap-6 mt-8 items-center">
        <!-- Label para o campo de nova função -->
        <div class="col-span-3 sm:col-span-2">
          <label
            class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
            for="roleInput"
          >
            Nova Função
          </label>
        </div>

        <!-- Campo de entrada para a nova função -->
        <div class="col-span-9 sm:col-span-8">
          <div class="flex flex-col gap-2">
            <TextInput
              id="roleInput"
              ref="roleInput"
              rules="required|max:10|regex:/^[A-Z][a-zA-Z]*$/"
              v-model="form.roleInput"
              type="text"
              class="mt-1 block w-full"
              placeholder="Após criar uma função para o time não será possível voltar atrás"
              autocomplete="off"
            />
            <!-- Mensagem de erro abaixo do campo de entrada -->
            <InputError :message="form.errors.roleInput" />
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
