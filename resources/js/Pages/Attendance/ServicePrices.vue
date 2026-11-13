<script setup>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import { Link, useForm } from "@inertiajs/vue3";
import Swal from "sweetalert2";

const props = defineProps({
  services: {
    type: Array,
    required: true,
  },
});

const form = useForm({
  services: props.services.map((service) => ({
    id: service.id,
    name: service.name,
    icon: service.icon,
    price: Number(service.price).toFixed(2),
  })),
});

const savePrices = () => {
  form.put(route("attendance.service-prices.update"), {
    preserveScroll: true,
    onSuccess: () => Swal.fire({
      icon: "success",
      title: "Preços atualizados",
      text: "Os novos valores já estão disponíveis para os atendimentos.",
    }),
  });
};
</script>

<template>
  <BaseLayout title="Preços dos serviços">
    <main class="mx-auto w-full max-w-3xl px-4 py-6">
      <section class="rounded-xl bg-white p-5 shadow-lg dark:bg-gray-900 sm:p-7">
        <header class="mb-6 flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Preços dos serviços</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Atualize os valores usados nos novos atendimentos.</p>
          </div>
          <Link
            :href="route('dashboard')"
            class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-100 dark:hover:bg-gray-800"
          >Voltar</Link>
        </header>

        <form class="space-y-4" @submit.prevent="savePrices">
          <div
            v-for="(service, index) in form.services"
            :key="service.id"
            class="grid grid-cols-1 items-center gap-2 rounded-lg border border-gray-200 p-3 dark:border-gray-700 sm:grid-cols-[1fr_auto]"
          >
            <label :for="`service-price-${service.id}`" class="font-medium text-gray-800 dark:text-gray-100">
              <span class="mr-2">{{ service.icon }}</span>{{ service.name }}
            </label>
            <div>
              <div class="flex items-center gap-2">
                <span class="text-sm text-gray-500">R$</span>
                <input
                  :id="`service-price-${service.id}`"
                  v-model="service.price"
                  type="number"
                  min="0"
                  max="99999999.99"
                  step="0.01"
                  required
                  class="w-36 rounded-md border-gray-300 text-right shadow-sm focus:border-teal-700 focus:ring-teal-700 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                />
              </div>
              <p v-if="form.errors[`services.${index}.price`]" class="mt-1 text-sm text-red-600">
                {{ form.errors[`services.${index}.price`] }}
              </p>
            </div>
          </div>

          <div class="flex justify-end border-t border-gray-200 pt-4 dark:border-gray-700">
            <button
              type="submit"
              :disabled="form.processing"
              class="rounded-md bg-teal-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-teal-800 disabled:cursor-not-allowed disabled:opacity-50"
            >{{ form.processing ? "Salvando..." : "Salvar preços" }}</button>
          </div>
        </form>
      </section>
    </main>
  </BaseLayout>
</template>
