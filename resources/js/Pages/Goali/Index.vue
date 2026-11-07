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
const editDriverOptions = computed(() => (
  props.isDriver
    ? props.drivers.filter((driver) => Number(driver.id) === Number(props.currentDriverId))
    : props.drivers
));
const selectedEditDriver = computed({
  get: () => props.drivers.find((driver) => driver.id === Number(editForm.driver_id)) || null,
  set: (driver) => {
    editForm.driver_id = driver?.id ?? null;
  },
});
const canDriverEditTrip = (record) => (
  (record.trip_status === "Em Andamento" || record.trip_status === "Criada")
  && (record.driver_id === null || Number(record.driver_id) === Number(props.currentDriverId))
);
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
  { name: "Cancelada por" },
  { name: "Ações" },
];
const tableColumns = [
  "travelDate",
  "status",
  "tripStatus",
  "solicitation",
  "route",
  "passenger",
  "driver",
  "responsible",
  "amount",
  "cancelledBy",
  "buttonActions",
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
  cancelledBy: record.cancelled_by_role
    ? `${record.cancelled_by_role}: ${record.cancelled_by_name}`
    : "—",
  buttonActions: [
    (props.isAdmin && record.trip_status !== "Finalizada")
      || (props.isDriver && canDriverEditTrip(record))
      ? `<button type="button" class="goali-action goali-action--edit" data-goali-action="edit" data-record-id="${Number(record.id)}">Editar</button>`
      : "",
    !["Finalizada", "Aguardando Analise"].includes(record.trip_status)
      ? `<button type="button" class="goali-action goali-action--cancel" data-goali-action="cancel" data-record-id="${Number(record.id)}">Cancelar</button>`
      : "",
    (props.isAdmin && record.trip_status !== "Finalizada")
      ? `<button type="button" class="goali-action goali-action--delete" data-goali-action="delete" data-record-id="${Number(record.id)}">Excluir</button>`
      : "",
  ].join(""),
})));
const mobileSearch = ref("");
const mobilePage = ref(1);
const mobilePageSize = 10;
const mobileFilteredRecords = computed(() => {
  const search = mobileSearch.value.trim().toLocaleLowerCase("pt-BR");
  if (!search) {
    return props.records;
  }

  return props.records.filter((record) => [
    record.travel_date,
    record.travel_time,
    record.status,
    record.trip_status,
    record.solicitation,
    record.origin,
    record.destination,
    record.passenger,
    record.driver?.name,
    record.responsible,
    record.cancelled_by_name,
    record.cancelled_by_role,
  ].filter(Boolean).join(" ").toLocaleLowerCase("pt-BR").includes(search));
});
const mobilePageCount = computed(() => Math.max(1, Math.ceil(mobileFilteredRecords.value.length / mobilePageSize)));
const mobilePageRecords = computed(() => {
  const start = (mobilePage.value - 1) * mobilePageSize;
  return mobileFilteredRecords.value.slice(start, start + mobilePageSize);
});
const mobilePageStart = computed(() => (
  mobileFilteredRecords.value.length ? (mobilePage.value - 1) * mobilePageSize + 1 : 0
));
const mobilePageEnd = computed(() => Math.min(mobilePage.value * mobilePageSize, mobileFilteredRecords.value.length));

watch(() => props.filters, (value) => filters.defaults(value).reset(), { deep: true });
watch([mobileSearch, () => props.records], () => { mobilePage.value = 1; });
const applyFilters = () => filters.get(route("goali.index"), { preserveState: true, preserveScroll: true });
let filterTimeout;
watch(
  () => [filters.startDate, filters.endDate, filters.status, filters.tripStatus, filters.solicitation],
  () => {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(applyFilters, 250);
  }
);
const clearFilters = () => {
  filters.startDate = "";
  filters.endDate = "";
  filters.status = "";
  filters.tripStatus = "";
  filters.solicitation = "";
};
const startEdit = (record) => {
  if (record.trip_status === "Finalizada" || (props.isDriver && !canDriverEditTrip(record))) {
    return;
  }

  editingId.value = record.id;
  if (props.isDriver) {
    editForm.defaults({
      trip_status: record.trip_status === "Criada" ? "Em Andamento" : "Aguardando Analise",
      driver_id: record.driver_id,
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
    title: "Cancelar corrida?",
    text: `A corrida de ${record.passenger} voltará para Criada, sem motorista. Seu nome e perfil ficarão registrados.`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Confirmar cancelamento",
    cancelButtonText: "Manter corrida",
    confirmButtonColor: "#b45309",
  });

  if (result.isConfirmed) {
    router.put(route("goali.update", record.id), { cancel_trip: true }, { preserveScroll: true });
  }
};
const nextTripStatus = (status) => ({
  Criada: "Em Andamento",
  "Em Andamento": "Finalizada",
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
onBeforeUnmount(() => {
  document.removeEventListener("click", handleTableAction);
  clearTimeout(filterTimeout);
});
</script>

<template>
  <BaseLayout title="Relatório Goali">
    <main class="mx-auto w-full min-w-0 px-1 pt-1 sm:px-2">
      <section class="rounded-xl bg-white p-2 shadow-lg dark:bg-gray-900 sm:p-4 lg:p-6">
        <div class="grid min-w-0 grid-cols-12 gap-3 sm:gap-4">
          <header class="col-span-12 grid grid-cols-12 gap-3 border-b border-gray-200 pb-4 dark:border-gray-700 sm:gap-4 sm:pb-5">
            <div class="col-span-12 text-center">
              <p class="text-xs font-bold uppercase text-teal-700">{{ $page.props.auth.user.current_team.name }}</p>
              <h1 class="mt-1 text-xl font-bold text-gray-900 dark:text-white sm:text-2xl">Relatório de deslocamentos</h1>
              <div class="mx-auto mt-3 h-1 w-14 rounded-full bg-blue-600" aria-hidden="true"></div>
            </div>
            <div class="col-span-12 flex flex-col gap-2 sm:flex-row sm:justify-end">
          <a :href="route('goali.form')" class="inline-flex min-h-11 items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-100">Novo registro</a>
          <a :href="reportUrl" class="inline-flex min-h-11 items-center justify-center rounded-md bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800">Exportar Excel</a>
        </div>
      </header>

      <form class="col-span-12 grid min-w-0 grid-cols-1 gap-3 border-b border-gray-200 pb-4 dark:border-gray-700 sm:grid-cols-2 xl:grid-cols-5" @submit.prevent="applyFilters">
        <label class="filter-label">Data inicial<TextInput v-model="filters.startDate" type="date" class="mt-1 block w-full" /></label>
        <label class="filter-label">Data final<TextInput v-model="filters.endDate" type="date" class="mt-1 block w-full" /></label>
        <label class="filter-label">Tipo<Select v-model="filters.status" :options="statusOptions" placeholder="Todos" :select2="true" /></label>
        <label class="filter-label">Status da corrida<Select v-model="filters.tripStatus" :options="props.tripStatuses" placeholder="Todos" :select2="true" /></label>
        <label class="filter-label">Solicitação<Select v-model="filters.solicitation" :options="props.solicitations" placeholder="Todas" :select2="true" /></label>
        <button class="min-h-11 self-stretch rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-700 dark:border-gray-600 dark:text-gray-100 dark:hover:bg-gray-800 sm:self-end" type="button" @click="clearFilters">Limpar filtros</button>
      </form>

      <section class="col-span-12 grid grid-cols-1 gap-2 border-b border-gray-200 py-3 dark:border-gray-700 sm:grid-cols-3 sm:gap-4 sm:py-4">
        <div class="rounded-md border-l-4 border-teal-600 bg-gray-50 px-3 py-2 dark:bg-gray-800 sm:px-4"><p class="text-xs text-gray-500 sm:text-sm">Registros no período</p><p class="mt-1 text-xl font-semibold text-gray-900 dark:text-white sm:text-2xl">{{ props.summary.count }}</p></div>
        <div class="rounded-md border-l-4 border-amber-500 bg-gray-50 px-3 py-2 dark:bg-gray-800 sm:px-4"><p class="text-xs text-gray-500 sm:text-sm">Valor total</p><p class="mt-1 text-xl font-semibold text-gray-900 dark:text-white sm:text-2xl">{{ money(props.summary.amount) }}</p></div>
        <div class="rounded-md border-l-4 border-green-600 bg-gray-50 px-3 py-2 dark:bg-gray-800 sm:px-4"><p class="text-xs text-gray-500 sm:text-sm">Valor a receber</p><p class="mt-1 text-xl font-semibold text-gray-900 dark:text-white sm:text-2xl">{{ money(props.summary.receivable) }}</p></div>
      </section>

      <section class="col-span-12 lg:hidden" aria-label="Deslocamentos">
        <div class="mb-3">
          <label for="goali-mobile-search" class="sr-only">Buscar deslocamentos</label>
          <input
            id="goali-mobile-search"
            v-model="mobileSearch"
            type="search"
            placeholder="Buscar nos resultados..."
            class="min-h-11 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-teal-700 focus:ring-teal-700 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
          />
        </div>

        <div v-if="mobilePageRecords.length" class="space-y-3">
          <article
            v-for="record in mobilePageRecords"
            :key="record.id"
            class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
          >
            <div class="flex flex-wrap items-start justify-between gap-2 border-b border-gray-100 px-4 py-3 dark:border-gray-700">
              <div>
                <p class="font-semibold text-gray-900 dark:text-white">{{ date(record.travel_date) }} · {{ time(record.travel_time) }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ record.solicitation }}</p>
              </div>
              <div class="flex flex-wrap gap-1.5">
                <span class="rounded-full bg-teal-50 px-2.5 py-1 text-xs font-semibold text-teal-800 dark:bg-teal-900/40 dark:text-teal-200">{{ record.status }}</span>
                <span
                  class="rounded-full px-2.5 py-1 text-xs font-semibold"
                  :class="{
                    'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200': record.trip_status === 'Criada',
                    'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200': record.trip_status === 'Em Andamento',
                    'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200': record.trip_status === 'Aguardando Analise',
                    'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200': record.trip_status === 'Finalizada',
                  }"
                >{{ record.trip_status }}</span>
              </div>
            </div>

            <div class="space-y-3 px-4 py-3">
              <div>
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Passageiro</p>
                <p class="mt-0.5 font-semibold text-gray-900 dark:text-white">{{ record.passenger }}</p>
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div class="col-span-2">
                  <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Origem / destino</p>
                  <p class="mt-0.5 break-words text-sm text-gray-800 dark:text-gray-200">{{ record.origin }} → {{ record.destination }}</p>
                </div>
                <div>
                  <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Motorista</p>
                  <p class="mt-0.5 text-sm text-gray-800 dark:text-gray-200">{{ record.driver?.name || "—" }}</p>
                </div>
                <div>
                  <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Valor</p>
                  <p class="mt-0.5 text-sm font-semibold text-gray-900 dark:text-white">{{ money(record.amount) }}</p>
                </div>
                <div class="col-span-2">
                  <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Responsável</p>
                  <p class="mt-0.5 text-sm text-gray-800 dark:text-gray-200">{{ record.responsible }}</p>
                </div>
                <div v-if="record.cancelled_by_role" class="col-span-2">
                  <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Cancelada por</p>
                  <p class="mt-0.5 text-sm text-gray-800 dark:text-gray-200">{{ record.cancelled_by_role }}: {{ record.cancelled_by_name }}</p>
                </div>
              </div>
            </div>

            <div
              v-if="(props.isAdmin && record.trip_status !== 'Finalizada') || (props.isDriver && canDriverEditTrip(record)) || !['Finalizada', 'Aguardando Analise'].includes(record.trip_status)"
              class="flex flex-wrap gap-2 border-t border-gray-100 px-4 py-3 dark:border-gray-700"
            >
              <button
                v-if="(props.isAdmin && record.trip_status !== 'Finalizada') || (props.isDriver && canDriverEditTrip(record))"
                type="button"
                class="min-h-11 flex-1 rounded-md bg-teal-700 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-700"
                @click="startEdit(record)"
              >{{ props.isDriver ? "Atualizar status" : "Editar" }}</button>
              <button
                v-if="!['Finalizada', 'Aguardando Analise'].includes(record.trip_status)"
                type="button"
                class="min-h-11 flex-1 rounded-md bg-amber-600 px-3 py-2 text-sm font-semibold text-white hover:bg-amber-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-700"
                @click="cancelTrip(record)"
              >Cancelar</button>
              <button
                v-if="props.isAdmin && record.trip_status !== 'Finalizada'"
                type="button"
                class="min-h-11 flex-1 rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700"
                @click="deleteRecord(record)"
              >Excluir</button>
            </div>
          </article>
        </div>

        <p v-else class="rounded-lg border border-dashed border-gray-300 px-4 py-8 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
          {{ mobileSearch ? "Nenhum deslocamento encontrado para essa busca." : "Nenhum deslocamento encontrado para os filtros selecionados." }}
        </p>

        <div v-if="mobileFilteredRecords.length" class="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm text-gray-600 dark:text-gray-300">
          <span>Mostrando {{ mobilePageStart }}–{{ mobilePageEnd }} de {{ mobileFilteredRecords.length }}</span>
          <div v-if="mobilePageCount > 1" class="flex gap-2">
            <button
              type="button"
              class="min-h-11 rounded-md border border-gray-300 px-3 font-medium disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600"
              :disabled="mobilePage === 1"
              @click="mobilePage--"
            >Anterior</button>
            <button
              type="button"
              class="min-h-11 rounded-md border border-gray-300 px-3 font-medium disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600"
              :disabled="mobilePage === mobilePageCount"
              @click="mobilePage++"
            >Próxima</button>
          </div>
        </div>
      </section>

      <Datatable class="col-span-12 hidden w-full min-w-0 lg:block" :thead="tableHeaders" :tbody="tableData" :row-columns="tableColumns" :id="tableId" :order-by="[0, sortOrder]" :row-class="tripRowClass" managed-rows />
        </div>
      </section>

      <div v-if="editingId" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-2 sm:items-center sm:p-4" @click.self="editingId = null">
        <form class="my-2 max-h-[calc(100dvh-1rem)] w-full max-w-2xl overflow-y-auto rounded-lg bg-white p-4 shadow-xl dark:bg-gray-900 sm:my-6 sm:max-h-[90vh] sm:p-6" data-select2-dropdown-parent @click.stop @submit.prevent="saveEdit">
          <div class="mb-5 flex items-center justify-between"><h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ props.isDriver ? "Atualizar status da corrida" : "Editar deslocamento" }}</h2><button type="button" aria-label="Fechar" class="text-2xl leading-none text-gray-500" @click="editingId = null">&times;</button></div>
          <div v-if="props.isDriver" class="grid grid-cols-1 gap-4">
            <div v-if="!editingRecord?.driver_id" class="filter-label">Motorista
              <Select v-model="selectedEditDriver" :options="editDriverOptions" label="name" track-by="id"
                placeholder="Selecione seu nome para aceitar" :allow-empty="false" required />
              <InputError :message="editForm.errors.driver_id" />
              <span class="font-normal text-gray-500">
                Selecione seu nome para atribuir esta corrida a você. Ela passará para Em Andamento ao salvar.
              </span>
            </div>
            <div v-else class="filter-label">
              Motorista
              <p class="mt-1 rounded-md border border-gray-300 bg-gray-50 px-3 py-2 font-normal text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                {{ selectedEditDriver?.name || "Atribuída a você" }}
              </p>
            </div>
            <label class="filter-label">Próximo status
              <select
                v-model="editForm.trip_status"
                class="mt-1 min-h-11 w-full rounded-md border-gray-300 shadow-sm focus:border-teal-700 focus:ring-teal-700 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                required
              >
                <option v-for="status in editTripStatusOptions" :key="status" :value="status">
                  {{ status }}
                </option>
              </select>
              <InputError :message="editForm.errors.trip_status" />
            </label>
          </div>
          <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="filter-label sm:col-span-2">Andamento<Select :key="`admin-trip-status-${editingRecord?.id}-${editingRecord?.trip_status}`" v-model="editForm.trip_status" :options="editTripStatusOptions" placeholder="Manter status atual" :allow-empty="true" /><InputError :message="editForm.errors.trip_status" /></div>
            <label class="filter-label">Data<TextInput v-model="editForm.travel_date" type="date" class="mt-1 block w-full" required /><InputError :message="editForm.errors.travel_date" /></label>
            <label class="filter-label">Horário<TextInput v-model="editForm.travel_time" type="time" class="mt-1 block w-full" required /><InputError :message="editForm.errors.travel_time" /></label>
            <div class="filter-label">Tipo<Select v-model="editForm.status" :options="statusOptions" placeholder="Selecione o tipo" :allow-empty="false" required /><InputError :message="editForm.errors.status" /></div>
            <div class="filter-label">Motorista<Select v-model="selectedEditDriver" :options="props.drivers" label="name" track-by="id" placeholder="Selecione um motorista" :allow-empty="true" /><InputError :message="editForm.errors.driver_id" /></div>
            <div class="filter-label">Solicitação<Select v-model="editForm.solicitation" :options="props.solicitations" placeholder="Selecione uma empresa" :allow-empty="false" required /><span v-if="editForm.errors.solicitation" class="text-xs text-red-700">{{ editForm.errors.solicitation }}</span></div>
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
:deep(#goali-trips-datatable .datatable-actions) {
  display: flex;
  flex-wrap: nowrap;
  gap: .375rem;
  width: max-content;
  min-width: max-content;
}
:deep(#goali-trips-datatable .goali-action) {
  display: inline-flex;
  min-height: 2.75rem;
  min-width: 4.75rem;
  flex: 0 0 auto;
  white-space: nowrap;
  align-items: center;
  justify-content: center;
  border: 1px solid transparent;
  border-radius: .375rem;
  padding: .5rem .625rem;
  font-size: .8125rem;
  font-weight: 600;
  line-height: 1.25rem;
  color: white;
  box-shadow: 0 1px 2px rgb(0 0 0 / 12%);
  touch-action: manipulation;
  transition: background-color .15s ease, box-shadow .15s ease, transform .15s ease;
}
:deep(#goali-trips-datatable .goali-action--edit) { background-color: #0f766e; }
:deep(#goali-trips-datatable .goali-action--edit:hover) { background-color: #115e59; }
:deep(#goali-trips-datatable .goali-action--cancel) { background-color: #d97706; }
:deep(#goali-trips-datatable .goali-action--cancel:hover) { background-color: #b45309; }
:deep(#goali-trips-datatable .goali-action--delete) { background-color: #dc2626; }
:deep(#goali-trips-datatable .goali-action--delete:hover) { background-color: #b91c1c; }
:deep(#goali-trips-datatable .goali-action:focus-visible) {
  outline: 3px solid #93c5fd;
  outline-offset: 2px;
}
:deep(#goali-trips-datatable .goali-action:active) { transform: translateY(1px); }
:deep(.goali-trip--created > td) { background-color: #f3f4f6 !important; }
:deep(.goali-trip--in-progress > td) { background-color: #eff6ff !important; }
:deep(.goali-trip--awaiting > td) { background-color: #fef3c7 !important; }
:deep(.goali-trip--finished > td) { background-color: #dcfce7 !important; }
</style>