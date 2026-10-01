<script setup>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import Select from "@/Components/Select.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import { router, useForm } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import Swal from "sweetalert2";

const props = defineProps({
  records: { type: Object, required: true },
  filters: { type: Object, required: true },
  solicitations: { type: Array, default: () => [] },
  summary: { type: Object, required: true },
});

const filters = useForm({ ...props.filters });
const statusOptions = ["ENTRADA", "SAÍDA"];
const editingId = ref(null);
const editForm = useForm({});
const reportUrl = computed(() => route("goali.export", filters.data()));

watch(() => props.filters, (value) => filters.defaults(value).reset(), { deep: true });
watch(() => editForm.solicitation, (company) => {
  editForm.origin = company || "";
  if (company) editForm.clearErrors("solicitation");
});

const applyFilters = () => filters.get(route("goali.index"), { preserveState: true, preserveScroll: true });
const startEdit = (record) => {
  editingId.value = record.id;
  editForm.defaults({
    travel_date: record.travel_date,
    travel_time: String(record.travel_time).slice(0, 5),
    status: record.status,
    solicitation: record.solicitation,
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
const money = (value) => Number(value || 0).toLocaleString("pt-BR", { style: "currency", currency: "BRL" });
const date = (value) => new Date(`${value}T00:00:00`).toLocaleDateString("pt-BR");
</script>

<template>
  <BaseLayout title="Relatório Goali">
    <main class="mx-auto w-full max-w-screen-2xl px-3 py-5 sm:px-6">
      <header class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
          <p class="text-xs font-bold uppercase text-teal-700">GT Dionínio Barbosa</p>
          <h1 class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">Relatório de deslocamentos</h1>
        </div>
        <div class="flex gap-2">
          <a :href="route('goali.form')" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-100">Novo registro</a>
          <a :href="reportUrl" class="rounded-md bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800">Exportar Excel</a>
        </div>
      </header>

      <form class="mb-5 grid grid-cols-1 gap-3 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900 sm:grid-cols-2 xl:grid-cols-5" @submit.prevent="applyFilters">
        <label class="filter-label">Data inicial<TextInput v-model="filters.startDate" type="date" class="mt-1 block w-full" /></label>
        <label class="filter-label">Data final<TextInput v-model="filters.endDate" type="date" class="mt-1 block w-full" /></label>
        <label class="filter-label">Status<Select v-model="filters.status" :options="statusOptions" placeholder="Todos" /></label>
        <label class="filter-label">Solicitação<Select v-model="filters.solicitation" :options="props.solicitations" placeholder="Todas" /></label>
        <button class="self-end rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700" type="submit">Filtrar</button>
      </form>

      <section class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div class="border-l-4 border-teal-600 bg-white px-5 py-4 dark:bg-gray-900"><p class="text-sm text-gray-500">Registros no período</p><p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ props.summary.count }}</p></div>
        <div class="border-l-4 border-amber-500 bg-white px-5 py-4 dark:bg-gray-900"><p class="text-sm text-gray-500">Valor total</p><p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ money(props.summary.amount) }}</p></div>
      </section>

      <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <table class="min-w-full divide-y divide-gray-200 text-left text-sm dark:divide-gray-700">
          <thead class="bg-gray-50 text-xs uppercase text-gray-600 dark:bg-gray-800 dark:text-gray-300"><tr><th class="px-4 py-3">Data / Hora</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Solicitação</th><th class="px-4 py-3">Origem / Destino</th><th class="px-4 py-3">Passageiro</th><th class="px-4 py-3">Responsável</th><th class="px-4 py-3 text-right">Valor</th><th class="px-4 py-3 text-right">Ações</th></tr></thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            <tr v-for="record in props.records.data" :key="record.id" class="text-gray-700 dark:text-gray-200">
              <td class="whitespace-nowrap px-4 py-3">{{ date(record.travel_date) }}<span class="block text-xs text-gray-500">{{ String(record.travel_time).slice(0, 5) }}</span></td>
              <td class="px-4 py-3">{{ record.status }}</td><td class="px-4 py-3">{{ record.solicitation }}</td>
              <td class="min-w-52 px-4 py-3">{{ record.origin }}<span class="mx-1 text-gray-400">→</span>{{ record.destination }}</td>
              <td class="px-4 py-3">{{ record.passenger }}</td><td class="px-4 py-3">{{ record.responsible }}</td><td class="whitespace-nowrap px-4 py-3 text-right">{{ money(record.amount) }}</td>
              <td class="whitespace-nowrap px-4 py-3 text-right"><button type="button" class="mr-3 font-semibold text-teal-700 hover:underline" @click="startEdit(record)">Editar</button><button type="button" class="font-semibold text-red-700 hover:underline" @click="deleteRecord(record)">Excluir</button></td>
            </tr>
            <tr v-if="props.records.data.length === 0"><td colspan="8" class="px-4 py-10 text-center text-gray-500">Nenhum registro encontrado para os filtros selecionados.</td></tr>
          </tbody>
        </table>
      </div>

      <nav v-if="props.records.links?.length > 3" class="mt-4 flex flex-wrap gap-2" aria-label="Paginação">
        <template v-for="link in props.records.links" :key="link.label"><span v-if="!link.url" class="rounded border px-3 py-2 text-sm text-gray-400" v-html="link.label"></span><a v-else :href="link.url" class="rounded border px-3 py-2 text-sm" :class="link.active ? 'border-teal-700 bg-teal-700 text-white' : 'border-gray-300 text-gray-700 hover:bg-gray-50'" v-html="link.label"></a></template>
      </nav>

      <div v-if="editingId" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 sm:items-center" @click.self="editingId = null">
        <form class="my-6 max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-lg bg-white p-6 shadow-xl dark:bg-gray-900" @submit.prevent="saveEdit">
          <div class="mb-5 flex items-center justify-between"><h2 class="text-lg font-semibold text-gray-900 dark:text-white">Editar deslocamento</h2><button type="button" aria-label="Fechar" class="text-2xl leading-none text-gray-500" @click="editingId = null">&times;</button></div>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <label class="filter-label">Data<TextInput v-model="editForm.travel_date" type="date" class="mt-1 block w-full" required /><InputError :message="editForm.errors.travel_date" /></label>
            <label class="filter-label">Horário<TextInput v-model="editForm.travel_time" type="time" class="mt-1 block w-full" required /><InputError :message="editForm.errors.travel_time" /></label>
            <label class="filter-label">Status<Select v-model="editForm.status" :options="statusOptions" placeholder="Selecione status" :allow-empty="false" required /><InputError :message="editForm.errors.status" /></label>
            <label class="filter-label">Solicitação<Select v-model="editForm.solicitation" :options="props.solicitations" placeholder="Selecione uma empresa" :allow-empty="false" required /><span v-if="editForm.errors.solicitation" class="text-xs text-red-700">{{ editForm.errors.solicitation }}</span></label>
            <label class="filter-label">Origem<TextInput v-model="editForm.origin" type="text" class="mt-1 block w-full" readonly required /><InputError :message="editForm.errors.origin" /></label>
            <label class="filter-label">Destino<TextInput v-model="editForm.destination" type="text" class="mt-1 block w-full" required /><InputError :message="editForm.errors.destination" /></label>
            <label class="filter-label">Passageiro<TextInput v-model="editForm.passenger" type="text" class="mt-1 block w-full" required /><InputError :message="editForm.errors.passenger" /></label>
            <label class="filter-label">Responsável<TextInput v-model="editForm.responsible" type="text" class="mt-1 block w-full" required /><InputError :message="editForm.errors.responsible" /></label>
            <label class="filter-label">Valor (R$)<TextInput v-model="editForm.amount" type="number" class="mt-1 block w-full" min="0" step="0.01" inputmode="decimal" required /><InputError :message="editForm.errors.amount" /></label>
          </div>
          <p v-if="editForm.hasErrors" class="mt-3 text-sm text-red-700">Verifique os campos informados.</p>
          <div class="mt-6 flex justify-end gap-2"><button type="button" class="rounded-md border border-gray-300 px-4 py-2 text-sm" @click="editingId = null">Cancelar</button><button type="submit" class="rounded-md bg-teal-700 px-4 py-2 text-sm font-semibold text-white" :disabled="editForm.processing">Salvar alterações</button></div>
        </form>
      </div>
    </main>
  </BaseLayout>
</template>

<style scoped>
.filter-label { display: flex; flex-direction: column; gap: .4rem; color: #4b5563; font-size: .8rem; font-weight: 600; }
</style>