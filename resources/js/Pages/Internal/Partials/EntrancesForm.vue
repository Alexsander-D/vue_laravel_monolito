<script setup>
import moment from "moment";
import "moment-timezone";
import { computed, ref, defineProps } from "vue";
import FormSection from "@/Components/FormSection.vue";
import Datatable from "@/Components/Datatable.vue";
import { useForm } from "@inertiajs/vue3";
import DateFilter from "@/Components/DateFilter.vue";

const props = defineProps({
  tracking: {
    type: Array,
    required: true,
  },
  date: {
    type: Object,
  },
});

const startDate = ref(
  props.date?.startDate ?? moment().startOf("month").format("YYYY-MM-DD")
);
const endDate = ref(props.date?.endDate ?? moment().endOf("month").format("YYYY-MM-DD"));

const form = useForm({
  startDate: startDate.value,
  endDate: endDate.value,
});

const submitForm = async () => {
  form.get(route("collect-tracking.show"), {
    errorBag: "submitForm",
    preserveScroll: true,
    onSuccess: () => {},
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
  <FormSection>
    <template #title>
      <div class="flex justify-between items-center">
        <div class="flex-grow text-center">Protocolo de Rastreamento</div>
      </div>
    </template>

    <template #description>
      <DateFilter
        v-model:startDate="form.startDate"
        v-model:endDate="form.endDate"
        @submit="submitForm"
      />
    </template>

    <template #form>
      <Datatable :thead="tableHeaders" :tbody="tableData" :id="tableId" />
    </template>
  </FormSection>
</template>
