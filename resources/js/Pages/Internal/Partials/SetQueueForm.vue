<script setup>
import $ from "jquery";
import { ref, defineProps, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import ActionMessage from "@/Components/ActionMessage.vue";
import FormSection from "@/Components/FormSection.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import Select from "@/Components/Select.vue";
import Datatable from "@/Components/DatatableServerSide.vue";
import "moment-timezone";

const uniqueId = ref(null);

const props = defineProps({
  allowedUsers: {
    type: Array,
    required: true,
    default: () => [],
  },
  userId: {
    type: Object,
    required: false,
    default: () => null,
  },
});

const products = ref([]);
const fetchProducts = async (search) => {
  try {
    const { data } = await axios.post(route("findProducts.show"), { sku: search });

    if (Array.isArray(data)) {
      products.value = data.map((item) => ({
        label: item.sku,
        value: item.family,
      }));
    }
  } catch (error) {
    console.error(`Erro ao carregar produtos: ${error.message}`);
  }
};

const form = useForm({
  uniqueId: "",
  product: "",
  quantity: "1",
  responsableSelect: null,
});

const tableHeaders = ref([
  { name: "Entrada", data: "entries_created_at" },
  { name: "ID", data: "unique_id" },
  { name: "Fila", data: "queue_created_at" },
  { name: "Responsável", data: "name" },
  { name: "Produto", data: "product" },
  { name: "Excluir", data: "button" },
]);

const tableId = ref("ManageQueues");

function deleteQueue(queueId) {
  form.delete(route("set-queue.delete", queueId), {
    preserveScroll: true,
    onSuccess: (response) => {
      console.log(response);
      $(`#ManageQueues`).DataTable().ajax.reload();
    },
    onError: (error) => {
      console.log(error);
    },
  });
}

$(document).on("click", ".delete-btn", function () {
  const entryId = $(this).data("id");
  deleteQueue(entryId);
});

const submitQueueForm = () => {
  form.post(route("set-queue.create"), {
    errorBag: "submitQueueForm",
    preserveScroll: true,
    onSuccess: () => {
      $(`#ManageQueues`).DataTable().ajax.reload();
      form.reset("uniqueId", "product", "quantity");
    },
    onError: () => {
      if (form.errors.uniqueId) {
        form.reset("uniqueId");
        uniqueId.value.focus();
      }
    },
  });
};

watch(
  () => form.responsableSelect?.id,
  (userId) => {
    if (userId) {
      $(`#ManageQueues`).DataTable().ajax.reload();
    }
  }
);
</script>

<template>
  <FormSection @submitted="submitQueueForm">
    <template #title> Fila de produtos </template>

    <template #description>
      Os produtos deverão ser colocados na fila para serem retrabalhos.
    </template>

    <template #form>
      <!-- Formulário -->
      <div class="space-y-6 mt-8">
        <div>
          <label
            class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
            for="responsableSelect"
          >
            Responsável
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
        <!-- Label para o campo de nova função -->
        <div
          v-if="$page.props.auth.user.current_team.name == 'SAC' && !!form.responsableSelect"
          class="col-span-3 sm:col-span-2"
        >
          <label
            class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
            for="uniqueId"
          >
            Protocolo
          </label>

          <!-- Campo de entrada para a nova função -->
          <div class="col-span-9 sm:col-span-8">
            <div class="flex flex-col gap-2">
              <TextInput
                id="uniqueId"
                ref="uniqueId"
                rules="required|max:10|regex:/^[A-Z][a-zA-Z]*$/"
                v-model="form.uniqueId"
                type="text"
                class="mt-1 block w-full"
                autocomplete="off"
              />
              <!-- Mensagem de erro abaixo do campo de entrada -->
              <InputError :message="form.errors.uniqueId" />
            </div>
          </div>
        </div>

        <div
          v-if="$page.props.auth.user.current_team.name == 'RMA'"
          v-show="!!form.responsableSelect"
          class="col-span-3 sm:col-span-2"
        >
          <label
            class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
            for="product"
          >
            Produto
          </label>

          <!-- Campo de entrada para a nova função -->
          <div class="col-span-9 sm:col-span-8">
            <div class="flex flex-col gap-2">
              <Select
                id="productSelect"
                class="mt-1 block w-full"
                :options="products"
                v-model="form.product"
                @search-change="fetchProducts"
                label="label"
                required
              />
              <!-- Mensagem de erro abaixo do campo de entrada -->
              <InputError :message="form.errors.product" />
            </div>
          </div>
        </div>

        <div
          v-if="$page.props.auth.user.current_team.name == 'RMA'"
          v-show="!!form.responsableSelect"
          class="col-span-3 sm:col-span-2"
        >
          <label
            class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
            for="quantity"
          >
            Quantidade
          </label>

          <!-- Campo de entrada para a nova função -->
          <div class="col-span-9 sm:col-span-8">
            <div class="flex flex-col gap-2">
              <TextInput
                id="quantity"
                ref="quantity"
                v-model="form.quantity"
                type="number"
                class="mt-1 block w-full"
                autocomplete="off"
              />
              <!-- Mensagem de erro abaixo do campo de entrada -->
              <InputError :message="form.errors.quantity" />
            </div>
          </div>
        </div>

        <!-- Botão de salvar -->
        <div class="col-span-12 sm:col-span-12 flex justify-end mt-4 sm:mt-2">
          <PrimaryButton
            :class="{ 'opacity-25': form.processing }"
            :disabled="form.processing"
          >
            Atribuir
          </PrimaryButton>
        </div>

        <!-- Mensagem de sucesso -->
        <div class="col-span-12 sm:col-span-12 flex justify-end mt-2">
          <ActionMessage :on="form.recentlySuccessful">
            {{ $page.props.flash.message }}
          </ActionMessage>
        </div>
      </div>

      <Datatable
        :order-by="[2, 'desc']"
        :thead="tableHeaders"
        :id="tableId"
        :ajax="route('set-queue.datatable', { id: form.responsableSelect?.id })"
      />
    </template>
  </FormSection>
</template>
