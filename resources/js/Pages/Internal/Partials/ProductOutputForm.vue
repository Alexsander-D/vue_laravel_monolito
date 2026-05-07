<script setup>
import { ref, defineProps } from "vue";
import { useForm } from "@inertiajs/vue3";
import ActionMessage from "@/Components/ActionMessage.vue";
import FormSection from "@/Components/FormSection.vue";
import InputError from "@/Components/InputError.vue";
import Datatable from "@/Components/DatatableServerSide.vue";
import Select from "@/Components/Select.vue";
import "moment-timezone";
import $ from "jquery";

const props = defineProps({
  allowedUsers: { type: Array, default: () => [] },
});

const form = useForm({
  responsableSelect: "",
  queue_id: null,
});

$(document).on("click", ".create-product-output", function () {
  let dataId = $(this).data("id");
  form.queue_id = dataId;

  form.post(route("product_output.create"), {
    preserveScroll: true,
    onSuccess: () => {
      $(`#productOutputDatatable`).DataTable().ajax.reload();
    },
    onError: (error) => {
      console.log(error);
      form.reset();
    },
  });
});

const tableHeaders = ref([
  { name: "ENTRADA", data: "created_at" },
  { name: "RESPONSÁVEL", data: "responsable" },
  { name: "LOTE", data: "lote" },
  { name: "PRODUTO", data: "product" },
  { name: "SERIAL NUMBER", data: "serial_number" },
  { name: "IMEI1", data: "imei1" },
  { name: "IMEI2", data: "imei2" },
  { name: "APONTADO EM", data: "updated_at" },
  { name: "", data: "button" },
  // { name: "SAÍDA", data: "output_created_at" },
]);
</script>

<template>
  <FormSection>
    <template #title>
      <div class="flex justify-between items-center">
        <div class="flex-grow text-center">Embalagem</div>
      </div>
    </template>

    <template #description>
      Selecione o produto que deseja embalar e o responsável.
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
        id="productOutputDatatable"
        :ajax="route('product_output.datatable', form.responsableSelect)"
      />
    </template>
  </FormSection>
</template>
