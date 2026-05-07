<script setup>
import { ref, defineProps } from "vue";
import { useForm } from "@inertiajs/vue3";
import FormSection from "@/Components/FormSection.vue";
import Datatable from "@/Components/DatatableServerSide.vue";
import ActionMessage from "@/Components/ActionMessage.vue";
import DateFilter from "@/Components/DateFilter.vue";

const props = defineProps({
  date: {
    type: Object,
  },
});

const form = useForm({
  startDate: props.date.startDate,
  endDate: props.date.endDate,
});

const submitForm = async () => {
  form.get(route("individual_report.index"), {
    errorBag: "submitForm",
    preserveScroll: true,
    onSuccess: () => {},
    onError: (error) => {
      console.log(error);
    },
  });
};

const viewReports = ref([
  { name: "ID", data: "id" },
  { name: "CRIADO EM", data: "created_at" },
  { name: "RESPONSÁVEL", data: "user_name" },
  { name: "PRODUTO", data: "product" },
  { name: "FAMILIA", data: "family" },
  { name: "COMPONENTE", data: "component" },
  { name: "DEFEITO", data: "defect" },
  { name: "SOLUÇÃO", data: "solution" },
  { name: "SERIAL NUMBER", data: "serial_number" },
  { name: "ATUALIZADO EM", data: "updated_at" },
  { name: "STATUS", data: "status" },

]);
</script>

<template>
  <FormSection>
    <template #title>Visualizar Lançamentos</template>

    <template #description>
      <DateFilter
        v-model:startDate="form.startDate"
        v-model:endDate="form.endDate"
        @submit="submitForm"
      />
    </template>

    <template #form>
      <div class="grid grid-cols-12 gap-2 sm:gap-6 items-center">
        <!-- Mensagem de sucesso -->
        <div class="col-span-12 sm:col-span-12 flex justify-end mt-2">
          <ActionMessage :on="form.recentlySuccessful"> Salvo. </ActionMessage>
        </div>
      </div>
    
      <Datatable
        :thead="viewReports"
        id="viewReport"
        :ajax="
          route('individual_report.datatable', {
            startDate: form.startDate,
            endDate: form.endDate,
          })
        "
        :export-url="
          route('individual_report.export', {
            startDate: form.startDate,
            endDate: form.endDate,
          })
        "
      />
    </template>
  </FormSection>
</template>
