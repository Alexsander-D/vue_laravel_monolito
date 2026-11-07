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
  currentDriverId: { type: Number, default: null },
});

const filters = useForm({ ...props.filters });
const statusOptions = ["ENTRADA", "SAÍDA"];
const editingId = ref(null);
const editForm = useForm({});
const reportUrl = computed(() => route("goali.export", filters.data()));
const sortOrder = computed(() => filters.sortOrder || "desc");
const editingRecord = computed(() => props.records.find((record) => String(record.id) === String(editingId.value)) || null);
const tableKey = computed(() => props.records
  .map((record) => `${record.id}:${record.updated_at || ""}`)
  .join("|"));
const editTripStatusOptions = computed(() => {
  if (!editingRecord.value) {
    return [];
  }

  if (props.isDriver) {
    if (editingRecord.value.trip_status === "Criada") {
      return ["Em Andamento"];
    }

    return editingRecord.value.trip_status === "Em Andamento"
      ? ["Aguardando Analise"]
      : [];
  }

  const nextStatus = nextTripStatus(editingRecord.value.trip_status);
  return nextStatus ? [nextStatus] : [];
});
const selectedEditDriver = computed({
  get: () => props.drivers.find((driver) => driver.id === Number(editForm.driver_id)) || null,
  set: (driver) => {
    editForm.driver_id = driver?.id ?? null;
  },
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
    (props.isAdmin && record.trip_status !== "Finalizada") || (props.isDriver && record.trip_status === "Em Andamento")
      || (props.isDriver && record.trip_status === "Criada" && Number(record.driver_id) === Number(props.currentDriverId))
      ? `<button type="button" class="mr-3 font-semibold text-teal-700 hover:underline" data-goali-action="edit" data-record-id="${Number(record.id)}">Editar</button>`
      : "",
    record.trip_status === "Aguardando Analise" && (props.isAdmin || (props.isDriver && Number(record.driver_id) === Number(props.currentDriverId)))
      ? `<button type="button" class="mr-3 font-semibold text-amber-700 hover:underline" data-goali-action="cancel" data-record-id="${Number(record.id)}">Cancelar corrida</button>`
      : "",
    props.isAdmin
      ? `<button type="button" class="font-semibold text-red-700 hover:underline" data-goali-action="delete" data-record-id="${Number(record.id)}">Excluir</button>`
      : "",
  ].join(""),
})));

watch(() => props.filters, (value) => filters.defaults(value).reset(), { deep: true });
const applyFilters = () => filters.get(route("goali.index"), { preserveState: true, preserveScroll: true });
const startEdit = (record) => {
  const canDriverUpdate = record.trip_status === "Em Andamento"
    || (record.trip_status === "Criada" && Number(record.driver_id) === Number(props.currentDriverId));
  if (record.trip_status === "Finalizada" || (props.isDriver && !canDriverUpdate)) {
    return;
  }

  editingId.value = record.id;
  if (props.isDriver) {
    editForm.defaults({
      trip_status: record.trip_status === "Criada" ? "Em Andamento" : "Aguardando Analise",
      driver_id: props.currentDriverId,
    }).reset();
    return;
  }

  editForm.defaults({
    travel_date: record.travel_date,
    travel_time: String(record.travel_time).slice(0, 5),
    status: record.status,
    trip_status: "",
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
  if (props.isAdmin && !editForm.solicitation) {
    editForm.setError("solicitation", "Selecione uma empresa.");
    return;
  }
  editForm.put(route("goali.update", editingId.value), { preserveScroll: true, onSuccess: () => { editingId.value = null; } });
};
const handleEditModalClick = (event) => {
  if (event.target.closest(".select2-container, .select2-dropdown")) {
    return;
  }

  if (event.target === event.currentTarget) {
    editingId.value = null;
  }
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
  } else if (button.dataset.goaliAction === "cancel") {
    cancelTrip(record);
  }
};
const cancelTrip = async (record) => {
  const result = await Swal.fire({
    title: "Devolver corrida às pendentes?",
    text: `A corrida de ${record.passenger} ficará disponível para atribuição novamente.`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Cancelar corrida",
    cancelButtonText: "Manter corrida",
    confirmButtonColor: "#b45309",
  });

  if (result.isConfirmed) {
    router.put(route("goali.update", record.id), { cancel_trip: true }, { preserveScroll: true });
  }
};
const nextTripStatus = (status) => ({
  Criada: "Em Andamento",
  "Aguardando Analise": "Finalizada",
}[status] || "");
const tripRowClass = (row) => ({
  "goali-trip--created": row.tripStatus === "Criada",
  "goali-trip--in-progress": row.tripStatus === "Em Andamento",
  "goali-trip--awaiting": row.tripStatus === "Aguardando Analise",
  "goali-trip--finished": row.tripStatus === "Finalizada",
});
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

      <Datatable :key="tableKey" class="col-span-12 w-full min-w-0" :thead="tableHeaders" :tbody="tableData" :id="tableId" :order-by="[0, sortOrder]" :row-class="tripRowClass" />
        </div>
      </section>

      <div v-if="editingId" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 sm:items-center" data-select2-dropdown-parent @click="handleEditModalClick">
        <form class="my-6 max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-lg bg-white p-6 shadow-xl dark:bg-gray-900" @click.stop @submit.prevent="saveEdit">
          <div class="mb-5 flex items-center justify-between"><h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ props.isDriver ? "Atualizar status da corrida" : "Editar deslocamento" }}</h2><button type="button" aria-label="Fechar" class="text-2xl leading-none text-gray-500" @click="editingId = null">&times;</button></div>
          <div v-if="props.isDriver" class="grid grid-cols-1 gap-4">
            <div class="filter-label">Motorista
              <Select v-model="selectedEditDriver" :options="props.drivers" label="name" track-by="id"
                placeholder="Seu nome" :allow-empty="false" :select2="true" disabled />
              <InputError :message="editForm.errors.driver_id" />
              <span v-if="editingRecord && !editingRecord.driver_id" class="font-normal text-gray-500">
                Ao confirmar, esta corrida será atribuída a você.
              </span>
            </div>
            <div class="filter-label">Próximo status<Select :key="`driver-trip-status-${editingRecord?.id}-${editingRecord?.trip_status}`" v-model="editForm.trip_status" :options="editTripStatusOptions" placeholder="Selecione o status" :allow-empty="false" :select2="true" required /><InputError :message="editForm.errors.trip_status" /></div>
          </div>
          <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="filter-label sm:col-span-2">Andamento<Select :key="`admin-trip-status-${editingRecord?.id}-${editingRecord?.trip_status}`" v-model="editForm.trip_status" :options="editTripStatusOptions" placeholder="Manter status atual" :allow-empty="true" :select2="true" /><InputError :message="editForm.errors.trip_status" /></div>
            <label class="filter-label">Data<TextInput v-model="editForm.travel_date" type="date" class="mt-1 block w-full" required /><InputError :message="editForm.errors.travel_date" /></label>
            <label class="filter-label">Horário<TextInput v-model="editForm.travel_time" type="time" class="mt-1 block w-full" required /><InputError :message="editForm.errors.travel_time" /></label>
            <div class="filter-label">Tipo<Select v-model="editForm.status" :options="statusOptions" placeholder="Selecione o tipo" :allow-empty="false" :select2="true" required /><InputError :message="editForm.errors.status" /></div>
            <div class="filter-label">Motorista<Select v-model="selectedEditDriver" :options="props.drivers" label="name" track-by="id" placeholder="Selecione um motorista" :allow-empty="true" :select2="true" /><InputError :message="editForm.errors.driver_id" /></div>
            <div class="filter-label">Solicitação<Select v-model="editForm.solicitation" :options="props.solicitations" placeholder="Selecione uma empresa" :allow-empty="false" :select2="true" required /><span v-if="editForm.errors.solicitation" class="text-xs text-red-700">{{ editForm.errors.solicitation }}</span></div>
            <label class="filter-label">Origem<TextInput v-model="editForm.origin" type="text" class="mt-1 block w-full" maxlength="255" required /><InputError :message="editForm.errors.origin" /></label>
            <label class="filter-label">Destino<TextInput v-model="editForm.destination" type="text" class="mt-1 block w-full" required /><InputError :message="editForm.errors.destination" /></label>
            <label class="filter-label">Passageiro<TextInput v-model="editForm.passenger" type="text" class="mt-1 block w-full" required /><InputError :message="editForm.errors.passenger" /></label>
            <label class="filter-label">Responsável<TextInput v-model="editForm.responsible" type="text" class="mt-1 block w-full" :readonly="props.isDriver" required /><InputError :message="editForm.errors.responsible" /></label>
            <label class="filter-label">Valor (R$)<TextInput v-model="editForm.amount" type="number" class="mt-1 block w-full" min="0" step="0.01" inputmode="decimal" required /><InputError :message="editForm.errors.amount" /></label>
          </div>
          <p v-if="editForm.hasErrors" class="mt-3 text-sm text-red-700">Verifique os campos informados.</p>
          <div class="mt-6 flex justify-end gap-2"><button type="button" class="rounded-md border border-gray-300 px-4 py-2 text-sm" @click="editingId = null">Cancelar</button><button type="submit" class="rounded-md bg-teal-700 px-4 py-2 text-sm font-semibold text-white" :disabled="editForm.processing">{{ editForm.processing ? "Salvando..." : props.isDriver ? "Atualizar status" : "Salvar alterações" }}</button></div>
        </form>
      </div>
    </main>
  </BaseLayout>
</template>

<style scoped>
.filter-label { display: flex; flex-direction: column; gap: .4rem; color: #4b5563; font-size: .8rem; font-weight: 600; }
:deep(.goali-trip--created > td) { background-color: #f3f4f6 !important; }
:deep(.goali-trip--in-progress > td) { background-color: #eff6ff !important; }
:deep(.goali-trip--awaiting > td) { background-color: #fef3c7 !important; }
:deep(.goali-trip--finished > td) { background-color: #dcfce7 !important; }
</style>