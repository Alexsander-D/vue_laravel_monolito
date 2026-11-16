<script setup>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import DialogModal from "@/Components/DialogModal.vue";
import InputError from "@/Components/InputError.vue";
import Select from "@/Components/Select.vue";
import TextInput from "@/Components/TextInput.vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import Swal from "sweetalert2";

const props = defineProps({ passengers: { type: Array, default: () => [] }, companies: { type: Array, default: () => [] } });
const page = usePage();
const showModal = ref(false);
const editingId = ref(null);
const form = useForm({ company_id: null, name: "", boarding_point: "", phone: "" });
const useCompanyPhone = ref(false);
const successMessage = computed(() => page.props.flash?.success || "");
const modalTitle = computed(() => editingId.value ? "Editar passageiro" : "Novo passageiro");
const selectedCompany = computed({ get: () => props.companies.find((company) => company.id === Number(form.company_id)) || null, set: (company) => { form.company_id = company?.id ?? null; if (useCompanyPhone.value) form.phone = company?.phone || ""; } });
const resetForm = () => { form.reset(); form.clearErrors(); editingId.value = null; useCompanyPhone.value = false; };
const openCreate = () => { resetForm(); showModal.value = true; };
const openEdit = (passenger) => { editingId.value = passenger.id; form.company_id = passenger.company_id; form.name = passenger.name; form.boarding_point = passenger.boarding_point || ""; form.phone = passenger.phone || ""; form.clearErrors(); useCompanyPhone.value = false; showModal.value = true; };
const toggleCompanyPhone = () => { if (useCompanyPhone.value) form.phone = selectedCompany.value?.phone || ""; };
const closeModal = () => { if (!form.processing) { showModal.value = false; resetForm(); } };
const submit = () => { const options = { preserveScroll: true, onSuccess: closeModal }; editingId.value ? form.put(route("goali.passengers.update", editingId.value), options) : form.post(route("goali.passengers.store"), options); };
const remove = async (passenger) => { const result = await Swal.fire({ title: "Excluir passageiro?", text: `Remover ${passenger.name} do cadastro?`, icon: "warning", showCancelButton: true, confirmButtonText: "Excluir", cancelButtonText: "Cancelar", confirmButtonColor: "#dc2626" }); if (result.isConfirmed) router.delete(route("goali.passengers.destroy", passenger.id), { preserveScroll: true }); };
</script>

<template>
  <BaseLayout title="GOALI · PASSAGEIROS">
    <main class="col-span-12 w-full px-3 py-5 sm:px-6 lg:px-8">
      <section class="overflow-hidden rounded-2xl border border-cyan-100 bg-white shadow-lg dark:border-cyan-900/50 dark:bg-gray-900">
        <header class="flex flex-col gap-4 border-b border-cyan-100 bg-cyan-50/70 px-5 py-5 dark:border-cyan-900/50 dark:bg-cyan-950/30 sm:flex-row sm:items-center sm:justify-between"><div><p class="text-sm font-semibold uppercase tracking-widest text-cyan-700 dark:text-cyan-300">GOALI Transportes</p><h1 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">Passageiros</h1><p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Mantenha os passageiros vinculados à empresa correta.</p></div><button type="button" class="min-h-11 rounded-lg bg-cyan-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-cyan-800" @click="openCreate">Novo passageiro</button></header>
        <p v-if="successMessage" class="mx-5 mt-4 rounded-lg bg-cyan-50 px-4 py-3 text-sm font-medium text-cyan-800 dark:bg-cyan-950/40 dark:text-cyan-200">{{ successMessage }}</p>
        <div class="overflow-x-auto p-5"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700"><thead><tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400"><th class="px-3 py-3">Nome</th><th class="px-3 py-3">Empresa</th><th class="px-3 py-3">Ponto de embarque</th><th class="px-3 py-3">Telefone</th><th class="px-3 py-3 text-right">Ações</th></tr></thead><tbody class="divide-y divide-gray-100 dark:divide-gray-800"><tr v-for="passenger in props.passengers" :key="passenger.id" class="text-sm text-gray-700 dark:text-gray-200"><td class="px-3 py-4 font-semibold">{{ passenger.name }}</td><td class="px-3 py-4">{{ passenger.company?.name || "—" }}</td><td class="px-3 py-4">{{ passenger.boarding_point || "—" }}</td><td class="px-3 py-4">{{ passenger.phone || "—" }}</td><td class="px-3 py-4 text-right"><button class="mr-2 font-semibold text-cyan-700 hover:text-cyan-900 dark:text-cyan-300" @click="openEdit(passenger)">Editar</button><button class="font-semibold text-red-600 hover:text-red-800" @click="remove(passenger)">Excluir</button></td></tr><tr v-if="!props.passengers.length"><td colspan="5" class="px-3 py-10 text-center text-sm text-gray-500">Nenhum passageiro cadastrado.</td></tr></tbody></table></div>
        <div class="grid gap-3 md:hidden">
          <article v-for="passenger in props.passengers" :key="`card-${passenger.id}`" class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
            <h2 class="font-semibold text-gray-900 dark:text-white">{{ passenger.name }}</h2>
            <dl class="mt-3 space-y-2 text-sm text-gray-600 dark:text-gray-300"><div><dt class="font-semibold text-gray-500 dark:text-gray-400">Empresa</dt><dd>{{ passenger.company?.name || "—" }}</dd></div><div><dt class="font-semibold text-gray-500 dark:text-gray-400">Ponto de embarque</dt><dd>{{ passenger.boarding_point || "—" }}</dd></div><div><dt class="font-semibold text-gray-500 dark:text-gray-400">Telefone</dt><dd>{{ passenger.phone || "—" }}</dd></div></dl>
            <div class="mt-4 flex justify-end gap-4 border-t border-gray-200 pt-3 dark:border-gray-700"><button class="font-semibold text-cyan-700 hover:text-cyan-900 dark:text-cyan-300" @click="openEdit(passenger)">Editar</button><button class="font-semibold text-red-600 hover:text-red-800" @click="remove(passenger)">Excluir</button></div>
          </article>
          <p v-if="!props.passengers.length" class="py-10 text-center text-sm text-gray-500">Nenhum passageiro cadastrado.</p>
        </div>
      </section>
    </main>
    <DialogModal :show="showModal" max-width="2xl" @close="closeModal"><template #title>{{ modalTitle }}</template><template #content><form class="grid gap-4" @submit.prevent="submit"><label class="font-medium text-sm text-gray-800 dark:text-gray-200">Empresa *<Select v-model="selectedCompany" :options="props.companies" label="name" track-by="id" placeholder="Selecione a empresa" :allow-empty="false" class="mt-1" /><InputError :message="form.errors.company_id" /></label><label class="font-medium text-sm text-gray-800 dark:text-gray-200">Nome *<TextInput v-model="form.name" class="mt-1 block w-full" maxlength="150" required /><InputError :message="form.errors.name" /></label><label class="font-medium text-sm text-gray-800 dark:text-gray-200">Ponto de embarque<TextInput v-model="form.boarding_point" class="mt-1 block w-full" maxlength="255" /><InputError :message="form.errors.boarding_point" /></label><label class="font-medium text-sm text-gray-800 dark:text-gray-200">Telefone<label class="mt-2 flex items-center gap-2 text-sm font-normal"><input v-model="useCompanyPhone" type="checkbox" class="rounded border-gray-300 text-cyan-700" @change="toggleCompanyPhone" />Usar o telefone da empresa</label><TextInput v-model="form.phone" class="mt-1 block w-full" maxlength="30" :disabled="useCompanyPhone" /><InputError :message="form.errors.phone" /></label></form></template><template #footer><button type="button" class="mr-3 rounded-lg border border-gray-300 px-4 py-2 text-sm" @click="closeModal">Cancelar</button><button type="button" class="rounded-lg bg-cyan-700 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-800" :disabled="form.processing" @click="submit">{{ form.processing ? "Salvando..." : "Salvar" }}</button></template></DialogModal>
  </BaseLayout>
</template>

<style scoped>
@media (max-width: 767px) {
  section > .overflow-x-auto {
    display: none;
  }
}
</style>
