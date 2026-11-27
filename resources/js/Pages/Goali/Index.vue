<script setup>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import Select from "@/Components/Select.vue";
import Datatable from "@/Components/Datatable.vue";
import TextInput from "@/Components/TextInput.vue";
import TextArea from "@/Components/TextArea.vue";
import InputError from "@/Components/InputError.vue";
import { router, useForm } from "@inertiajs/vue3";
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import Swal from "sweetalert2";

const props = defineProps({
  records: { type: Array, required: true },
  filters: { type: Object, required: true },
  isAdmin: { type: Boolean, default: false },
  canViewAllTrips: { type: Boolean, default: false },
  solicitations: { type: Array, default: () => [] },
  passengers: { type: Array, default: () => [] },
  drivers: { type: Array, default: () => [] },
  filterDrivers: { type: Array, default: () => [] },
  responsibles: { type: Array, default: () => [] },
  tripStatuses: { type: Array, default: () => [] },
  tripRoles: { type: Array, default: () => [] },
  summary: { type: Object, required: true },
  isDriver: { type: Boolean, default: false },
  isAdministrative: { type: Boolean, default: false },
  currentDriverId: { type: Number, default: null },
  canCreateTrips: { type: Boolean, default: false },
  canCancelTrips: { type: Boolean, default: false },
});

const normalizedFilters = (value) => ({
  driverId: value.driverId || null,
  status: Array.isArray(value.status)
    ? value.status
    : value.status
      ? [value.status]
      : [],
  tripStatus: Array.isArray(value.tripStatus)
    ? value.tripStatus
    : value.tripStatus
      ? [value.tripStatus]
      : [],
  solicitation: Array.isArray(value.solicitation)
    ? value.solicitation
    : value.solicitation
      ? [value.solicitation]
      : [],
  passenger: value.passenger || "",
  responsible: value.responsible || "",
  driverRole: value.driverRole || "",
  responsibleRole: value.responsibleRole || "",
  sortOrder: value.sortOrder || "desc",
});
const getFilterKey = (value) => JSON.stringify([
  value.startDate || "",
  value.endDate || "",
  value.driverId || "",
  [...(Array.isArray(value.status) ? value.status : [])].sort(),
  [...(Array.isArray(value.tripStatus) ? value.tripStatus : [])].sort(),
  [...(Array.isArray(value.solicitation) ? value.solicitation : [])].sort(),
  value.passenger || "",
  value.responsible || "",
  value.driverRole || "",
  value.responsibleRole || "",
]);
const dateFilters = useForm({
  startDate: props.filters.startDate || "",
  endDate: props.filters.endDate || "",
});
const appliedDateFilters = ref({
  startDate: props.filters.startDate || "",
  endDate: props.filters.endDate || "",
});
const filters = useForm(normalizedFilters(props.filters));
const combinedFilterData = () => ({
  ...appliedDateFilters.value,
  ...filters.data(),
});
let lastAppliedFilterKey = getFilterKey(combinedFilterData());
const statusOptions = ["ENTRADA", "SAÍDA"];
const editingId = ref(null);
const editForm = useForm({});
const reportUrl = computed(() => route("goali.export", combinedFilterData()));
const sortOrder = computed(() => filters.sortOrder || "desc");
const editingRecord = computed(() => props.records.find((record) => String(record.id) === String(editingId.value)) || null);
const nextTripStatus = (status) => ({
  Criada: "Em Andamento",
  "Em Andamento": "Aguardando Aprovação",
  "Aguardando Aprovação": "Pago",
}[status] || "");
const selectedFilterDriver = computed({
  get: () => props.filterDrivers.find((driver) => driver.id === Number(filters.driverId)) || null,
  set: (driver) => {
    filters.driverId = driver?.id ?? null;
  },
});
const editTripStatusOptions = computed(() => {
  if (!editingRecord.value || (!props.isAdmin && !props.isAdministrative)) {
    return [];
  }

  const allowedStatuses = props.isAdministrative && !props.isAdmin
    ? {
      Criada: "Em Andamento",
      "Em Andamento": "Aguardando Aprovação",
    }
    : {
      Criada: "Em Andamento",
      "Em Andamento": "Pago",
      "Aguardando Aprovação": "Pago",
    };
  const nextStatus = allowedStatuses[editingRecord.value.trip_status];
  return nextStatus ? [nextStatus] : [];
});
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
const canEditTrip = (record) => (
  record.trip_status !== "Pago"
  && (props.isAdmin
    || (props.isAdministrative && ["Criada", "Em Andamento"].includes(record.trip_status)))
);
const canAdvanceTripStatus = (record) => (
  !props.isAdmin && !props.isAdministrative && props.isDriver && canDriverEditTrip(record)
);
const tableId = "goali-trips-datatable";
const tableHeaders = computed(() => [
  { name: "Data" },
  { name: "Tipo" },
  { name: "Status" },
  { name: "Solicitação" },
  { name: "ID" },
  { name: "Código da Viagem" },
  { name: "Origem / Destino" },
  { name: "Passageiro" },
  { name: "Motorista" },
  ...(props.isAdmin ? [{ name: "Responsável" }] : []),
  ...(props.isAdmin ? [{ name: "Lucro da empresa" }] : []),
  { name: "Valor" },
  ...(props.isAdmin ? [{ name: "Cancelada por" }] : []),
  { name: "Ações" },
]);
const tableColumns = computed(() => [
  "travelDate",
  "status",
  "tripStatus",
  "solicitation",
  "tripId",
  "tripCode",
  "route",
  "passenger",
  "driver",
  ...(props.isAdmin ? ["responsible"] : []),
  ...(props.isAdmin ? ["companyProfit"] : []),
  "amount",
  ...(props.isAdmin ? ["cancelledBy"] : []),
  "buttonActions",
]);
const tableData = computed(() => props.records.map((record) => ({
  travelDate: `${date(record.travel_date)} - ${time(record.travel_time)}`,
  status: record.status,
  tripStatus: record.trip_status,
  solicitation: record.solicitation,
  tripId: `ID${record.id}`,
  tripCode: record.trip_code || "—",
  route: `${record.origin} → ${record.destination}`,
  passenger: record.passenger,
  driverId: record.driver_id,
  driver: {
    name: record.driver?.name || "—",
    photoUrl: record.driver?.profile_photo_url || "",
  },
  responsible: record.responsible,
  ...(props.isAdmin ? { companyProfit: Number(record.company_profit || 0) } : {}),
  amount: Number(record.amount || 0),
  cancelledBy: record.cancelled_by_role
    ? `${record.cancelled_by_role}: ${record.cancelled_by_name}`
    : "—",
  buttonActions: [
    canEditTrip(record)
      ? `<button type="button" class="goali-action goali-action--edit" data-goali-action="edit" data-record-id="${Number(record.id)}">Editar</button>`
      : (canAdvanceTripStatus(record)
      ? `<button type="button" class="goali-action goali-action--status" data-goali-action="status" data-record-id="${Number(record.id)}">${nextTripStatus(record.trip_status) || "Atualizar status"}</button>`
        : ""),
    props.canCancelTrips
      && record.trip_status !== "Criada"
      && record.trip_status !== "Cancelada"
      && record.driver_id !== null
      && !["Pago", "Aguardando Aprovação"].includes(record.trip_status)
      ? `<button type="button" class="goali-action goali-action--cancel" data-goali-action="cancel" data-record-id="${Number(record.id)}">Devolver à fila</button>`
      : "",
    props.isAdmin && record.trip_status !== "Cancelada"
      ? `<button type="button" class="goali-action goali-action--cancelled" data-goali-action="mark-cancelled" data-record-id="${Number(record.id)}">Marcar Cancelada</button>`
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
    record.trip_code,
    `ID${record.id}`,
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

watch(() => props.filters, (value) => {
  const normalized = normalizedFilters(value);
  const dateValues = {
    startDate: value.startDate || "",
    endDate: value.endDate || "",
  };
  dateFilters.defaults(dateValues).reset();
  appliedDateFilters.value = dateValues;
  filters.defaults(normalized).reset();
  lastAppliedFilterKey = getFilterKey({ ...dateValues, ...normalized });
}, { deep: true });
watch([mobileSearch, () => props.records], () => { mobilePage.value = 1; });
const applyFilters = (dateRange = appliedDateFilters.value) => {
  const filterData = { ...dateRange, ...filters.data() };
  const filterKey = getFilterKey(filterData);
  if (filterKey === lastAppliedFilterKey) {
    return;
  }

  lastAppliedFilterKey = filterKey;
  dateFilters.clearErrors();
  filters.clearErrors();
  router.get(route("goali.index"), filterData, {
    preserveState: true,
    preserveScroll: true,
    onError: (errors) => {
      dateFilters.setError(errors);
      filters.setError(errors);
      lastAppliedFilterKey = getFilterKey(props.filters);
    },
  });
};
const applyDateFilters = () => {
  appliedDateFilters.value = { ...dateFilters.data() };
  filters.driverId = null;
  filters.solicitation = [];
  filters.passenger = "";
  filters.responsible = "";
  applyFilters(appliedDateFilters.value);
};
const applyOtherFilters = () => applyFilters();
undefinedng(record.travel_time).slice(0, 5),
    status: record.status,
    trip_status: "",
    solicitation: record.solicitation,
    trip_code: record.trip_code || "",
    driver_id: record.driver_id,
    origin: record.origin,
    destination: record.destination,
    passenger: record.passenger,
    observation: record.observation || "",
    amount: Number(record.amount).toFixed(2),
    company_profit: record.company_profit === null || record.company_profit === undefined
      ? ""
      : Number(record.company_profit).toFixed(2),
    responsible: record.responsible,
  }).reset();
};
const advanceTripStatus = async (record) => {
  if (!canAdvanceTripStatus(record)) {
    return;
  }

  const nextStatus = nextTripStatus(record.trip_status);
  if (!nextStatus || nextStatus === "Pago") {
    return;
  }

  const result = await Swal.fire({
    title: "Atualizar status da corrida?",
    text: `A corrida será alterada para ${nextStatus}.`,
    icon: "question",
    showCancelButton: true,
    confirmButtonText: `Confirmar ${nextStatus}`,
    cancelButtonText: "Cancelar",
    confirmButtonColor: "#0891b2",
  });

  if (result.isConfirmed) {
    router.put(route("goali.update", record.id), { trip_status: nextStatus }, { preserveScroll: true });
  }
};
const saveEdit = () => {
  if (props.isAdministrative && !props.isAdmin) {
    if (!editForm.trip_status) {
      editForm.setError("trip_status", "Selecione o próximo status.");
      return;
    }

    router.put(route("goali.update", editingId.value), { trip_status: editForm.trip_status }, {
      preserveScroll: true,
      onSuccess: () => { editingId.value = null; },
      onError: (errors) => editForm.setError(errors),
    });
    return;
  }

  if (props.isAdmin && !editForm.solicitation) {
    editForm.setError("solicitation", "Selecione uma empresa.");
    return;
  }
  editForm.put(route("goali.update", editingId.value), { preserveScroll: true, onSuccess: () => { editingId.value = null; } });
};
const markTripCancelled = async (record) => {
  if (!props.isAdmin || record.trip_status === "Cancelada") {
    return;
  }

  const result = await Swal.fire({
    title: "Marcar corrida como cancelada?",
    text: `A corrida de ${record.passenger} será mantida no histórico com status Cancelada.`,
    icon: "question",
    showCancelButton: true,
    confirmButtonText: "Marcar Cancelada",
    cancelButtonText: "Cancelar",
    confirmButtonColor: "#b42318",
  });

  if (result.isConfirmed) {
    router.put(route("goali.update", record.id), { mark_cancelled: true }, { preserveScroll: true });
  }
};
const handleTableAction = (event) => {
  const target = event.target instanceof Element ? event.target : null;
  const button = target?.closest(`#${tableId} button[data-goali-action]`);
  if (!button) {
    return;
  }

  const record = props.records.find((item) => String(item.id) === button.dataset.recordId);
  if (!record) {
    return;
  }

  if (button.dataset.goaliAction === "edit") {
    startEdit(record);
  } else if (button.dataset.goaliAction === "status") {
    advanceTripStatus(record);
  } else if (button.dataset.goaliAction === "mark-cancelled") {
    markTripCancelled(record);
  } else if (button.dataset.goaliAction === "cancel") {
    returnTripToQueue(record);
  }
};
const returnTripToQueue = async (record) => {
  if (record.trip_status === "Criada" || record.trip_status === "Cancelada" || record.driver_id === null) {
    return;
  }

  const result = await Swal.fire({
    title: "Devolver corrida à fila?",
    text: `A corrida de ${record.passenger} voltará para Criada, sem motorista. Seu nome e perfil ficarão registrados.`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Devolver à fila",
    cancelButtonText: "Manter corrida",
    confirmButtonColor: "#b45309",
  });

  if (result.isConfirmed) {
    router.put(route("goali.update", record.id), { cancel_trip: true }, { preserveScroll: true });
  }
};
const tripRowClass = (row) => ({
  "goali-trip--cancelled": row.tripStatus === "Cancelada",
  "goali-trip--created": row.tripStatus !== "Cancelada" && row.driverId !== null && row.tripStatus === "Criada",
  "goali-trip--unassigned": row.tripStatus !== "Cancelada" && row.driverId === null,
  "goali-trip--in-progress": row.tripStatus !== "Cancelada" && row.driverId !== null && row.tripStatus === "Em Andamento",
  "goali-trip--awaiting": row.tripStatus !== "Cancelada" && row.driverId !== null && row.tripStatus === "Aguardando Aprovação",
  "goali-trip--finished": row.tripStatus !== "Cancelada" && row.driverId !== null && row.tripStatus === "Pago",
});
const money = (value) => Number(value || 0).toLocaleString("pt-BR", { style: "currency", currency: "BRL" });
const escapeHtml = (value) => String(value).replace(/[&<>"']/g, (character) => ({
  "&": "&amp;",
  "<": "&lt;",
  ">": "&gt;",
  '"': "&quot;",
  "'": "&#039;",
}[character]));
const tableColumnRenderers = {
  driver: (value, type) => {
    const name = escapeHtml(value?.name || "—");
    const photoUrl = escapeHtml(value?.photoUrl || "");

    if (type !== "display") {
      return name;
    }

    const photo = photoUrl
      ? `<img src="${photoUrl}" alt="" class="h-8 w-8 rounded-full object-cover" loading="lazy">`
      : "";

    return `<span class="inline-flex items-center gap-2 whitespace-nowrap">${photo}<span>${name}</span></span>`;
  },
  amount: (value, type) => (type === "display" ? money(value) : Number(value || 0)),
  companyProfit: (value, type) => (type === "display" ? money(value) : Number(value || 0)),
};
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
});
</script>

<template>
  <BaseLayout title="RELATORIO DE CORRIDAS">
    <main class="col-span-12 w-full px-3 py-5 sm:px-6 lg:px-8">
      <section class="overflow-hidden rounded-2xl border border-cyan-100 bg-white p-2 shadow-lg dark:border-cyan-900/50 dark:bg-gray-900 sm:p-4 lg:p-6">
        <div class="grid min-w-0 grid-cols-12 gap-3 sm:gap-4">
          <header class="col-span-12 grid grid-cols-12 gap-3 border-b border-cyan-100 bg-cyan-50/70 px-3 pb-4 pt-3 dark:border-cyan-900/50 dark:bg-cyan-950/30 sm:gap-4 sm:px-5 sm:pb-5 sm:pt-5">
            <div class="col-span-12 text-center">
              <p class="text-xs font-bold uppercase text-cyan-700">{{ $page.props.auth.user.current_team.name }}</p>
              <h1 class="mt-1 text-xl font-bold text-gray-900 dark:text-white sm:text-2xl">Relatório de corridas</h1>
              <div class="mx-auto mt-3 h-1 w-14 rounded-full bg-cyan-600" aria-hidden="true"></div>
            </div>
            <div class="col-span-12 flex flex-col gap-2 sm:flex-row sm:justify-end">
          <a v-if="props.canCreateTrips" :href="route('goali.form')" class="inline-flex min-h-11 items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-100 dark:hover:bg-gray-800">Criar Corrida</a>
          <a :href="reportUrl" class="inline-flex min-h-11 items-center justify-center rounded-md bg-cyan-700 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-800">Exportar Excel</a>
        </div>
      </header>

      <form class="col-span-12 grid min-w-0 grid-cols-1 gap-3 border-b border-gray-200 px-3 py-4 dark:border-gray-700 sm:grid-cols-2 sm:px-5 xl:grid-cols-2" @submit.prevent="applyDateFilters">
        <label class="filter-label">Data inicial<TextInput v-model="dateFilters.startDate" type="date" class="mt-1 block w-full" @blur="applyDateFilters" /></label>
        <label class="filter-label">Data final<TextInput v-model="dateFilters.endDate" type="date" class="mt-1 block w-full" @blur="applyDateFilters" /><InputError :message="dateFilters.errors.endDate || dateFilters.errors.startDate" /></label>
      </form>

      <form class="col-span-12 grid min-w-0 grid-cols-1 gap-3 border-b border-gray-200 px-3 pb-4 pt-4 dark:border-gray-700 sm:grid-cols-2 sm:px-5 xl:grid-cols-7" @submit.prevent="applyOtherFilters">
        <label class="filter-label">Tipo<Select v-model="filters.status" :options="statusOptions" placeholder="Todos" multiple @select2-close="applyOtherFilters" /></label>
        <label class="filter-label">Status da corrida<Select v-model="filters.tripStatus" :options="props.tripStatuses" placeholder="Todos" multiple @select2-close="applyOtherFilters" /></label>
        <label class="filter-label">Solicitação<Select v-model="filters.solicitation" :options="props.solicitations" placeholder="Todas" multiple @select2-close="applyOtherFilters" /></label>
        <div v-if="props.passengers.length" class="filter-label"><span>Passageiro</span><Select v-model="filters.passenger" :options="props.passengers" aria-label="Passageiro" placeholder="Todos" @select2-close="applyOtherFilters" /></div>
        <div v-if="props.canViewAllTrips" class="filter-label"><span>Motorista</span><Select v-model="selectedFilterDriver" :options="props.filterDrivers" aria-label="Motorista" label="name" track-by="id" placeholder="Todos" @update:model-value="applyOtherFilters" /></div>
        <div v-if="props.canViewAllTrips" class="filter-label"><span>Responsável</span><Select v-model="filters.responsible" :options="props.responsibles" aria-label="Responsável" placeholder="Todos" @select2-close="applyOtherFilters" /></div>
        <div v-if="props.isAdmin && props.tripRoles.length" class="filter-label"><span>Função do motorista</span><Select v-model="filters.driverRole" :options="props.tripRoles" aria-label="Função do motorista" placeholder="Todas" @select2-close="applyOtherFilters" /></div>
        <div v-if="props.isAdmin && props.tripRoles.length" class="filter-label"><span>Função do responsável</span><Select v-model="filters.responsibleRole" :options="props.tripRoles" aria-label="Função do responsável" placeholder="Todas" @select2-close="applyOtherFilters" /></div>
        <button class="min-h-11 self-stretch rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-700 dark:border-gray-600 dark:text-gray-100 dark:hover:bg-gray-800 sm:self-end" type="button" @click="clearFilters">Limpar filtros</button>
      </form>

      <section class="col-span-12 grid grid-cols-1 gap-2 border-b border-gray-200 py-3 dark:border-gray-700 sm:gap-4 sm:py-4" :class="props.isAdmin ? 'sm:grid-cols-2 lg:grid-cols-4' : 'sm:grid-cols-3'">
        <div class="rounded-md border-l-4 border-teal-600 bg-gray-50 px-3 py-2 dark:bg-gray-800 sm:px-4"><p class="text-xs text-gray-500 sm:text-sm">Registros no período</p><p class="mt-1 text-xl font-semibold text-gray-900 dark:text-white sm:text-2xl">{{ props.summary.count }}</p></div>
        <div class="rounded-md border-l-4 border-amber-500 bg-gray-50 px-3 py-2 dark:bg-gray-800 sm:px-4"><p class="text-xs text-gray-500 sm:text-sm">Valor total</p><p class="mt-1 text-xl font-semibold text-gray-900 dark:text-white sm:text-2xl">{{ money(props.summary.amount) }}</p></div>
        <div class="rounded-md border-l-4 border-green-600 bg-gray-50 px-3 py-2 dark:bg-gray-800 sm:px-4"><p class="text-xs text-gray-500 sm:text-sm">Valor pago</p><p class="mt-1 text-xl font-semibold text-gray-900 dark:text-white sm:text-2xl">{{ money(props.summary.receivable) }}</p></div>
        <div v-if="props.isAdmin" class="rounded-md border-l-4 border-emerald-600 bg-gray-50 px-3 py-2 dark:bg-gray-800 sm:px-4"><p class="text-xs text-gray-500 sm:text-sm">Lucro total da empresa</p><p class="mt-1 text-xl font-semibold text-gray-900 dark:text-white sm:text-2xl">{{ money(props.summary.companyProfit) }}</p></div>
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
            class="overflow-hidden rounded-lg border-l-8 shadow-md"
            :class="{
              'border-red-700 border-y border-r bg-red-50 dark:border-red-400 dark:bg-red-950/50': record.trip_status === 'Cancelada',
              'border-orange-600 border-y border-r bg-orange-50 dark:border-orange-400 dark:bg-orange-950/50': !record.driver_id && record.trip_status !== 'Cancelada',
              'border-gray-500 border-y border-r bg-gray-50 dark:border-gray-400 dark:bg-gray-800': record.driver_id && record.trip_status === 'Criada',
              'border-blue-700 border-y border-r bg-blue-50 dark:border-blue-400 dark:bg-blue-950/50': record.driver_id && record.trip_status === 'Em Andamento',
              'border-amber-600 border-y border-r bg-amber-50 dark:border-amber-400 dark:bg-amber-950/50': record.driver_id && record.trip_status === 'Aguardando Aprovação',
              'border-green-700 border-y border-r bg-green-50 dark:border-green-400 dark:bg-green-950/50': record.driver_id && record.trip_status === 'Pago',
            }"
          >
            <div
              class="flex flex-wrap items-start justify-between gap-2 border-b px-4 py-3"
              :class="{
                'border-red-200 bg-red-100 dark:border-red-800 dark:bg-red-900/60': record.trip_status === 'Cancelada',
                'border-orange-200 bg-orange-100 dark:border-orange-800 dark:bg-orange-900/60': !record.driver_id && record.trip_status !== 'Cancelada',
                'border-gray-200 bg-gray-100 dark:border-gray-700 dark:bg-gray-800': record.driver_id && record.trip_status === 'Criada',
                'border-blue-200 bg-blue-100 dark:border-blue-800 dark:bg-blue-900/60': record.driver_id && record.trip_status === 'Em Andamento',
                'border-amber-200 bg-amber-100 dark:border-amber-800 dark:bg-amber-900/60': record.driver_id && record.trip_status === 'Aguardando Aprovação',
                'border-green-200 bg-green-100 dark:border-green-800 dark:bg-green-900/60': record.driver_id && record.trip_status === 'Pago',
              }"
            >
              <div>
                <p class="font-semibold text-gray-900 dark:text-white">{{ date(record.travel_date) }} · {{ time(record.travel_time) }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ record.solicitation }}</p>
              </div>
              <div class="flex flex-wrap gap-1.5">
                <span class="rounded-full bg-teal-50 px-2.5 py-1 text-xs font-semibold text-teal-800 dark:bg-teal-900/40 dark:text-teal-200">{{ record.status }}</span>
                <span
                  class="rounded-full px-2.5 py-1 text-xs font-semibold"
                  :class="{
                    'bg-red-700 text-white dark:bg-red-600': record.trip_status === 'Cancelada',
                    'bg-orange-700 text-white dark:bg-orange-600': !record.driver_id && record.trip_status !== 'Cancelada',
                    'bg-gray-700 text-white dark:bg-gray-600': record.driver_id && record.trip_status === 'Criada',
                    'bg-blue-700 text-white dark:bg-blue-600': record.driver_id && record.trip_status === 'Em Andamento',
                    'bg-amber-700 text-white dark:bg-amber-600': record.driver_id && record.trip_status === 'Aguardando Aprovação',
                    'bg-green-700 text-white dark:bg-green-600': record.driver_id && record.trip_status === 'Pago',
                  }"
                >{{ record.trip_status }}</span>
              </div>
            </div>

            <div class="space-y-3 px-4 py-3">
              <div>
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">ID</p>
                <p class="mt-0.5 font-semibold text-gray-900 dark:text-white">ID{{ record.id }}</p>
              </div>
              <div v-if="record.trip_code">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Código da Viagem</p>
                <p class="mt-0.5 font-semibold text-gray-900 dark:text-white">{{ record.trip_code }}</p>
              </div>
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
                  <div class="mt-0.5 flex items-center gap-2 text-sm text-gray-800 dark:text-gray-200">
                    <img
                      v-if="record.driver?.profile_photo_url"
                      :src="record.driver.profile_photo_url"
                      :alt="record.driver.name"
                      class="h-7 w-7 rounded-full object-cover"
                      loading="lazy"
                    />
                    <span>{{ record.driver?.name || "—" }}</span>
                  </div>
                </div>
                <div>
                  <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Valor</p>
                  <p class="mt-0.5 text-sm font-semibold text-gray-900 dark:text-white">{{ money(record.amount) }}</p>
                </div>
                <div v-if="props.isAdmin" class="col-span-2">
                  <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Lucro da empresa</p>
                  <p class="mt-0.5 text-sm font-semibold text-gray-900 dark:text-white">{{ money(record.company_profit) }}</p>
                </div>
                <div v-if="props.isAdmin" class="col-span-2">
                  <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Responsável</p>
                  <p class="mt-0.5 text-sm text-gray-800 dark:text-gray-200">{{ record.responsible }}</p>
                </div>
                <div v-if="props.isAdmin && record.cancelled_by_role" class="col-span-2">
                  <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Cancelada por</p>
                  <p class="mt-0.5 text-sm text-gray-800 dark:text-gray-200">{{ record.cancelled_by_role }}: {{ record.cancelled_by_name }}</p>
                </div>
              </div>
            </div>

            <div
              v-if="props.isAdmin || canEditTrip(record) || canAdvanceTripStatus(record) || (props.canCancelTrips && record.trip_status !== 'Criada' && record.trip_status !== 'Cancelada' && record.driver_id !== null && !['Pago', 'Aguardando Aprovação'].includes(record.trip_status))"
              class="flex flex-wrap gap-2 border-t border-gray-100 px-4 py-3 dark:border-gray-700"
            >
              <button
                v-if="canEditTrip(record)"
                type="button"
                class="min-h-11 flex-1 rounded-md bg-cyan-700 px-3 py-2 text-sm font-semibold text-white hover:bg-cyan-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan-700"
                @click="startEdit(record)"
              >Editar</button>
              <button
                v-if="canAdvanceTripStatus(record)"
                type="button"
                class="min-h-11 flex-1 rounded-md bg-cyan-700 px-3 py-2 text-sm font-semibold text-white hover:bg-cyan-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan-700"
                @click="advanceTripStatus(record)"
              >{{ nextTripStatus(record.trip_status) }}</button>
              <button
                v-if="props.canCancelTrips && record.trip_status !== 'Criada' && record.trip_status !== 'Cancelada' && record.driver_id !== null && !['Pago', 'Aguardando Aprovação'].includes(record.trip_status)"
                type="button"
                class="min-h-11 flex-1 rounded-md bg-amber-600 px-3 py-2 text-sm font-semibold text-white hover:bg-amber-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-700"
                @click="returnTripToQueue(record)"
              >Devolver à fila</button>
              <button
                v-if="props.isAdmin && record.trip_status !== 'Cancelada'"
                type="button"
                class="min-h-11 flex-1 rounded-md bg-red-700 px-3 py-2 text-sm font-semibold text-white hover:bg-red-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700"
                @click="markTripCancelled(record)"
              >Marcar Cancelada</button>
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

      <Datatable class="col-span-12 hidden w-full min-w-0 lg:block" :thead="tableHeaders" :tbody="tableData" :row-columns="tableColumns" :column-renderers="tableColumnRenderers" :id="tableId" :order-by="[]" :row-class="tripRowClass" managed-rows />
        </div>
      </section>

      <div v-if="editingId" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-2 sm:items-center sm:p-4" @click.self="editingId = null">
        <form class="my-2 max-h-[calc(100dvh-1rem)] w-full max-w-2xl overflow-y-auto rounded-lg bg-white p-4 shadow-xl dark:bg-gray-900 sm:my-6 sm:max-h-[90vh] sm:p-6" data-select2-dropdown-parent @click.stop @submit.prevent="saveEdit">
          <div class="mb-5 flex items-center justify-between"><h2 class="text-lg font-semibold text-gray-900 dark:text-white">Editar deslocamento</h2><button type="button" aria-label="Fechar" class="text-2xl leading-none text-gray-500" @click="editingId = null">&times;</button></div>
          <div v-if="props.isAdmin || props.isAdministrative" class="grid grid-cols-1 gap-4">
            <label class="filter-label">Próximo status
              <select
                v-model="editForm.trip_status"
                class="mt-1 min-h-11 w-full rounded-md border-gray-300 shadow-sm focus:border-teal-700 focus:ring-teal-700 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
              >
                <option value="">Manter status atual</option>
                <option v-for="status in editTripStatusOptions" :key="status" :value="status">
                  {{ status }}
                </option>
              </select>
              <InputError :message="editForm.errors.trip_status" />
            </label>
          </div>
          <div v-if="props.isAdmin" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <label class="filter-label">Data<TextInput v-model="editForm.travel_date" type="date" class="mt-1 block w-full" required /><InputError :message="editForm.errors.travel_date" /></label>
            <label class="filter-label">Horário<TextInput v-model="editForm.travel_time" type="time" class="mt-1 block w-full" required /><InputError :message="editForm.errors.travel_time" /></label>
            <div class="filter-label">Tipo<Select v-model="editForm.status" :options="statusOptions" placeholder="Selecione o tipo" :allow-empty="false" required /><InputError :message="editForm.errors.status" /></div>
            <div class="filter-label">Motorista<Select v-model="selectedEditDriver" :options="props.drivers" label="name" track-by="id" placeholder="Selecione um motorista" :allow-empty="true" /><InputError :message="editForm.errors.driver_id" /></div>
            <div class="filter-label">Solicitação<Select v-model="editForm.solicitation" :options="props.solicitations" placeholder="Selecione uma empresa" :allow-empty="false" required /><span v-if="editForm.errors.solicitation" class="text-xs text-red-700">{{ editForm.errors.solicitation }}</span></div>
            <label class="filter-label">Código da Viagem<TextInput v-model="editForm.trip_code" type="text" class="mt-1 block w-full" maxlength="255" /><InputError :message="editForm.errors.trip_code" /></label>
            <label class="filter-label">Origem<TextInput v-model="editForm.origin" type="text" class="mt-1 block w-full" maxlength="255" required /><InputError :message="editForm.errors.origin" /></label>
            <label class="filter-label">Destino<TextInput v-model="editForm.destination" type="text" class="mt-1 block w-full" required /><InputError :message="editForm.errors.destination" /></label>
            <label class="filter-label">Passageiro<TextArea v-model="editForm.passenger" class="mt-1 block w-full" required /><InputError :message="editForm.errors.passenger" /></label>
            <label class="filter-label">Observação<TextArea v-model="editForm.observation" class="mt-1 block w-full" maxlength="5000" autocomplete="off" /><InputError :message="editForm.errors.observation" /></label>
            <label class="filter-label">Responsável<TextInput v-model="editForm.responsible" type="text" class="mt-1 block w-full" :readonly="props.isDriver" required /><InputError :message="editForm.errors.responsible" /></label>
            <label class="filter-label">Valor (R$)<TextInput v-model="editForm.amount" type="number" class="mt-1 block w-full" min="0" step="0.01" inputmode="decimal" required /><InputError :message="editForm.errors.amount" /></label>
            <label class="filter-label">Lucro da empresa (R$)<TextInput v-model="editForm.company_profit" type="number" class="mt-1 block w-full" min="0" step="0.01" inputmode="decimal" /><InputError :message="editForm.errors.company_profit" /></label>
          </div>
          <p v-if="editForm.hasErrors" class="mt-3 text-sm text-red-700">Verifique os campos informados.</p>
          <div class="mt-6 flex justify-end gap-2"><button type="button" class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-white dark:hover:bg-gray-800" @click="editingId = null">Cancelar</button><button type="submit" class="rounded-md bg-cyan-700 px-4 py-2 text-sm font-semibold text-white" :disabled="editForm.processing">{{ editForm.processing ? "Salvando..." : props.isAdmin ? "Salvar alterações" : "Atualizar status" }}</button></div>
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
:deep(#goali-trips-datatable .goali-action--status) { background-color: #0891b2; color: #fff; }
:deep(#goali-trips-datatable .goali-action--status:hover) { background-color: #0e7490; }
:deep(#goali-trips-datatable .goali-action--cancel) { background-color: #d97706; }
:deep(#goali-trips-datatable .goali-action--cancel:hover) { background-color: #b45309; }
:deep(#goali-trips-datatable .goali-action--cancelled) { background-color: #b91c1c; }
:deep(#goali-trips-datatable .goali-action--cancelled:hover) { background-color: #991b1b; }
:deep(#goali-trips-datatable .goali-action:focus-visible) {
  outline: 3px solid #93c5fd;
  outline-offset: 2px;
}
:deep(#goali-trips-datatable .goali-action:active) { transform: translateY(1px); }
:deep(.goali-trip--created > td) { background-color: #f3f4f6 !important; }
:deep(.goali-trip--unassigned > td) { background-color: #ffedd5 !important; }
:deep(.goali-trip--in-progress > td) { background-color: #eff6ff !important; }
:deep(.goali-trip--awaiting > td) { background-color: #fef3c7 !important; }
:deep(.goali-trip--finished > td) { background-color: #dcfce7 !important; }
:deep(.goali-trip--cancelled > td) { background-color: #fee2e2 !important; }
:deep(.goali-trip--cancelled > td) { background-color: #fee2e2 !important; }
</style>
