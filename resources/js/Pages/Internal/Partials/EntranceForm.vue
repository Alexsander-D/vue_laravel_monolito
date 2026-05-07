<script setup>
import { computed, ref, defineProps } from "vue";
import { useForm } from "@inertiajs/vue3";
import ActionMessage from "@/Components/ActionMessage.vue";
import FormSection from "@/Components/FormSection.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import Datatable from "@/Components/Datatable.vue";
import moment from "moment";
import "moment-timezone";
import Swal from "sweetalert2";

const props = defineProps({
  tracking: {
    type: Array,
  },
});

const form = useForm({
  trackingInput: "",
});

const entranceForm = () => {
  form.post(route("collect-tracking.create"), {
    errorBag: "entranceForm",
    preserveScroll: true,
    onSuccess: (data) => {
      form.reset();
      if (data.props.flash.message) {
        Swal.fire({
          icon: "warning",
          title: "A/C ADMINISTRATIVO",
          text: data.props.flash.message,
          showConfirmButton: true,
        });
      }
    },
    onError: (error) => {
      console.log(error);
    },
  });
};

const tableHeaders = ref([
  { name: "ID" },
  { name: "DATA" },
  { name: "RASTREIO" },
  { name: "RESPONSÁVEL" },
  { name: "STATUS" },
]);

const tableData = computed(() => {
  return props.tracking.map((row) => ({
    id: row.id,
    updated_at: moment.utc(row.updated_at).format("DD/MM/YY HH:mm:ss"),
    tracking: row.tracking,
    responsable: row.responsable,
    status: row.status,
  }));
});

const tableId = ref("ManageTrackingProtocol");
</script>

<template>
  <FormSection @submitted="entranceForm">
    <template #title>
      <div class="flex justify-between items-center">
        <div class="flex-grow text-center">
          Protocolo de Rastreamento: {{ props.tracking.length ?? 0 }}
        </div>
      </div>
    </template>

    <template #description>
      Os produtos aqui cadastrados, deverão ser separados e entregue ao responsável.
    </template>

    <template #form>
      <div class="grid grid-cols-12 gap-2 sm:gap-6 mt-8 items-center">
        <div class="col-span-2 text-center">
          <label
            class="font-medium inline-block text-sm text-gray-800 mt-2.5 dark:text-neutral-200"
            for="trackingInput"
          >
            Rastreio
          </label>
        </div>

        <div class="col-span-8">
          <div class="flex items-center gap-2">
            <TextInput
              id="trackingInput"
              ref="trackingInput"
              rules="required|max:10|regex:/^[A-Z][a-zA-Z]*$/"
              v-model="form.trackingInput"
              type="text"
              class="mt-1 block w-full"
              autocomplete="off"
              autofocus
            />
          </div>
          <InputError :message="form.errors.trackingInput" />
        </div>

        <div class="col-span-2"></div>

        <div class="col-span-12 sm:col-span-12 flex justify-end mt-4 sm:mt-2">
          <PrimaryButton
            :class="{ 'opacity-25': form.processing }"
            :disabled="form.processing"
          >
            Salvar
          </PrimaryButton>
        </div>

        <div class="col-span-12 sm:col-span-12 flex justify-end mt-2">
          <ActionMessage :on="form.recentlySuccessful"> Salvo. </ActionMessage>
        </div>
      </div>
      <Datatable :thead="tableHeaders" :tbody="tableData" :id="tableId" />
    </template>
  </FormSection>
</template>
