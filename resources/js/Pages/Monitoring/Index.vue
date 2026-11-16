<script setup>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
  metrics: { type: Object, default: () => ({ hostMemoryUsagePercent: null, containers: [] }) },
  available: { type: Boolean, default: false },
  checkedAt: { type: String, default: "" },
});

const formatBytes = (bytes) => {
  if (bytes === null || bytes === undefined) return "—";
  const units = ["B", "KB", "MB", "GB", "TB"];
  let value = Number(bytes);
  let unit = 0;
  while (value >= 1024 && unit < units.length - 1) {
    value /= 1024;
    unit += 1;
  }
  return `${value.toFixed(1)} ${units[unit]}`;
};

const formatPercent = (value) => value === null || value === undefined ? "—" : `${Number(value).toFixed(1)}%`;
const refresh = () => router.reload({ only: ["metrics", "available", "checkedAt"], preserveScroll: true });
</script>

<template>
  <BaseLayout title="MONITORAMENTO DO SISTEMA">
    <main class="col-span-12 w-full px-3 py-5 sm:px-6 lg:px-8">
      <section class="overflow-hidden rounded-2xl border border-cyan-100 bg-white shadow-lg dark:border-cyan-900/50 dark:bg-gray-900">
        <header class="flex flex-wrap items-center justify-between gap-4 border-b border-cyan-100 bg-cyan-50/70 px-5 py-5 dark:border-cyan-900/50 dark:bg-cyan-950/30">
          <div>
            <p class="text-sm font-semibold uppercase tracking-widest text-cyan-700 dark:text-cyan-300">Infraestrutura</p>
            <h1 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">Monitoramento do sistema</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Memória da EC2 e consumo dos containers.</p>
          </div>
          <button type="button" class="rounded-lg bg-cyan-700 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-800" @click="refresh">Atualizar</button>
        </header>

        <div v-if="!props.available" class="m-5 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800">Prometheus indisponível. Verifique se o stack de monitoramento está em execução.</div>
        <div class="grid gap-4 p-5 sm:grid-cols-2">
          <article class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">Uso de memória da EC2</p>
            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ formatPercent(props.metrics.hostMemoryUsagePercent) }}</p>
          </article>
          <article class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">Containers monitorados</p>
            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ props.metrics.containers.length }}</p>
          </article>
        </div>

        <div class="px-5 pb-5">
          <h2 class="mb-3 text-lg font-semibold text-gray-900 dark:text-white">Memória por container</h2>
          <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead><tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400"><th class="px-4 py-3">Container</th><th class="px-4 py-3">Memória utilizada</th></tr></thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800"><tr v-for="container in props.metrics.containers" :key="container.name" class="text-sm text-gray-700 dark:text-gray-200"><td class="px-4 py-3 font-semibold">{{ container.name }}</td><td class="px-4 py-3">{{ formatBytes(container.memoryBytes) }}</td></tr><tr v-if="!props.metrics.containers.length"><td colspan="2" class="px-4 py-8 text-center text-sm text-gray-500">Nenhuma métrica de container disponível.</td></tr></tbody>
            </table>
          </div>
          <p v-if="props.checkedAt" class="mt-3 text-xs text-gray-500 dark:text-gray-400">Última consulta: {{ props.checkedAt }}</p>
        </div>
      </section>
    </main>
  </BaseLayout>
</template>
