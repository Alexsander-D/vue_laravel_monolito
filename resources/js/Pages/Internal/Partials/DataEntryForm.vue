<script setup>
import $ from "jquery";
import { computed, ref, defineProps } from "vue";
import { Link, useForm } from "@inertiajs/vue3";
import ActionMessage from "@/Components/ActionMessage.vue";
import FormSection from "@/Components/FormSection.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import Datatable from "@/Components/Datatable.vue";
import Select from "@/Components/Select.vue";
import moment from "moment";
import "moment-timezone";
import axios from "axios";
import { Inertia } from "@inertiajs/inertia";

const entryInput = ref(null);
const products = ref([]);

const props = defineProps({
  entries: {
    type: Array,
    required: true,
    default: () => [],
  },
});

const form = useForm({
  entryInput: "",
  product: "",
  quantity: "1",
});

const entryForm = () => {
  form.post(route("entry.create"), {
    errorBag: "entryForm",
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
    },
    onError: (error) => {
      // Lida com erros
      console.log(error);
      if (form.errors.entryInput) {
        form.reset();
      }
    },
  });
};

const deleteEntry = (entryId) => {
  axios
    .delete(route("entry.destroy", entryId))
    .then(() => {
      Inertia.reload({ only: ["entries"] });
    })
    .catch((error) => {
      console.error("Erro:", error);
      alert("Falha ao deletar a entrada.");
    });
};

// const gerarIdUnico = () => {
//   const idUnico = String(Date.now());
//   return (form.entryInput = idUnico);b
// };

const deleteButton = (entryId) => {
  return `
    <button type="button" data-id="${entryId}" class="delete-btn flex shrink-0 justify-center items-center gap-2 size-[38px] text-sm rounded-lg border border-transparent bg-red-600 text-white hover:bg-red-700 focus:outline-none focus:bg-red-700 disabled:opacity-50 disabled:pointer-events-none">
      x
    </button>
  `;
};

const tableHeaders = ref([
  { name: "Data" },
  { name: "ID" },
  { name: "PRODUTO" },
  { name: "RESPONSÁVEL" },
  { name: "Excluir" },
]);

const tableData = computed(() => {
  return props.entries.map((entry) => ({
    created_at: moment.utc(entry.entries_created_at).format("DD/MM/YY HH:mm:ss"),
    uniqueId: entry.unique_id,
    product: entry.product,
    responsable: entry.name,
    button1: deleteButton(entry.id),
  }));
});

const tableId = ref("ManageEntries");

$(document).on("click", ".delete-btn", function () {
  const entryId = $(this).data("id");
  deleteEntry(entryId);
});

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
  <FormSection @submitted="entryForm">
    <template #title>
      <div class="flex justify-between items-center">
        <div class="flex-grow text-center">Entrada</div>
        <Link :href="route('set-queue.index')">
          <button
            type="button"
            class="flex items-center justify-center gap-2 size-8 text-sm rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none"
          >
            <svg
              class="shrink-0 size-6"
              fill="#fff"
              stroke-linecap="round"
              stroke-linejoin="round"
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 485.69 485.69"
            >
              <path
                d="M410.428,34.738h-76.405l5.155,23.852c0.634,2.961,0.603,5.934,0.271,8.859h54.621V452.98H91.588V67.449h54.637 c-0.332-2.941-0.348-5.914,0.285-8.891l5.156-23.82H75.248c-9.031,0-16.34,7.324-16.34,16.354v418.243 c0,9.016,7.309,16.354,16.34,16.354h335.18c9.031,0,16.354-7.341,16.354-16.354V51.093 C426.783,42.062,419.459,34.738,410.428,34.738z"
              ></path>
              <path
                d="M355.568,152.949h-111.71c-9.047,0-16.355,7.324-16.355,16.34c0,9.035,7.309,16.355,16.355,16.355h111.71 c9.047,0,16.354-7.32,16.354-16.355C371.924,160.273,364.615,152.949,355.568,152.949z"
              ></path>
              <path
                d="M355.568,253.254h-111.71c-9.047,0-16.355,7.323-16.355,16.354c0,9.021,7.309,16.357,16.355,16.357h111.71 c9.047,0,16.354-7.34,16.354-16.357C371.924,260.577,364.615,253.254,355.568,253.254z"
              ></path>
              <path
                d="M119.556,156.792c-6.898,5.82-7.786,16.137-1.965,23.047l23.855,28.27c3.117,3.699,7.688,5.805,12.496,5.805 c0.398,0,0.792-0.016,1.203-0.047c5.219-0.379,9.949-3.258,12.703-7.719l42.914-69.477c4.746-7.688,2.375-17.75-5.312-22.492 c-7.688-4.777-17.75-2.375-22.497,5.313l-31.066,50.273l-9.301-11.012C136.763,151.843,126.467,150.956,119.556,156.792z"
              ></path>
              <path
                d="M158.72,245.094c-13.554,0-24.535,10.978-24.535,24.517c0,13.543,10.98,24.52,24.535,24.52 c13.543,0,24.52-10.977,24.52-24.52C183.24,256.07,172.263,245.094,158.72,245.094z"
              ></path>
              <path
                d="M355.568,351.359h-111.71c-9.047,0-16.355,7.309-16.355,16.358c0,9.017,7.309,16.34,16.355,16.34h111.71 c9.047,0,16.354-7.323,16.354-16.34C371.924,358.667,364.615,351.359,355.568,351.359z"
              ></path>
              <path
                d="M158.72,343.199c-13.554,0-24.535,10.977-24.535,24.52c0,13.539,10.98,24.521,24.535,24.521 c13.543,0,24.52-10.979,24.52-24.521C183.24,354.176,172.263,343.199,158.72,343.199z"
              ></path>
              <path
                d="M173.463,75.613h138.73c3.401,0,6.613-1.521,8.746-4.176c2.137-2.629,2.961-6.105,2.229-9.43L311.686,8.859 C310.564,3.687,305.994,0,300.708,0H184.963c-5.281,0-9.852,3.688-10.977,8.859l-11.5,53.148 c-0.695,3.324,0.125,6.801,2.247,9.43C166.868,74.093,170.08,75.613,173.463,75.613z"
              ></path>
            </svg>
          </button>
        </Link>
      </div>
    </template>

    <template #description>
      Os produtos são cadastrados no sistema ao chegar na fabrica.
    </template>

    <template #form>
      <!-- Formulário -->
      <div
        v-if="$page.props.auth.user.current_team.name !== 'RMA'"
        class="grid grid-cols-12 gap-2 sm:gap-6 mt-8 items-center"
      >
        <!-- Label -->
        <div class="col-span-12 sm:col-span-2 text-center">
          <label
            class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
            for="entryInput"
          >
            ID
          </label>
        </div>

        <!-- Input -->
        <div class="col-span-11 sm:col-span-9">
          <div class="flex items-center gap-2 w-full">
            <TextInput
              id="entryInput"
              ref="entryInput"
              rules="required|max:10|regex:/^[A-Z][a-zA-Z]*$/"
              v-model="form.entryInput"
              type="text"
              class="mt-1 block w-full"
              autocomplete="off"
            />
          </div>
          <InputError :message="form.errors.entryInput" />
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

      <div
        v-if="$page.props.auth.user.current_team.name === 'RMA'"
        class="grid grid-cols-12 gap-2 sm:gap-6 mt-8 items-center"
      >
        <div class="col-span-12 sm:col-span-2 text-center">
          <label
            class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
            for="productSelect"
          >
            PRODUTO
          </label>
        </div>

        <!-- Input -->
        <div class="col-span-11 sm:col-span-9">
          <div class="flex items-center gap-2 w-full">
            <Select
              id="productSelect"
              class="mt-1 block w-full"
              :options="products"
              v-model="form.product"
              @search-change="fetchProducts"
              label="label"
              required
            />
          </div>
          <InputError :message="form.errors.product" />
        </div>

        <div class="col-span-12 sm:col-span-2 text-center">
          <label
            class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
            for="qtdInput"
          >
            QTD
          </label>
        </div>

        <!-- Input -->
        <div class="col-span-11 sm:col-span-9">
          <div class="flex items-center gap-2 w-full">
            <TextInput
              id="qtdInput"
              rules="required|max:10|regex:/^[A-Z][a-zA-Z]*$/"
              v-model="form.quantity"
              type="number"
              class="mt-1 block w-full"
              autocomplete="off"
              min="1"
            />
          </div>
          <InputError :message="form.errors.quantity" />
        </div>

        <div class="col-span-12 sm:col-span-12 flex justify-end mt-4 sm:mt-2">
          <PrimaryButton
            :class="{ 'opacity-25': form.processing }"
            :disabled="
              form.processing || $page.props.auth.user.current_team.name == 'RMA'
            "
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
