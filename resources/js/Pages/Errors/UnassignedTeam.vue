<script setup>
import { Head } from "@inertiajs/vue3";
</script>

<template>
  <Head title="Acesso não atribuído" />

  <main class="flex min-h-screen items-center justify-center bg-gray-100 px-6 py-12 dark:bg-gray-900">
    <section
      class="w-full max-w-xl rounded-lg border border-gray-200 bg-white p-8 text-center shadow-sm dark:border-gray-700 dark:bg-gray-800"
      role="alert"
    >
      <p class="text-sm font-semibold uppercase tracking-wide text-red-600 dark:text-red-400">Acesso não disponível</p>
      <h1 class="mt-4 text-xl font-bold leading-relaxed text-gray-900 dark:text-white">
        ENTRE EM CONTATO COM UM ADMINISTRADOR PARA TER ACESSO AS INFORMAÇÕES
      </h1>
    </section>
  </main>
</template>
