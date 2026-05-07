<script setup>
import $ from "jquery";
import { ref, defineProps, onMounted } from "vue";
import { useForm } from "@inertiajs/vue3";
import ActionMessage from "@/Components/ActionMessage.vue";
import FormSection from "@/Components/FormSection.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import InputError from "@/Components/InputError.vue";
import TextInput from "@/Components/TextInput.vue";
import TextArea from "@/Components/TextArea.vue";
import Select from "@/Components/Select.vue";
import axios from "axios";
import Swal from "sweetalert2";

const props = defineProps({
  reportInfo: Object,
  defectsSolutionsInfo: Object,
});

console.log(props.defectsSolutionsInfo);

const statusOptions = ref(["RECUPERADO", "DESCARTE"]);
const products = ref([]);
const defects = ref([]);
const components = ref([]);

const initialFailureSolutions =
  props.defectsSolutionsInfo?.length > 0
    ? props.defectsSolutionsInfo.map((item) => ({
        id: item.analysis_id,
        selectComponent: {
          label: item.component || "",
          value: item.component_id ?? "",
        },
        selectFailure: {
          label: [item.defect, item.solution].filter(Boolean).join(" => ") || "",
          value: item.defects_solutions_id ?? "",
        },
      }))
    : [
        {
          selectComponent: { label: "", value: "" },
          selectFailure: { label: "", value: "" },
        },
      ];

const form = useForm({
  _method: "POST",
  queueId: props.reportInfo.queue_id,
  uniqueId: props.reportInfo.unique_id,
  status: props.reportInfo.status,
  product: {
    label: props.reportInfo.product_name,
    value: props.reportInfo.product_id,
  },
  product_new: {
    label: props.reportInfo.product_new_name,
    value: props.reportInfo.product_new_id,
  },
  family: props.reportInfo.family,
  product_lot: props.reportInfo.product_lot,
  failureSolution: initialFailureSolutions,
  serial_number: props.reportInfo.serial_number,
  imei1: props.reportInfo.imei1,
  imei2: props.reportInfo.imei2,
  observation: props.reportInfo.observation,
});

console.log(form);

const submitReportForm = () => {
  if (form.status === "ANALISE") {
    form.errors.status = "ALTERE O STATUS DO PRODUTO!";

    $("html, body").stop().animate(
      {
        scrollTop: 0,
      },
      1000
    );
  } else {
    form.post(route("analyzes.create"), {
      errorBag: "submitReportForm",
      preserveScroll: true,
      onSuccess: (response) => {
        console.log(response);
      },
      onError: (errors) => {
        if (errors.submitReportForm) {
          Swal.fire({
            icon: "error",
            title: "LANÇAMENTO INCORRETO!",
            text: errors.submitReportForm,
          });
        }
      },
    });
  }
};

const fetchData = async (url, params, setter, key) => {
  try {
    const response = await axios.post(url, params);

    if (response.data) {
      const mappedData = response.data.map((item) => {
        let result = {};

        if (key === "products") {
          result = {
            label: item.sku,
            value: item.id,
            family: item.family,
          };
        } else if (key === "components") {
          result = {
            label: item.component,
            value: item.id,
          };
        } else if (key === "defects") {
          result = {
            label: item.defect + " => " + item.solution,
            value: item.id,
          };
        }

        return result;
      });

      setter(mappedData);
    }
  } catch (error) {
    console.error(`Erro ao carregar dados: ${error.message}`);
  }
};

const fetchComponents = (family) =>
  fetchData(
    route("findComponentsByFamily.show"),
    { family: family },
    (data) => (components.value = data),
    "components"
  );
const fetchDefects = (component, index) =>
  fetchData(
    route("findDefects.show"),
    { component_id: component },
    (data) => (defects.value[index] = data),
    "defects"
  );

const onSelectChange = (selectedOption, type, index = null) => {
  const value = selectedOption?.value;
  // const label = selectedOption?.label;

  if (form.failureSolution[index]) {
    form.failureSolution[index].selectFailure = { label: "", value: "" };
  }
  defects.value[index] = [];
  if (value) fetchDefects(value, index);
};

function addNewFailure() {
  form.failureSolution.push({
    selectComponent: { label: "", value: "" },
    selectFailure: { label: "", value: "" },
  });
}

onMounted(() => {
  if (form.family) {
    fetchComponents(form.family);
  }
});
</script>

<style scoped>
.colorBase {
  color: var(--cor-contraste);
}
</style>

<template>
  <FormSection @submitted="submitReportForm">
    <template #title> Laudo de Produção </template>
    <template #description> Atualize as informações do produto. </template>
    <template #form>
      <div class="grid grid-cols-12 gap-2 mt-3 text-center">
        <div class="col-span-12">
          <span
            id="uniqueId"
            class="font-medium inline-block colorBase mt-2 dark:text-neutral-200 w-full"
          >
            ID: {{ form.uniqueId }}
          </span>
          <InputError :message="form.errors.uniqueId" />
        </div>
      </div>

      <div class="grid grid-cols-12 gap-2 mt-3">
        <div class="col-span-2 text-center">
          <label
            class="font-medium inline-block colorBase mt-2 dark:text-neutral-200"
            for="status"
          >
            Status
          </label>
        </div>

        <div class="col-span-9">
          <div class="flex flex-col gap-2">
            <Select
              id="statusSelect"
              class="mt-1 block w-full"
              :options="statusOptions"
              v-model="form.status"
              required
            />
            <InputError :message="form.errors.status" />
          </div>
        </div>
      </div>

      <div class="grid grid-cols-12 gap-2 mt-3">
        <div class="col-span-2 text-center">
          <label
            class="font-medium inline-block colorBase mt-2 dark:text-neutral-200"
            for="productSelect"
          >
            SKU
          </label>
        </div>

        <div class="col-span-9">
          <div class="flex flex-col gap-2">
            <TextInput
              id="productSelect"
              v-model="form.product_new.label"
              type="text"
              class="mt-1 block w-full"
              autocomplete="off"
              required
              readonly
            />
            <InputError :message="form.errors.product" />
          </div>
        </div>
      </div>

      <div class="grid grid-cols-12 gap-2 mt-3">
        <div class="col-span-2 text-center">
          <label
            class="font-medium inline-block colorBase mt-2 dark:text-neutral-200"
            for="familyInput"
          >
            Família
          </label>
        </div>

        <div class="col-span-9">
          <div class="flex flex-col gap-2">
            <TextInput
              id="familyInput"
              type="text"
              v-model="form.family"
              class="mt-1 block w-full"
              autocomplete="off"
              placeholder="Selecione o produto"
              readonly
            />
            <InputError :message="form.errors.family" />
          </div>
        </div>
      </div>

      <div class="grid grid-cols-12 gap-2 mt-3">
        <div class="col-span-2 text-center">
          <label
            class="font-medium inline-block colorBase mt-2 dark:text-neutral-200"
            for="product_lot"
          >
            Lote
          </label>
        </div>

        <div class="col-span-9">
          <div class="flex flex-col gap-2">
            <TextInput
              id="product_lot"
              v-model="form.product_lot"
              type="text"
              class="mt-1 block w-full"
              autocomplete="off"
              required
              readonly
            />
            <InputError :message="form.errors.product_lot" />
          </div>
        </div>
      </div>

      <div class="grid grid-cols-12 gap-2 mt-3">
        <div class="col-span-2 text-center">
          <label
            class="font-medium inline-block colorBase mt-2 dark:text-neutral-200"
            for="obs"
          >
            Observação
          </label>
        </div>

        <div class="col-span-9">
          <div class="flex flex-col gap-2">
            <TextInput
              id="obs"
              v-model="form.observation"
              type="text"
              class="mt-1 block w-full"
              autocomplete="off"
              readonly
            />
            <InputError :message="form.errors.observation" />
          </div>
        </div>
      </div>

      <div v-if="form.family != 'AUDIO'" class="grid grid-cols-12 gap-2 mt-3">
        <div class="col-span-2 text-center">
          <label
            class="font-medium inline-block colorBase mt-2 dark:text-neutral-200"
            for="serialNumber"
          >
            Serial Number
          </label>
        </div>

        <div class="col-span-9">
          <div class="flex flex-col gap-2">
            <TextInput
              id="serialNumber"
              v-model="form.serial_number"
              type="text"
              class="mt-1 block w-full"
              autocomplete="off"
              required
            />
            <InputError :message="form.errors.serial_number" />
          </div>
        </div>
      </div>

      <div
        v-if="form.family === 'SMARTPHONE' || form.family === 'TABLET'"
        class="grid grid-cols-12 gap-2 mt-3"
      >
        <div class="col-span-2 text-center">
          <label
            class="font-medium inline-block colorBase mt-2 dark:text-neutral-200"
            for="imei1"
          >
            Imei 1
          </label>
        </div>

        <div class="col-span-9">
          <div class="flex flex-col gap-2">
            <TextInput
              id="imei1"
              v-model="form.imei1"
              type="text"
              class="mt-1 block w-full"
              autocomplete="off"
              required
            />
            <InputError :message="form.errors.imei1" />
          </div>
        </div>
      </div>

      <div
        v-if="form.family === 'SMARTPHONE' || form.family === 'TABLET'"
        class="grid grid-cols-12 gap-2 mt-3"
      >
        <div class="col-span-2 text-center">
          <label
            class="font-medium inline-block colorBase mt-2 dark:text-neutral-200"
            for="imei2"
          >
            Imei 2
          </label>
        </div>

        <div class="col-span-9">
          <div class="flex flex-col gap-2">
            <TextInput
              id="imei2"
              v-model="form.imei2"
              type="text"
              class="mt-1 block w-full"
              autocomplete="off"
              required
            />
            <InputError :message="form.errors.imei2" />
          </div>
        </div>
      </div>
      <div
        id="failure-container"
        v-for="(defectSolution, index) in form.failureSolution"
        :key="index"
      >
        <div class="mt-5">
          <div class="grid grid-cols-12 gap-2">
            <!-- Componente -->
            <div class="col-span-2 text-center">
              <label
                class="font-medium inline-block colorBase mt-2 dark:text-neutral-200"
                :for="'componentSelect' + index"
              >
                Componente
              </label>
            </div>
            <div class="col-span-8" id="componentSelectLenght">
              <div class="flex flex-col gap-2">
                <Select
                  :id="'defectSelect' + index"
                  class="mt-1 block w-full"
                  :options="components"
                  v-model="form.failureSolution[index].selectComponent"
                  @selected="(option) => onSelectChange(option, 'component', index)"
                  label="label"
                  required
                />
                <InputError :message="form.errors['component' + index]" />
              </div>
            </div>

            <div class="col-span-1" id="addButton">
              <button
                type="button"
                @click="addNewFailure()"
                class="flex items-center justify-center mt-2 size-8 text-sm rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-5 w-5"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M12 4.5v15m7.5-7.5h-15"
                  />
                </svg>
              </button>
            </div>
          </div>

          <div class="grid grid-cols-12 gap-2 mt-3">
            <!-- Defeito e Solução -->
            <div class="col-span-2 text-center">
              <label
                class="font-medium inline-block colorBase mt-2 dark:text-neutral-200"
                :for="'defectSelect' + index"
              >
                Defeito e Solução
              </label>
            </div>
            <div class="col-span-9">
              <div class="flex flex-col gap-2">
                <Select
                  :id="'defectSelect' + index"
                  class="mt-1 block w-full"
                  :options="defects[index]"
                  v-model="form.failureSolution[index].selectFailure"
                  label="label"
                  required
                />
                <InputError :message="form.errors['defect_solution' + index]" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <template #actions>
      <ActionMessage :on="form.recentlySuccessful" class="me-3"> Salvo. </ActionMessage>
      <PrimaryButton
        :class="{ 'opacity-25': form.processing }"
        :disabled="form.processing"
        >Salvar</PrimaryButton
      >
    </template>
  </FormSection>
</template>
