<script setup>
import { ref, defineProps, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import ActionMessage from "@/Components/ActionMessage.vue";
import FormSection from "@/Components/FormSection.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Datatable from "@/Components/DatatableServerSide.vue";
import Select from "@/Components/Select.vue";
import "moment-timezone";
import $ from "jquery";

const avalaibleProduct = ref(false);

const props = defineProps({
  allowedUsers: { type: Array, default: () => [] },
});

const form = useForm({
  product: "",
  responsableSelect: { type: Array, default: () => [] },
  receivedSelect: { type: Array, default: () => [] },
});

const productTransferAdminForm = () => {
  form.post(route("product_transfer_admin.create"), {
    errorBag: "productTransferAdminForm",
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
      $(`#ProductTransferDatatable`).DataTable().ajax.reload();
    },
    onError: (error) => {
      console.log(error);
      if (form.errors.protocol) {
        form.reset();
      }
    },
  });
};

const tableHeaders = ref([
  { name: "DATA", data: "created_at" },
  { name: "DATA ENVIADO", data: "sent_by" },
  { name: "PRODUTO", data: "product" },
  { name: "RECEBIDO POR", data: "received_by" },
  { name: "DATA RECEBIDO", data: "updated_at" },
  { name: "STATUS", data: "status" },
  { name: "AÇÃO", data: "button", orderable: false, searchable: false },
]);

const products = ref([]);
const fetchProducts = async (search) => {
  try {
    const { data } = await axios.post(route("findProducts.byAdmin"), {
      sku: search,
      userId: form.responsableSelect.id,
    });

    if (Array.isArray(data)) {
      products.value = data.map((item) => ({
        label: item.sku,
        value: item.family,
        queue_id: item.id,
      }));
    }
  } catch (error) {
    console.error(`Erro ao carregar produtos: ${error.message}`);
  }
};

watch(
  () => form.responsableSelect?.id,
  (userId) => {
    if (userId) {
      fetchProducts();
      avalaibleProduct.value = true;
    } else {
      avalaibleProduct.value = false;
    }
  }
);
</script>

<template>
  <FormSection @submitted="productTransferAdminForm">
    <template #title>
      <div class="flex justify-between items-center">
        <div class="flex-grow text-center">Transferir Produto</div>
      </div>
    </template>

    <template #description>
      Selecione o produto que deseja transferir e o responsável pela transferência.
    </template>

    <template #form>
      <!-- Formulário -->
      <div class="space-y-6 mt-8">
        <div>
          <label
            class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
            for="responsableSelect"
          >
            Responsável Envio
          </label>
          <Select
            id="responsableSelect"
            ref="responsableSelect"
            :options="props.allowedUsers"
            v-model="form.responsableSelect"
            label="name"
            required
          />
          <InputError :message="form.errors.responsableSelect" class="mt-2" />
        </div>
        <div v-show="avalaibleProduct">
          <label
            class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
            for="productSelect"
          >
            Produto
          </label>
          <Select
            id="productSelect"
            class="mt-1 block w-full"
            :options="products"
            v-model="form.product"
            @search-change="fetchProducts"
            label="label"
            required
          />
          <InputError :message="form.errors.product" class="mt-2" />
        </div>

        <div v-show="avalaibleProduct">
          <label
            class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
            for="responsableSelect"
          >
            Responsável Recebimento
          </label>
          <Select
            id="receivedSelect"
            ref="receivedSelect"
            :options="props.allowedUsers"
            v-model="form.receivedSelect"
            label="name"
            required
          />
          <InputError :message="form.errors.receivedSelect" class="mt-2" />
        </div>
        <!-- Botão de salvar -->
        <div class="col-span-12 sm:col-span-12 flex justify-end mt-4 sm:mt-2">
          <PrimaryButton
            :class="{ 'opacity-25': form.processing }"
            :disabled="form.processing"
          >
            Transferir
          </PrimaryButton>
        </div>

        <!-- Mensagem de sucesso -->
        <div class="col-span-12 sm:col-span-12 flex justify-end mt-2">
          <ActionMessage :on="form.recentlySuccessful">
            {{ $page.props.flash.message }}
          </ActionMessage>
        </div>
      </div>
      <!-- Fim do Formulário -->
      <Datatable
        :thead="tableHeaders"
        id="ProductTransferDatatable"
        :ajax="route('product_transfer.datatable')"
      />
    </template>
  </FormSection>
</template>
