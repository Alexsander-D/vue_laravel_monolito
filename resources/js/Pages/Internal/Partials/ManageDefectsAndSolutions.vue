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
import Select from "@/Components/Select.vue";

const submitComponentForm = () => {
  form.post(route("failure.create"), {
    errorBag: "submitComponentForm",
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
      Inertia.reload({ only: ["components"] });
    },
    onError: (error) => {
      console.log(error);
    },
  });
};

const props = defineProps({
  components: {
    type: Object,
    default: () => [],
  },
  defectsSolutions: {
    type: Object,
    default: () => [],
  },
});

const listedComponents = computed(() => {
  return props.components.map((component) => ({
    id: component.id,
    text: `${component.family} - ${component.component}`,
  }));
});

const form = useForm({
  components_id: "",
  defect: "",
  solution: "",
});

const tableHeaders = ref([
  { name: "ID" },
  { name: "COMPONENTE" },
  { name: "FAMÍLIA" },
  { name: "DEFEITO" },
  { name: "SOLUÇÃO" },
]);
const tableData = computed(() => {
  return props.defectsSolutions.map((defectsSolutions) => ({
    id: defectsSolutions.id,
    componente: defectsSolutions.component,
    familia: defectsSolutions.family,
    defeito: defectsSolutions.defect,
    solucao: defectsSolutions.solution,
  }));
});
const tableId = ref("ManageDefectsAndSolutions");
</script>

<template>
  <FormSection @submitted="submitComponentForm">
    <template #title> Gerenciar Defeitos e Soluções </template>

    <template #description> Atribua defeitos e soluções a um componente. </template>

    <template #form>
      <!-- Formulário -->
      <div class="grid grid-cols-12 gap-2 sm:gap-6 mt-8 items-center">
        <div class="col-span-12">
          <div class="col-span-3 sm:col-span-2">
            <label
              class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
              for="familyComponentSelect"
            >
              Componente e Família:
            </label>
          </div>

          <div class="col-span-9 sm:col-span-8">
            <div class="flex flex-col gap-2">
              <Select
                id="familyComponentSelect"
                class="mt-1 block w-full"
                :options="listedComponents"
                v-model="form.components_id"
                required
                label="text"
              />
              <InputError :message="form.errors.components_id" />
            </div>
          </div>
        </div>

        <div class="col-span-12">
          <div class="col-span-3 sm:col-span-2">
            <label
              class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
              for="defectInput"
            >
              Defeito:
            </label>
          </div>

          <div class="col-span-9 sm:col-span-8">
            <div class="flex flex-col gap-2">
              <TextInput
                id="defectInput"
                ref="defectInput"
                rules="required|max:10|regex:/^[A-Z][a-zA-Z]*$/"
                @input="form.defect = $event.target.value.toUpperCase()"
                v-model="form.defect"
                type="text"
                class="mt-1 block w-full"
                placeholder="Após criar um defeito não será possível voltar atrás"
                autocomplete="off"
              />
              <InputError :message="form.errors.defect" />
            </div>
          </div>
        </div>

        <div class="col-span-12">
          <div class="col-span-3 sm:col-span-2">
            <label
              class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
              for="solutionInput"
            >
              Solução:
            </label>
          </div>

          <div class="col-span-9 sm:col-span-8">
            <div class="flex flex-col gap-2">
              <TextInput
                id="solutionInput"
                ref="solutionInput"
                rules="required|max:10|regex:/^[A-Z][a-zA-Z]*$/"
                @input="form.solution = $event.target.value.toUpperCase()"
                v-model="form.solution"
                type="text"
                class="mt-1 block w-full"
                placeholder="Após criar uma solução não será possível voltar atrás"
                autocomplete="off"
              />
              <InputError :message="form.errors.solution" />
            </div>
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
