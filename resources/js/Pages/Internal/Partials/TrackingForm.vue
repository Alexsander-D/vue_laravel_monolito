<script setup>
import $ from "jquery";
import { ref } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import FormSection from "@/Components/FormSection.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import Datatable from "@/Components/DatatableServerSide.vue";
import DialogModal from "@/Components/DialogModal.vue";
import Swal from "sweetalert2";
import Radio from "@/Components/Radio.vue";

const page = usePage();
const form = useForm({
  trackingInput: "",
  responsableInput: "",
  excel_file: {
    type: Array,
    default: () => [],
  },
  rows: {
    type: Array,
    default: () => [],
  },
  radioSelection: "AGUARDANDO CHEGADA",
});

const openModal = ref(false);
const tableId = ref("ManageTrackingProtocol");

const trackingForm = () => {
  form.post(route("separated-tracking.create"), {
    errorBag: "trackingForm",
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
      $(`#${tableId.value}`).DataTable().ajax.reload();
    },
    onError: (error) => {
      console.log(error);
      if (form.errors.trackingForm) {
        form.reset("trackingForm");
        trackingForm.value.focus();
      }
    },
  });
};

const tableHeaders = ref([
  { name: "ID", data: "id" },
  { name: "DATA", data: "updated_at" },
  { name: "RASTREIO", data: "tracking" },
  { name: "RESPONSÁVEL", data: "responsable" },
  { name: "STATUS", data: "status" },
  { name: "", data: "update" },
  { name: "", data: "delete" },
]);

function updateTracking(trackingId) {
  axios
    .put(route("separated-tracking.update", trackingId))
    .then(() => {
      $(`#${tableId.value}`).DataTable().ajax.reload();
    })
    .catch((error) => {
      console.error("Erro:", error);
    });
}

function deleteTracking(trackingId) {
  axios
    .delete(route("separated-tracking.delete", trackingId))
    .then(() => {
      $(`#${tableId.value}`).DataTable().ajax.reload();
    })
    .catch((error) => {
      console.error("Erro:", error);
    });
}

$(document).on("click", ".update-btn", function () {
  const trackingId = $(this).data("id");
  updateTracking(trackingId);
});

$(document).on("click", ".delete-btn", function () {
  const trackingId = $(this).data("id");
  deleteTracking(trackingId);
});

const handleFileChange = (event) => {
  form.excel_file = event.target.files[0];
};

const uploadExcelSubmit = async () => {
  if (!(form.excel_file instanceof File) || form.excel_file.size === 0) {
    openModal.value = false;
    await Swal.fire({
      icon: "warning",
      title: "Arquivo não selecionado",
      text: "Por favor, selecione um arquivo Excel válido.",
    });
    return;
  }

  // Prepara o formData pra upload do Excel
  const formData = new FormData();
  formData.append("excel_file", form.excel_file);

  try {
    const response = await axios.post(route("excel.upload"), formData, {
      headers: {
        "Content-Type": "multipart/form-data",
      },
    });

    // Armazena os dados da planilha no form pra enviar depois
    const previewRows = response.data[0];
    form.rows = previewRows;

    form.post(route("separated-tracking.excel"), {
      preserveScroll: true,
      onSuccess: () => {
        openModal.value = false;
        form.reset();
        $(`#${tableId.value}`).DataTable().ajax.reload();
      },
      onError: async (errors) => {
        openModal.value = false;

        const importErrors = page.props?.errorBags.default.import_errors ?? [];
        console.log(importErrors);

        const errorsArray = Object.values(importErrors);

        await Swal.fire({
          icon: "error",
          title: "Erro na importação",
          html: `<div style="max-height: 300px; overflow-y: auto; text-align: center;">Não foi possível importar os seguintes rastreios pois estão duplicados: ${errorsArray
            .map((e) => `<p>${e}</p>`)
            .join("")}</div>`,
          width: "50rem",
          customClass: {
            popup: "swal-wide",
          },
          confirmButtonText: "Ok",
        });
      },
    });
  } catch (err) {
    openModal.value = false;

    await Swal.fire({
      icon: "error",
      title: "Erro ao processar o arquivo",
      text: "Falha ao fazer upload ou ler o arquivo Excel.",
    });

    console.error(err);
  }
};

const radioOptions = [
  { value: "AGUARDANDO CHEGADA", label: "Correio" },
  { value: "SOLICITADO", label: "Separação" },
];
</script>

<template>
  <DialogModal :show="openModal" @close="openModal = false">
    <template #title>
      <div class="flex justify-between items-center">
        <div class="flex-grow text-center">Adicionar Produto</div>
        <div class="relative inline-block max-w-full">
          <a
            href="/storage/excel/separated_tracking.xlsx"
            download="separated_tracking.xlsx"
            class="tooltip-button px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg shadow-md hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-400 transition-all"
          >
            Baixar Modelo
          </a>
        </div>
      </div>
    </template>

    <template #content>
      <div class="grid grid-cols-12 gap-2 sm:gap-6 mt-2 items-center">
        <div class="col-span-12">
          <div class="col-span-3 sm:col-span-2">
            <label
              class="font-medium inline-block text-sm text-gray-800 mt-1 dark:text-neutral-200"
              for="ean"
            >
              Excel:
            </label>
          </div>
          <div class="col-span-9 sm:col-span-8">
            <div class="flex flex-col gap-2">
              <TextInput
                @change="handleFileChange"
                type="file"
                class="mt-1 block w-full"
              />
            </div>
          </div>
        </div>
        <div class="col-span-8">
          <div class="flex gap-x-6">
            <Radio
              v-for="option in radioOptions"
              :key="option.value"
              v-model="form.radioSelection"
              :name="'categoria'"
              :value="option.value"
              :label="option.label"
            />
          </div>
        </div>
      </div>
    </template>

    <template #footer>
      <div class="col-span-12 sm:col-span-12 flex justify-end mt-4 sm:mt-2">
        <PrimaryButton
          @click="uploadExcelSubmit"
          :class="{ 'opacity-25': form.processing }"
          :disabled="form.processing"
        >
          Confirmar
        </PrimaryButton>
      </div>
    </template>
  </DialogModal>

  <FormSection @submitted="trackingForm">
    <template #title>
      <div class="flex justify-between items-center">
        <div class="flex-grow text-center">Protocolo de Rastreamento</div>
        <button
          @click="openModal = true"
          type="button"
          class="px-2 py-2 bg-blue-500 text-white text-sm rounded-md shadow hover:bg-blue-600 focus:outline-none focus:ring focus:ring-blue-300 dark:bg-blue-700 dark:hover:bg-blue-800"
        >
          Importar Lista
        </button>
      </div>
    </template>

    <template #description>
      Os produtos aqui cadastrados, deverão ser separados e entregue ao responsável.
    </template>

    <template #form>
      <div class="grid grid-cols-12 gap-2 mt-4">
        <!-- Linha 1: Rastreio -->
        <div class="col-span-2 text-right flex items-center">
          <label class="font-medium dark:text-neutral-200" for="trackingInput">
            Rastreio:
          </label>
        </div>
        <div class="col-span-8">
          <div class="flex flex-col gap-2">
            <TextInput
              id="trackingInput"
              ref="trackingInput"
              rules="required|max:10|regex:/^[A-Z][a-zA-Z]*$/"
              v-model="form.trackingInput"
              type="text"
              class="mt-1 block w-full"
              autocomplete="off"
            />
            <InputError :message="form.errors.trackingInput" />
          </div>
        </div>
        <div class="col-span-2"></div>

        <!-- Linha 2: Responsável -->
        <div class="col-span-2 text-right flex items-center">
          <label class="font-medium dark:text-neutral-200" for="responsableInput">
            Responsável:
          </label>
        </div>
        <div class="col-span-8">
          <div class="flex flex-col gap-2">
            <TextInput
              id="responsableInput"
              ref="responsableInput"
              rules="required|max:10|regex:/^[A-Z][a-zA-Z]*$/"
              v-model="form.responsableInput"
              type="text"
              class="mt-1 mb-1 block w-full"
              autocomplete="off"
            />
            <InputError :message="form.errors.responsableInput" />
          </div>
        </div>
        <div class="col-span-2"></div>

        <!-- Linha 3: Grupo de Rádios -->
        <div class="col-span-2 text-right flex items-center">
          <label class="font-medium dark:text-neutral-200"></label>
        </div>

        <div class="col-span-8">
          <div class="flex gap-x-6">
            <Radio
              v-for="option in radioOptions"
              :key="option.value"
              v-model="form.radioSelection"
              :name="'categoria'"
              :value="option.value"
              :label="option.label"
            />
          </div>
        </div>

        <div class="col-span-2"></div>

        <!-- Linha 4: Botão -->
        <div class="col-span-12 flex justify-end mt-4">
          <PrimaryButton
            :class="{ 'opacity-25': form.processing }"
            :disabled="form.processing"
          >
            Separar
          </PrimaryButton>
        </div>
      </div>

      <Datatable
        :thead="tableHeaders"
        :id="tableId"
        :ajax="route('separated-tracking.datatable')"
      />
    </template>
  </FormSection>
</template>
