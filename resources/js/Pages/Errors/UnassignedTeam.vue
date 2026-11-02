<script setup>
import { Head, router } from "@inertiajs/vue3";

const logout = () => router.post(route("logout"));
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
      <button
        type="button"
        class="mt-6 rounded-md bg-gray-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:bg-gray-200 dark:text-gray-900 dark:hover:bg-white"
        @click="logout"
      >
        Sair
      </button>
    </section>
  </main>
</template>
