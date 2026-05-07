<script setup>
import $ from "jquery";
import { onMounted, ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import FormSection from "@/Components/FormSection.vue";
import Datatable from "@/Components/DatatableServerSide.vue";
import DialogModal from "@/Components/DialogModal.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import Select from "@/Components/Select.vue";
import ActionMessage from "@/Components/ActionMessage.vue";
import Swal from "sweetalert2";
import BaseRadio from "@/Components/Radio.vue";

const currentProduct = ref();
const listedFamilies = ref([
  "AUDIO",
  "AUTOMOTIVO",
  "BABY",
  "ELETRO BEAUTY",
  "FEATURE PHONE",
  "MIDIA",
  "MOBILIDADE ELETRICA",
  "PERSONAL COMPUTER",
  "SMARTPHONE",
  "TABLET",
  "VIDEO",
  "WEARABLE",
]);

const form = useForm({
  family: "",
  ean: "",
  sku: "",
  description: "",
  type: "",
  line: "",
  group: "",
  sub_group: "",
  price: "",
  customization: "0",
});

const submitProductForm = () => {
  form.post(route("products.create"), {
    errorBag: "submitProductForm",
    preserveScroll: true,
    onSuccess: (success) => {
      $(`#ManageProducts`).DataTable().ajax.reload();
      // console.log(success);
    },
    onError: (error) => {
      console.log(error);
      form.reset();
    },
  });
};

const modalForm = useForm({
  id: "",
  family: "",
  ean: "",
  sku: "",
  description: "",
  customization: "0",
});

/**
 * Submete o formulário de atualização de produto.
 *
 * @param {Object} - Formulário com as informações do produto.
 *
 * @returns {Promise} - Uma promessa com o resultado da requisição.
 */
const submitUpdateProductForm = () => {
  modalForm.post(route("products.update"), {
    errorBag: "submitUpdateProductForm",
    preserveScroll: true,
    onSuccess: (response) => {
      $(`#ManageProducts`).DataTable().ajax.reload();
      currentlyUpdateProduct.value = false;
      Swal.fire({
        icon: "success",
        title: "Produto Atualizado!",
        text: response.props.flash.message,
        timer: 1800,
        showConfirmButton: false,
        timerProgressBar: true,
      });
    },
    onError: (error) => {
      console.log(error);
      form.reset();
    },
  });
};

const tableHeaders = ref([
  { name: "FAMÍLIA", data: "family" },
  { name: "EAN", data: "ean" },
  { name: "SKU", data: "sku" },
  { name: "DESCRIÇÃO", data: "description" },
  { name: "CUSTOMIZAÇÃO", data: "customization" },
  { name: "", data: "action" },
]);

const currentlyRegisterProduct = ref(false);
const currentlyUpdateProduct = ref(false);

function openProductModal(productInfo) {
  PopulateModal(productInfo);
  currentlyUpdateProduct.value = true;
}

/**
 * É usado para atualizar um produto.
 *
 * @param {Object} product - Informações do produto.
 */
function PopulateModal(productInfo) {
  modalForm.id = productInfo.id;
  modalForm.family = productInfo.family;
  modalForm.ean = productInfo.ean;
  modalForm.sku = productInfo.sku;
  modalForm.description = productInfo.description;
  modalForm.customization = productInfo.customization;
}

onMounted(() => {
  window.addEventListener("open-product-modal", (e) => {
    openProductModal(e.detail);
  });
});
</script>

<template>
  <FormSection>
    <template #title>
      <div class="flex justify-between items-center">
        <div class="flex-grow text-center">Produtos</div>
      </div>
    </template>

    <template #description>
      <div class="flex-grow">Lista de produtos cadastrados</div>

      <button
        type="button"
        @click="currentlyRegisterProduct = true"
        class="flex items-center justify-center gap-2 size-8 text-sm rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none"
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
    </template>

    <template #form>
      <DialogModal
        :show="currentlyRegisterProduct"
        @close="currentlyRegisterProduct = false"
      >
        <template #title>
          <div class="flex justify-between items-center">
            <div class="flex-grow text-center">Adicionar Produto</div>
          </div>
        </template>

        <template #content>
          <!-- Formulário -->
          <div class="grid grid-cols-12 gap-2 sm:gap-6 mt-2 items-center">
            <div class="col-span-12">
              <div class="col-span-3 sm:col-span-2">
                <label
                  class="font-medium inline-block text-sm text-gray-800 mt-1 dark:text-neutral-200"
                  for="family"
                >
                  Família:
                </label>
              </div>

              <div class="col-span-9 sm:col-span-8">
                <div class="flex flex-col gap-2">
                  <Select
                    id="family"
                    class="mt-1 block w-full"
                    :options="listedFamilies"
                    v-model="form.family"
                    required
                  />
                  <InputError :message="form.errors.family" />
                </div>
              </div>
            </div>

            <div class="col-span-12">
              <div class="col-span-3 sm:col-span-2">
                <label
                  class="font-medium inline-block text-sm text-gray-800 mt-1 dark:text-neutral-200"
                  for="ean"
                >
                  Ean:
                </label>
              </div>

              <div class="col-span-9 sm:col-span-8">
                <div class="flex flex-col gap-2">
                  <TextInput
                    id="ean"
                    v-model="form.ean"
                    type="text"
                    class="mt-1 block w-full"
                    placeholder="Preencha todas as informações"
                    autocomplete="off"
                  />
                  <InputError :message="form.errors.ean" />
                </div>
              </div>
            </div>

            <div class="col-span-12">
              <div class="col-span-3 sm:col-span-2">
                <label
                  class="font-medium inline-block text-sm text-gray-800 mt-1 dark:text-neutral-200"
                  for="sku"
                >
                  Sku:
                </label>
              </div>

              <div class="col-span-9 sm:col-span-8">
                <div class="flex flex-col gap-2">
                  <TextInput
                    id="sku"
                    v-model="form.sku"
                    type="text"
                    class="mt-1 block w-full"
                    placeholder="Preencha todas as informações"
                    autocomplete="off"
                  />
                  <InputError :message="form.errors.sku" />
                </div>
              </div>
            </div>

            <div class="col-span-12">
              <div class="col-span-3 sm:col-span-2">
                <label
                  class="font-medium inline-block text-sm text-gray-800 mt-1 dark:text-neutral-200"
                  for="description"
                >
                  Descrição:
                </label>
              </div>

              <div class="col-span-9 sm:col-span-8">
                <div class="flex flex-col gap-2">
                  <TextInput
                    id="description"
                    v-model="form.description"
                    type="text"
                    class="mt-1 block w-full"
                    placeholder="Preencha todas as informações"
                    autocomplete="off"
                  />
                  <InputError :message="form.errors.description" />
                </div>
              </div>
            </div>

            <div class="col-span-12">
              <div class="col-span-3 sm:col-span-2">
                <label
                  class="font-medium inline-block text-sm text-gray-800 mt-1 dark:text-neutral-200 mb-2"
                  for="description"
                >
                  Customização:
                </label>
              </div>

              <div class="col-span-9 sm:col-span-8">
                <div class="flex flex-col gap-2">
                  <div class="flex items-center">
                    <div class="flex items-center gap-6">
                      <div class="flex items-center gap-2">
                        <input
                          type="radio"
                          id="customizationNormal"
                          name="customization"
                          value="0"
                          v-model="form.customization"
                          class="colorBase shrink-0 mt-0.5 rounded-full text-blue-600 focus:ring-blue-500 checked:border-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-800 dark:border-neutral-700 dark:checked:bg-blue-500 dark:checked:border-blue-500 dark:focus:ring-offset-gray-800"
                        />
                        <label
                          for="customizationNormal"
                          class="text-sm text-gray-500 dark:text-neutral-400"
                        >
                          Normal
                        </label>
                      </div>

                      <div class="flex items-center gap-2">
                        <input
                          type="radio"
                          id="customizationCustom"
                          name="customization"
                          value="1"
                          v-model="form.customization"
                          class="colorBase shrink-0 mt-0.5 rounded-full text-blue-600 focus:ring-blue-500 checked:border-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-800 dark:border-neutral-700 dark:checked:bg-blue-500 dark:checked:border-blue-500 dark:focus:ring-offset-gray-800"
                        />
                        <label
                          for="customizationCustom"
                          class="text-sm text-gray-500 dark:text-neutral-400"
                        >
                          Customizado
                        </label>
                      </div>
                    </div>
                  </div>
                  <InputError :message="form.errors.customization" />
                </div>
              </div>
            </div>

            <!-- <div class="col-span-12">
              <div class="col-span-3 sm:col-span-2">
                <label
                  class="font-medium inline-block text-sm text-gray-800 mt-1 dark:text-neutral-200"
                  for="type"
                >
                  Tipo:
                </label>
              </div>

              <div class="col-span-9 sm:col-span-8">
                <div class="flex flex-col gap-2">
                  <TextInput
                    id="type"
                    v-model="form.type"
                    type="text"
                    class="mt-1 block w-full"
                    placeholder="Preencha todas as informações"
                    autocomplete="off"
                  />
                  <InputError :message="form.errors.type" />
                </div>
              </div>
            </div>

            <div class="col-span-12">
              <div class="col-span-3 sm:col-span-2">
                <label
                  class="font-medium inline-block text-sm text-gray-800 mt-1 dark:text-neutral-200"
                  for="line"
                >
                  Linha:
                </label>
              </div>

              <div class="col-span-9 sm:col-span-8">
                <div class="flex flex-col gap-2">
                  <TextInput
                    id="line"
                    v-model="form.line"
                    type="text"
                    class="mt-1 block w-full"
                    placeholder="Preencha todas as informações"
                    autocomplete="off"
                  />
                  <InputError :message="form.errors.line" />
                </div>
              </div>
            </div>

            <div class="col-span-12">
              <div class="col-span-3 sm:col-span-2">
                <label
                  class="font-medium inline-block text-sm text-gray-800 mt-1 dark:text-neutral-200"
                  for="group"
                >
                  Grupo:
                </label>
              </div>

              <div class="col-span-9 sm:col-span-8">
                <div class="flex flex-col gap-2">
                  <TextInput
                    id="group"
                    v-model="form.group"
                    type="text"
                    class="mt-1 block w-full"
                    placeholder="Preencha todas as informações"
                    autocomplete="off"
                  />
                  <InputError :message="form.errors.group" />
                </div>
              </div>
            </div>

            <div class="col-span-12">
              <div class="col-span-3 sm:col-span-2">
                <label
                  class="font-medium inline-block text-sm text-gray-800 mt-1 dark:text-neutral-200"
                  for="sub_group"
                >
                  Sub Grupo:
                </label>
              </div>

              <div class="col-span-9 sm:col-span-8">
                <div class="flex flex-col gap-2">
                  <TextInput
                    id="sub_group"
                    v-model="form.sub_group"
                    type="text"
                    class="mt-1 block w-full"
                    placeholder="Preencha todas as informações"
                    autocomplete="off"
                  />
                  <InputError :message="form.errors.sub_group" />
                </div>
              </div>
            </div>

            <div class="col-span-12">
              <div class="col-span-3 sm:col-span-2">
                <label
                  class="font-medium inline-block text-sm text-gray-800 mt-1 dark:text-neutral-200"
                  for="price"
                >
                  Valor:
                </label>
              </div>

              <div class="col-span-9 sm:col-span-8">
                <div class="flex flex-col gap-2">
                  <TextInput
                    id="price"
                    v-model="form.price"
                    type="text"
                    class="mt-1 block w-full"
                    placeholder="Preencha todas as informações"
                    autocomplete="off"
                  />
                  <InputError :message="form.errors.price" />
                </div>
              </div>
            </div> -->

            <!-- Botão de salvar -->
            <div class="col-span-12 sm:col-span-12 flex justify-end mt-2 sm:mt-1">
              <PrimaryButton
                @click="submitProductForm"
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
              >
                Salvar
              </PrimaryButton>
            </div>
          </div>
        </template>
        <template #footer>
          <!-- Mensagem de sucesso -->
          <div class="col-span-12 sm:col-span-12 flex justify-end mt-1">
            <ActionMessage :on="form.recentlySuccessful"> Salvo. </ActionMessage>
          </div>
        </template>
      </DialogModal>

      <DialogModal :show="currentlyUpdateProduct" @close="currentlyUpdateProduct = false">
        <template #title>
          <div class="flex justify-between items-center">
            <div class="flex-grow text-center">Editar Produto</div>
          </div>
        </template>

        <template #content>
          <!-- Formulário -->
          <div class="grid grid-cols-12 gap-2 sm:gap-6 mt-2 items-center">
            <div class="col-span-12">
              <div class="col-span-3 sm:col-span-2">
                <label
                  class="font-medium inline-block text-sm text-gray-800 mt-1 dark:text-neutral-200"
                  for="family"
                >
                  Família:
                </label>
              </div>

              <div class="col-span-9 sm:col-span-8">
                <div class="flex flex-col gap-2">
                  <Select
                    id="family"
                    class="mt-1 block w-full"
                    :options="listedFamilies"
                    v-model="modalForm.family"
                    required
                  />
                  <InputError :message="modalForm.errors.family" />
                </div>
              </div>
            </div>

            <div class="col-span-12">
              <div class="col-span-3 sm:col-span-2">
                <label
                  class="font-medium inline-block text-sm text-gray-800 mt-1 dark:text-neutral-200"
                  for="ean"
                >
                  Ean:
                </label>
              </div>

              <div class="col-span-9 sm:col-span-8">
                <div class="flex flex-col gap-2">
                  <TextInput
                    id="ean"
                    v-model="modalForm.ean"
                    type="text"
                    class="mt-1 block w-full"
                    placeholder="Preencha todas as informações"
                    autocomplete="off"
                  />
                  <InputError :message="modalForm.errors.ean" />
                </div>
              </div>
            </div>

            <div class="col-span-12">
              <div class="col-span-3 sm:col-span-2">
                <label
                  class="font-medium inline-block text-sm text-gray-800 mt-1 dark:text-neutral-200"
                  for="sku"
                >
                  Sku:
                </label>
              </div>

              <div class="col-span-9 sm:col-span-8">
                <div class="flex flex-col gap-2">
                  <TextInput
                    id="sku"
                    v-model="modalForm.sku"
                    type="text"
                    class="mt-1 block w-full"
                    placeholder="Preencha todas as informações"
                    autocomplete="off"
                  />
                  <InputError :message="modalForm.errors.sku" />
                </div>
              </div>
            </div>

            <div class="col-span-12">
              <div class="col-span-3 sm:col-span-2">
                <label
                  class="font-medium inline-block text-sm text-gray-800 mt-1 dark:text-neutral-200"
                  for="description"
                >
                  Descrição:
                </label>
              </div>

              <div class="col-span-9 sm:col-span-8">
                <div class="flex flex-col gap-2">
                  <TextInput
                    id="description"
                    v-model="modalForm.description"
                    type="text"
                    class="mt-1 block w-full"
                    placeholder="Preencha todas as informações"
                    autocomplete="off"
                  />
                  <InputError :message="modalForm.errors.description" />
                </div>
              </div>
            </div>

            <div class="col-span-12">
              <div class="col-span-3 sm:col-span-2">
                <label
                  class="font-medium inline-block text-sm text-gray-800 mt-1 dark:text-neutral-200 mb-2"
                  for="description"
                >
                  Customização:
                </label>
              </div>

              <div class="col-span-9 sm:col-span-8">
                <div class="flex flex-col gap-2">
                  <div class="flex items-center">
                    <div class="flex items-center gap-6">
                      <div class="flex items-center gap-2">
                        <input
                          type="radio"
                          id="customizationNormalModal"
                          name="customization"
                          value="0"
                          v-model="modalForm.customization"
                          class="colorBase shrink-0 mt-0.5 rounded-full text-blue-600 focus:ring-blue-500 checked:border-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-800 dark:border-neutral-700 dark:checked:bg-blue-500 dark:checked:border-blue-500 dark:focus:ring-offset-gray-800"
                        />
                        <label
                          for="customizationNormalModal"
                          class="text-sm text-gray-500 dark:text-neutral-400"
                        >
                          Normal
                        </label>
                      </div>

                      <div class="flex items-center gap-2">
                        <input
                          type="radio"
                          id="customizationCustomModal"
                          name="customization"
                          value="1"
                          v-model="modalForm.customization"
                          class="colorBase shrink-0 mt-0.5 rounded-full text-blue-600 focus:ring-blue-500 checked:border-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-800 dark:border-neutral-700 dark:checked:bg-blue-500 dark:checked:border-blue-500 dark:focus:ring-offset-gray-800"
                        />
                        <label
                          for="customizationCustomModal"
                          class="text-sm text-gray-500 dark:text-neutral-400"
                        >
                          Customizado
                        </label>
                      </div>
                    </div>
                  </div>
                  <InputError :message="form.errors.customization" />
                </div>
              </div>
            </div>

            <!-- Botão de salvar -->
            <div class="col-span-12 sm:col-span-12 flex justify-end mt-2 sm:mt-1">
              <PrimaryButton
                @click="submitUpdateProductForm"
                :class="{ 'opacity-25': modalForm.processing }"
                :disabled="modalForm.processing"
              >
                Salvar
              </PrimaryButton>
            </div>
          </div>
        </template>
        <template #footer>
          <!-- Mensagem de sucesso -->
          <div class="col-span-12 sm:col-span-12 flex justify-end mt-1">
            <ActionMessage :on="modalForm.recentlySuccessful"> Salvo. </ActionMessage>
          </div>
        </template>
      </DialogModal>

      <Datatable
        :thead="tableHeaders"
        id="ManageProducts"
        :ajax="route('products.datatable')"
        :export-url="route('products.export')"
      />
    </template>
  </FormSection>
</template>
