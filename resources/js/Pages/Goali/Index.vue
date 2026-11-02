<script setup>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import Select from "@/Components/Select.vue";
import Datatable from "@/Components/Datatable.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import { router, useForm } from "@inertiajs/vue3";
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import Swal from "sweetalert2";

const props = defineProps({
  records: { type: Array, required: true },
  filters: { type: Object, required: true },
  isAdmin: { type: Boolean, default: false },
  solicitations: { type: Array, default: () => [] },
  drivers: { type: Array, default: () => [] },
  tripStatuses: { type: Array, default: () => [] },
  summary: { type: Object, required: true },
  isDriver: { type: Boolean, default: false },
});

const filters = useForm({ ...props.filters });
const statusOptions = ["ENTRADA", "SAÍDA"];
const editingId = ref(null);
const statusTransitionRecord = ref(null);
const editForm = useForm({});
const statusTransitionForm = useForm({ trip_status: "" });
const reportUrl = computed(() => route("goali.export", filters.data()));
const sortOrder = computed(() => filters.sortOrder || "desc");
const selectedEditDriver = computed({
  get: () => props.drivers.find((driver) => driver.id === Number(editForm.driver_id)) || null,
  set: (driver) => {
    editForm.driver_id = driver?.id ?? null;
  },
});
const statusTransitionOptions = computed(() => {
  if (!statusTransitionRecord.value) {
    return [];
  }

  if (props.isDriver) {
    return ["Finalizada"];
  }

  const nextStatus = nextTripStatus(statusTransitionRecord.value.trip_status);
  return nextStatus ? [nextStatus] : [];
});

const tableId = "goali-trips-datatable";
const tableHeaders = [
  { name: "Data / Hora" },
  { name: "Tipo" },
  { name: "Status corrida" },
  { name: "Solicitação" },
  { name: "Origem / Destino" },
  { name: "Passageiro" },
  { name: "Motorista" },
  { name: "Responsável" },
  { name: "Valor" },
  { name: "Ações" },
];
const tableData = computed(() => props.records.map((record) => ({
  travelDate: `${date(record.travel_date)} - ${time(record.travel_time)}`,
  status: record.status,
  tripStatus: record.trip_status,
  solicitation: record.solicitation,
  route: `${record.origin} → ${record.destination}`,
  passenger: record.passenger,
  driver: record.driver?.name || "—",
  responsible: record.responsible,
  amount: money(record.amount),
  buttonActions: [
    props.isAdmin && record.trip_status !== "Finalizada"
      ? `<button type="button" class="mr-3 font-semibold text-teal-700 hover:underline" data-goali-action="advance-status" data-record-id="${Number(record.id)}">Avançar para ${nextTripStatus(record.trip_status)}</button>`
      : "",
    props.isAdmin
      ? `<button type="button" class="mr-3 font-semibold text-teal-700 hover:underline" data-goali-action="edit" data-record-id="${Number(record.id)}">Editar</button><button type="button" class="font-semibold text-red-700 hover:underline" data-goali-action="delete" data-record-id="${Number(record.id)}">Excluir</button>`
      : props.isDriver && record.trip_status === "Em Andamento"
        ? `<button type="button" class="font-semibold text-teal-700 hover:underline" data-goali-action="advance-status" data-record-id="${Number(record.id)}">Finalizar corrida</button>`
        : "",
  ].join(""),
})));

watch(() => props.filters, (value) => filters.defaults(value).reset(), { deep: true });
const applyFilters = () => filters.get(route("goali.index"), { preserveState: true, preserveScroll: true });
const startEdit = (record) => {
  editingId.value = record.id;
  editForm.defaults({
    travel_date: record.travel_date,
    travel_time: String(record.travel_time).slice(0, 5),
    status: record.status,
    solicitation: record.solicitation,
    driver_id: record.driver_id,
    origin: record.origin,
    destination: record.destination,
    passenger: record.passenger,
    amount: Number(record.amount).toFixed(2),
    responsible: record.responsible,
  }).reset();
};
const saveEdit = () => {
  if (!editForm.solicitation) {
    editForm.setError("solicitation", "Selecione uma empresa.");
    return;
  }
  editForm.put(route("goali.update", editingId.value), { preserveScroll: true, onSuccess: () => { editingId.value = null; } });
};
const deleteRecord = async (record) => {
  const result = await Swal.fire({
    title: "Excluir registro?",
    text: `O deslocamento de ${record.passenger} será removido.`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Excluir",
    cancelButtonText: "Cancelar",
    confirmButtonColor: "#b42318",
  });
  if (result.isConfirmed) router.delete(route("goali.destroy", record.id), { preserveScroll: true });
};
const openStatusTransition = (record) => {
  statusTransitionRecord.value = record;
  statusTransitionForm.reset();
  statusTransitionForm.trip_status = nextTripStatus(record.trip_status);
};
const closeStatusTransition = () => {
  statusTransitionRecord.value = null;
  statusTransitionForm.reset();
};
const submitStatusTransition = () => {
  if (!statusTransitionRecord.value || !statusTransitionOptions.value.includes(statusTransitionForm.trip_status)) {
    return;
  }

  statusTransitionForm.put(route("goali.advanceStatus", statusTransitionRecord.value.id), {
    preserveScroll: true,
    onSuccess: closeStatusTransition,
  });
};
const handleTableAction = (event) => {
  const button = event.target.closest(`#${tableId} button[data-goali-action]`);
  if (!button) {
    return;
  }

  const record = props.records.find((item) => item.id === Number(button.dataset.recordId));
  if (!record) {
    return;
  }

  if (button.dataset.goaliAction === "edit") {
    startEdit(record);
  } else if (button.dataset.goaliAction === "delete") {
    deleteRecord(record);
  } else if (button.dataset.goaliAction === "advance-status") {
    openStatusTransition(record);
  }
};
const nextTripStatus = (status) => ({
  Criada: "Em Andamento",
  "Em Andamento": "Finalizada",
}[status] || "");
const money = (value) => Number(value || 0).toLocaleString("pt-BR", { style: "currency", currency: "BRL" });
const date = (value) => {
  const datePart = String(value || "").slice(0, 10);
  const [year, month, day] = datePart.split("-");

  return year && month && day ? `${day}/${month}/${year}` : datePart;
};
const time = (value) => {
  const valueString = String(value || "");
  const timePart = valueString.includes("T")
    ? valueString.split("T")[1]
    : valueString;

  return timePart.slice(0, 5);
};

onMounted(() => document.addEventListener("click", handleTableAction));
onBeforeUnmount(() => document.removeEventListener("click", handleTableAction));
</script>

<template>
  <BaseLayout title="Relatório Goali">
    <main class="w-full mx-auto pt-1">
      <section class="rounded-xl bg-white p-4 shadow-lg dark:bg-gray-900 sm:p-6">
        <div class="grid grid-cols-12 gap-4">
          <header class="col-span-12 grid grid-cols-12 gap-4 border-b border-gray-200 pb-5 dark:border-gray-700">
            <div class="col-span-12 text-center">
              <p class="text-xs font-bold uppercase text-teal-700">GT Dionínio Barbosa</p>
              <h1 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">Relatório de deslocamentos</h1>
              <div class="mx-auto mt-3 h-1 w-14 rounded-full bg-blue-600" aria-hidden="true"></div>
            </div>
            <div class="col-span-12 flex flex-wrap justify-end gap-2">
          <a :href="route('goali.form')" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-100">Novo registro</a>
          <a :href="reportUrl" class="rounded-md bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800">Exportar Excel</a>
        </div>
      </header>

      <form class="col-span-12 grid grid-cols-1 gap-3 border-b border-gray-200 pb-5 dark:border-gray-700 sm:grid-cols-2 xl:grid-cols-5" @submit.prevent="applyFilters">
        <label class="filter-label">Data inicial<TextInput v-model="filters.startDate" type="date" class="mt-1 block w-full" /></label>
        <label class="filter-label">Data final<TextInput v-model="filters.endDate" type="date" class="mt-1 block w-full" /></label>
        <label class="filter-label">Tipo<Select v-model="filters.status" :options="statusOptions" placeholder="Todos" :select2="true" /></label>
        <label class="filter-label">Status da corrida<Select v-model="filters.tripStatus" :options="props.tripStatuses" placeholder="Todos" :select2="true" /></label>
        <label class="filter-label">Solicitação<Select v-model="filters.solicitation" :options="props.solicitations" placeholder="Todas" :select2="true" /></label>
        <button class="self-end rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700" type="submit">Filtrar</button>
      </form>

      <section class="col-span-12 grid grid-cols-1 gap-4 border-b border-gray-200 pb-5 dark:border-gray-700 sm:grid-cols-3">
        <div class="border-l-4 border-teal-600 px-4 py-2"><p class="text-sm text-gray-500">Registros no período</p><p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ props.summary.count }}</p></div>
        <div class="border-l-4 border-amber-500 px-4 py-2"><p class="text-sm text-gray-500">Valor total</p><p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ money(props.summary.amount) }}</p></div>
        <div class="border-l-4 border-green-600 px-4 py-2"><p class="text-sm text-gray-500">Valor a receber</p><p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ money(props.summary.receivable) }}</p></div>
      </section>

      <Datatable class="col-span-12 w-full min-w-0" :thead="tableHeaders" :tbody="tableData" :id="tableId" :order-by="[0, sortOrder]" />
        </div>
      </section>

      <div v-if="editingId" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 sm:items-center" @click.self="editingId = null">
        <form class="my-6 max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-lg bg-white p-6 shadow-xl dark:bg-gray-900" @submit.prevent="saveEdit">
          <div class="mb-5 flex items-center justify-between"><h2 class="text-lg font-semibold text-gray-900 dark:text-white">Editar deslocamento</h2><button type="button" aria-label="Fechar" class="text-2xl leading-none text-gray-500" @click="editingId = null">&times;</button></div>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <label class="filter-label">Data<TextInput v-model="editForm.travel_date" type="date" class="mt-1 block w-full" required /><InputError :message="editForm.errors.travel_date" /></label>
            <label class="filter-label">Horário<TextInput v-model="editForm.travel_time" type="time" class="mt-1 block w-full" required /><InputError :message="editForm.errors.travel_time" /></label>
            <label class="filter-label">Tipo<Select v-model="editForm.status" :options="statusOptions" placeholder="Selecione o tipo" :allow-empty="false" :select2="true" required /><InputError :message="editForm.errors.status" /></label>
            <label class="filter-label">Status da corrida<TextInput :model-value="props.records.find((record) => record.id === editingId)?.trip_status || ''" type="text" class="mt-1 block w-full" readonly /></label>
            <label class="filter-label">Motorista<Select v-model="selectedEditDriver" :options="props.drivers" label="name" track-by="id" placeholder="Selecione um motorista" :allow-empty="false" :select2="true" /><InputError :message="editForm.errors.driver_id" /></label>
            <label class="filter-label">Solicitação<Select v-model="editForm.solicitation" :options="props.solicitations" placeholder="Selecione uma empresa" :allow-empty="false" :select2="true" required /><span v-if="editForm.errors.solicitation" class="text-xs text-red-700">{{ editForm.errors.solicitation }}</span></label>
            <label class="filter-label">Origem<TextInput v-model="editForm.origin" type="text" class="mt-1 block w-full" maxlength="255" required /><InputError :message="editForm.errors.origin" /></label>
            <label class="filter-label">Destino<TextInput v-model="editForm.destination" type="text" class="mt-1 block w-full" required /><InputError :message="editForm.errors.destination" /></label>
            <label class="filter-label">Passageiro<TextInput v-model="editForm.passenger" type="text" class="mt-1 block w-full" required /><InputError :message="editForm.errors.passenger" /></label>
            <label class="filter-label">Responsável<TextInput v-model="editForm.responsible" type="text" class="mt-1 block w-full" :readonly="props.isDriver" required /><InputError :message="editForm.errors.responsible" /></label>
            <label class="filter-label">Valor (R$)<TextInput v-model="editForm.amount" type="number" class="mt-1 block w-full" min="0" step="0.01" inputmode="decimal" required /><InputError :message="editForm.errors.amount" /></label>
          </div>
          <p v-if="editForm.hasErrors" class="mt-3 text-sm text-red-700">Verifique os campos informados.</p>
          <div class="mt-6 flex justify-end gap-2"><button type="button" class="rounded-md border border-gray-300 px-4 py-2 text-sm" @click="editingId = null">Cancelar</button><button type="submit" class="rounded-md bg-teal-700 px-4 py-2 text-sm font-semibold text-white" :disabled="editForm.processing">Salvar alterações</button></div>
        </form>
      </div>

      <div
        v-if="statusTransitionRecord"
        class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 p-4"
        @click.self="closeStatusTransition"
      >
        <form
          class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl dark:bg-gray-900"
          @submit.prevent="submitStatusTransition"
        >
          <div class="mb-5 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Atualizar status da corrida</h2>
            <button type="button" aria-label="Fechar" class="text-2xl leading-none text-gray-500" @click="closeStatusTransition">&times;</button>
          </div>
          <label class="filter-label">
            Próximo status
            <Select
              v-model="statusTransitionForm.trip_status"
              :options="statusTransitionOptions"
              placeholder="Selecione o próximo status"
              :allow-empty="false"
              :select2="true"
              required
            />
            <InputError :message="statusTransitionForm.errors.trip_status" />
          </label>
          <div class="mt-6 flex justify-end gap-2">
            <button type="button" class="rounded-md border border-gray-300 px-4 py-2 text-sm" @click="closeStatusTransition">Cancelar</button>
            <button type="submit" class="rounded-md bg-teal-700 px-4 py-2 text-sm font-semibold text-white" :disabled="statusTransitionForm.processing">
              {{ statusTransitionForm.processing ? "Salvando..." : "Confirmar" }}
            </button>
          </div>
        </form>
      </div>
    </main>
  </BaseLayout>
</template>

<style scoped>
.filter-label { display: flex; flex-direction: column; gap: .4rem; color: #4b5563; font-size: .8rem; font-weight: 600; }
</style>