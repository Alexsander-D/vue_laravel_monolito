<script setup>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import DialogModal from "@/Components/DialogModal.vue";
import InputError from "@/Components/InputError.vue";
import TextInput from "@/Components/TextInput.vue";
import { Link, router, useForm, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import Swal from "sweetalert2";

const props = defineProps({ companies: { type: Array, default: () => [] } });
const page = usePage();
const showModal = ref(false);
const editingId = ref(null);
const form = useForm({ name: "", departure_address: "", phone: "" });
const successMessage = computed(() => page.props.flash?.success || "");
const modalTitle = computed(() => editingId.value ? "Editar empresa" : "Nova empresa");
const goBack = () => window.history.back();

const resetForm = () => {
  form.reset();
  form.clearErrors();
  editingId.value = null;
};
const openCreate = () => {
  resetForm();
  showModal.value = true;
};
const openEdit = (company) => {
  editingId.value = company.id;
  form.name = company.name;
  form.departure_address = company.departure_address || "";
  form.phone = company.phone || "";
  form.clearErrors();
  showModal.value = true;
};
const closeModal = () => {
  if (!form.processing) {
    showModal.value = false;
    resetForm();
  }
};
const submit = () => {
  const options = { preserveScroll: true, onSuccess: closeModal };
  if (editingId.value) {
    form.put(route("goali.companies.update", editingId.value), options);
  } else {
    form.post(route("goali.companies.store"), options);
  }
};
const remove = async (company) => {
  const result = await Swal.fire({
    title: "Excluir empresa?",
    text: `Os passageiros vinculados a ${company.name} também serão removidos.`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Excluir",
    cancelButtonText: "Cancelar",
    confirmButtonColor: "#dc2626",
  });
  if (result.isConfirmed) router.delete(route("goali.companies.destroy", company.id), { preserveScroll: true });
};
</script>

<template>
  <BaseLayout title="GOALI · EMPRESAS">
    <main class="col-span-12 w-full px-3 py-5 sm:px-6 lg:px-8">
      <button type="button" class="mb-4 min-h-11 rounded-lg border border-cyan-700 px-4 py-2 text-sm font-semibold text-cyan-800 hover:bg-cyan-100 dark:text-cyan-200 dark:hover:bg-cyan-900/40" @click="goBack">Voltar</button>
      <section class="overflow-hidden rounded-2xl border border-cyan-100 bg-white shadow-lg dark:border-cyan-900/50 dark:bg-gray-900">
        <header class="flex flex-col gap-4 border-b border-cyan-100 bg-cyan-50/70 px-5 py-5 dark:border-cyan-900/50 dark:bg-cyan-950/30 sm:flex-row sm:items-center sm:justify-between">
          <div><p class="text-sm font-semibold uppercase tracking-widest text-cyan-700 dark:text-cyan-300">GOALI Transportes</p><h1 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">Empresas</h1><p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Cadastre empresas e seus endereços padrão de partida.</p></div>
          <button type="button" class="min-h-11 rounded-lg bg-cyan-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-cyan-800" @click="openCreate">Nova empresa</button>
        </header>
        <p v-if="successMessage" class="mx-5 mt-4 rounded-lg bg-cyan-50 px-4 py-3 text-sm font-medium text-cyan-800 dark:bg-cyan-950/40 dark:text-cyan-200">{{ successMessage }}</p>
        <div class="p-5">
          <div class="hidden overflow-x-auto md:block">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead><tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400"><th class="px-3 py-3">Empresa</th><th class="px-3 py-3">Endereço de partida</th><th class="px-3 py-3">Telefone</th><th class="px-3 py-3">Passageiros</th><th class="px-3 py-3 text-right">Ações</th></tr></thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
              <tr v-for="company in props.companies" :key="company.id" class="text-sm text-gray-700 dark:text-gray-200"><td class="px-3 py-4 font-semibold">{{ company.name }}</td><td class="px-3 py-4">{{ company.departure_address || "—" }}</td><td class="px-3 py-4">{{ company.phone || "—" }}</td><td class="px-3 py-4">{{ company.passengers_count }}</td><td class="px-3 py-4 text-right"><button class="mr-2 font-semibold text-cyan-700 hover:text-cyan-900 dark:text-cyan-300" @click="openEdit(company)">Editar</button><button class="font-semibold text-red-600 hover:text-red-800" @click="remove(company)">Excluir</button></td></tr>
              <tr v-if="!props.companies.length"><td colspan="5" class="px-3 py-10 text-center text-sm text-gray-500">Nenhuma empresa cadastrada.</td></tr>
            </tbody>
          </table>
          </div>
          <div class="grid gap-3 md:hidden">
            <article v-for="company in props.companies" :key="`card-${company.id}`" class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
              <div class="flex items-start justify-between gap-3">
                <h2 class="font-semibold text-gray-900 dark:text-white">{{ company.name }}</h2>
                <span class="rounded-full bg-cyan-100 px-2 py-1 text-xs font-semibold text-cyan-800 dark:bg-cyan-950/50 dark:text-cyan-200">{{ company.passengers_count }} passageiros</span>
              </div>
              <dl class="mt-3 space-y-2 text-sm text-gray-600 dark:text-gray-300">
                <div><dt class="font-semibold text-gray-500 dark:text-gray-400">Endereço de partida</dt><dd>{{ company.departure_address || "—" }}</dd></div>
                <div><dt class="font-semibold text-gray-500 dark:text-gray-400">Telefone</dt><dd>{{ company.phone || "—" }}</dd></div>
              </dl>
              <div class="mt-4 flex justify-end gap-4 border-t border-gray-200 pt-3 dark:border-gray-700">
                <button class="font-semibold text-cyan-700 hover:text-cyan-900 dark:text-cyan-300" @click="openEdit(company)">Editar</button>
                <button class="font-semibold text-red-600 hover:text-red-800" @click="remove(company)">Excluir</button>
              </div>
            </article>
            <p v-if="!props.companies.length" class="py-10 text-center text-sm text-gray-500">Nenhuma empresa cadastrada.</p>
          </div>
        </div>
      </section>
    </main>
    <DialogModal :show="showModal" max-width="2xl" @close="closeModal">
      <template #title>{{ modalTitle }}</template>
      <template #content><form class="grid gap-4" @submit.prevent="submit"><label class="font-medium text-sm text-gray-800 dark:text-gray-200">Nome *<TextInput v-model="form.name" class="mt-1 block w-full" maxlength="150" required /><InputError :message="form.errors.name" /></label><label class="font-medium text-sm text-gray-800 dark:text-gray-200">Endereço padrão de partida<TextInput v-model="form.departure_address" class="mt-1 block w-full" maxlength="255" placeholder="Ex.: Rua, número, bairro" /><InputError :message="form.errors.departure_address" /></label><label class="font-medium text-sm text-gray-800 dark:text-gray-200">Telefone<TextInput v-model="form.phone" class="mt-1 block w-full" maxlength="30" /><InputError :message="form.errors.phone" /></label></form></template>
      <template #footer><button type="button" class="mr-3 rounded-lg border border-gray-300 px-4 py-2 text-sm" @click="closeModal">Cancelar</button><button type="button" class="rounded-lg bg-cyan-700 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-800" :disabled="form.processing" @click="submit">{{ form.processing ? "Salvando..." : "Salvar" }}</button></template>
    </DialogModal>
  </BaseLayout>
</template>
