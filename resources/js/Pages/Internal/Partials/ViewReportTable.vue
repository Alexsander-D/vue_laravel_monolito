<script setup>
import { ref, defineProps } from "vue";
import { useForm } from "@inertiajs/vue3";
import FormSection from "@/Components/FormSection.vue";
import Datatable from "@/Components/DatatableServerSide.vue";
import DateFilter from "@/Components/DateFilter.vue";
import Select from "@/Components/Select.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";

const props = defineProps({
  date: {
    type: Object,
  },
  filters: {
    type: Object,
  },
  allowedUsers: {
    type: Array,
    required: true,
    default: () => [],
  },
});

const form = useForm({
  startDate: props.date.startDate,
  endDate: props.date.endDate,
  filters: {
    userFilter: props.filters.userFilter,
    productFilter: props.filters.productFilter,
  },
});

const submitForm = async () => {
  form.get(route("report.index"), {
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
  { name: "Criado em", data: "created_at" },
  { name: "Responsável", data: "user_name" },
  { name: "Produto Entrada", data: "product" },
  { name: "Produto Saída", data: "product_new" },
  { name: "Transformação", data: "transformed" },
  { name: "Família", data: "family" },
  { name: "Componente", data: "component" },
  { name: "Defeito", data: "defect" },
  { name: "Solução", data: "solution" },
  { name: "Serial Number", data: "serial_number" },
  { name: "Lote", data: "lot" },
  { name: "Observação", data: "observation" },
  { name: "Atualizado em", data: "updated_at" },
  { name: "Status", data: "status" },
  { name: "Data Embalagem", data: "output_date" },
  { name: "Responsável embalagem", data: "output_user_name" },
]);

const productionReports = ref([
  { name: "ID", data: "id" },
  { name: "Criado em", data: "created_at" },
  { name: "Responsável", data: "user_name" },
  { name: "Produto Entrada", data: "product" },
  { name: "Produto Saída", data: "product_new" },
  { name: "Transformação", data: "transformed" },
  { name: "Família", data: "family" },
  { name: "Serial Number", data: "serial_number" },
  { name: "Lote", data: "lot" },
  { name: "Atualizado em", data: "updated_at" },
  { name: "Status", data: "status" },
  { name: "Data Embalagem", data: "output_date" },
  { name: "Responsável embalagem", data: "output_user_name" },
  { name: "", data: "button", type: "button", class: "text-center" },
]);

const dailyProductionReports = ref([
  { name: "DATA", data: "DATA" },
  { name: "RESPONSÁVEL", data: "RESPONSAVEL" },
  { name: "DESCARTE", data: "DESCARTE" },
  { name: "TOTAL", data: "TOTAL" },
  { name: "NB", data: "NB" },
  { name: "P9", data: "P9" },
  { name: "BAR", data: "BAR" },
  { name: "PC", data: "PC" },
  { name: "ES", data: "ES" },
  { name: "PER", data: "PER" },
  { name: "TV/VD", data: "TV_VD" },
  { name: "PD", data: "PD" },
]);

const products = ref(props.filters.productFilter ? [props.filters.productFilter] : []);
const fetchData = async (url, params, setter) => {
  try {
    const response = await axios.post(url, params);

    if (response.data) {
      const mappedData = response.data.map((item) => {
        let result = {};

        result = {
          label: item.sku,
          value: item.family,
        };

        return result;
      });

      setter(mappedData);
    }
  } catch (error) {
    console.error(`Erro ao carregar dados: ${error.message}`);
  }
};
const fetchProducts = (search) =>
  fetchData(
    route("findProducts.show"),
    { sku: search },
    (data) => (products.value = data)
  );
</script>

<template>
  <FormSection @submitted="submitForm">
    <template #title>Visualizar Lançamentos</template>

    <template #description>
      <DateFilter v-model:startDate="form.startDate" v-model:endDate="form.endDate" />

      <div class="grid grid-cols-12 gap-2 mt-4">
        <div class="col-span-1 text-center">
          <label
            class="font-medium inline-block colorBase mt-4 dark:text-neutral-200"
            for="userFilter"
          >
            Técnico:
          </label>
        </div>

        <div class="col-span-11">
          <div class="flex flex-col gap-2">
            <Select
              id="userFilter"
              class="mt-1 block w-full"
              :options="props.allowedUsers"
              v-model="form.filters.userFilter"
              label="name"
            />
            <InputError :message="form.errors.userFilter" />
          </div>
        </div>

        <div class="col-span-1 text-center">
          <label
            class="font-medium inline-block colorBase mt-4 dark:text-neutral-200"
            for="productFilter"
          >
            Produto:
          </label>
        </div>

        <div class="col-span-11">
          <div class="flex flex-col gap-2">
            <Select
              id="productFilter"
              class="mt-1 block w-full"
              :options="products"
              v-model="form.filters.productFilter"
              label="label"
              @search-change="fetchProducts"
            />
            <InputError :message="form.errors.productFilter" />
          </div>
        </div>
      </div>

      <div class="col-span-12 sm:col-span-12 flex justify-end mt-4 sm:mt-2">
        <PrimaryButton
          :class="{ 'opacity-25': form.processing }"
          :disabled="form.processing"
        >
          FILTRAR
        </PrimaryButton>
      </div>
    </template>

    <template #form>
      <h3
        class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight text-center"
      >
        Relatório de Lançamentos de Falhas
      </h3>

      <Datatable
        :thead="viewReports"
        id="viewReport"
        :ajax="
          route('report.datatable', {
            table: 'reports',
            startDate: form.startDate,
            endDate: form.endDate,
            filters: {
              productFilter: form.filters.productFilter,
              userFilter: form.filters.userFilter,
            },
          })
        "
        :export-url="
          route('report.export', {
            table: 'reports',
            startDate: form.startDate,
            endDate: form.endDate,
            filters: {
              productFilter: form.filters.productFilter,
              userFilter: form.filters.userFilter,
            },
          })
        "
      />
      <br />

      <h3
        class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight text-center"
      >
        Relatório de Produtividade
      </h3>

      <Datatable
        :thead="productionReports"
        id="productionReport"
        :ajax="
          route('report.datatable', {
            table: 'production_reports',
            startDate: form.startDate,
            endDate: form.endDate,
            filters: {
              productFilter: form.filters.productFilter,
              userFilter: form.filters.userFilter,
            },
          })
        "
        :export-url="
          route('report.export', {
            table: 'production_reports',
            startDate: form.startDate,
            endDate: form.endDate,
            filters: {
              productFilter: form.filters.productFilter,
              userFilter: form.filters.userFilter,
            },
          })
        "
      />
      <br />

      <h3
        class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight text-center"
      >
        Relatório de Produção Diária
      </h3>

      <Datatable
        :thead="dailyProductionReports"
        id="daily_production_reports"
        :ajax="
          route('report.datatable', {
            table: 'daily_production_reports',
            startDate: form.startDate,
            endDate: form.endDate,
            filters: {
              productFilter: form.filters.productFilter,
              userFilter: form.filters.userFilter,
            },
          })
        "
        :export-url="
          route('report.export', {
            table: 'daily_production_reports',
            startDate: form.startDate,
            endDate: form.endDate,
            filters: {
              productFilter: form.filters.productFilter,
              userFilter: form.filters.userFilter,
            },
          })
        "
      />
      <br />
    </template>
  </FormSection>
</template>
