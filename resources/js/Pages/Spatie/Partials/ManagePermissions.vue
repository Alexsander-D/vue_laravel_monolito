<script setup>
import { computed, ref, nextTick, defineProps } from "vue";
import { useForm } from "@inertiajs/vue3";
import ActionMessage from "@/Components/ActionMessage.vue";
import FormSection from "@/Components/FormSection.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Select from "@/Components/Select.vue";
import Datatable from "@/Components/Datatable.vue";

const props = defineProps({
  rolesWithPermissions: {
    type: Array,
    required: true,
    default: () => [],
  },
  permissions: Array,
});

const tableHeaders = ref([{ name: "Role" }, { name: "Permissões" }]);
const tableData = computed(() => {
  return props.rolesWithPermissions.map((roleWithPermissions) => {
    return {
      role: roleWithPermissions.role.name, // Nome da role
      permissions: roleWithPermissions.permissions
        .map((permission) => permission.name)
        .join(", "), // Nome das permissões, separadas por vírgula
    };
  });
});

const tableId = ref("ManagePermissions");

const roles = computed(() => {
  return props.rolesWithPermissions.map((item) => ({
    id: item.role.id,
    text: item.role.name,
  }));
});

const permissionNames = computed(() => {
  return props.permissions.map((item) => ({
    id: item.id,
    text: item.name,
  }));
});

const roleSelect = ref(null);
const permissionSelect = ref(null);

const form = useForm({
  roleSelect: "",
  permissionSelect: "",
});

const submitPermissionForm = () => {
  form.put(route("permissions.storePermissionRoleRelation"), {
    errorBag: "submitPermissionForm",
    preserveScroll: true,
    onSuccess: (data) => {
      form.reset();
      console.log(data);
    },
    onError: () => {
      if (form.errors.permissionSelect) {
        form.reset("permissionSelect");
        nextTick(() => {
          permissionSelect.value.$el.focus();
        });
      }
    },
  });
};
</script>

<template>
  <FormSection @submitted="submitPermissionForm">
    <template #title> Gerenciar Permissões </template>

    <template #description>
      Crie permissões para seu time e atribua permissões a elas.
    </template>

    <template #form>
      <!-- Formulário -->
      <div class="space-y-6 mt-8">
        <!-- Label e campo de entrada para a nova permissão -->
        <div>
          <label
            class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
            for="roleSelect"
          >
            Função
          </label>
          <Select
            id="roleSelect"
            ref="roleSelect"
            :options="roles"
            v-model="form.roleSelect"
            label="text"
          />
          <InputError :message="form.errors.roleSelect" class="mt-2" />
        </div>

        <!-- Label e campo de entrada para a nova permissão -->
        <div>
          <label
            class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
            for="permissionSelect"
          >
            Nova Permissão
          </label>
          <Select
            id="permissionSelect"
            ref="permissionSelect"
            :options="permissionNames"
            v-model="form.permissionSelect"
            label="text"
          />
          <InputError :message="form.errors.permissionSelect" class="mt-2" />
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

      <!-- Fim da Tabela -->
    </template>
  </FormSection>
</template>
