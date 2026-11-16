<script setup>
import { Head } from "@inertiajs/vue3";
</script>

<template>

  <Head title="Baixar aplicativo GOALI" />

  <main class="flex min-h-screen items-center justify-center bg-gray-100 px-4 py-10 dark:bg-gray-950">
    <section class="w-full max-w-lg rounded-2xl bg-white p-6 text-center shadow-lg dark:bg-gray-900 sm:p-10">

      <span v-if="$page.props.auth?.user?.current_team?.name?.trim()?.toLocaleUpperCase() === 'GOALI TRANSPORTES'"
        class="text-lg font-semibold stringColor sm:text-xl">
        GOALI
      </span>
      <img v-else src="/images/logomarca.png" alt="barbearia" class="h-12 w-24 sm:h-16 sm:w-32" />


      <div
        class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200">
        <svg class="h-8 w-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
          stroke-width="1.8">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M12 16V4m0 0L7 9m5-5 5 5M5 14v4a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4" />
        </svg>
      </div>

      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Aplicativo GOALI</h1>
      <p class="mt-3 text-gray-600 dark:text-gray-300">
        Baixe o aplicativo para Android pelo botão abaixo.
      </p>

      <a :href="route('app.download')"
        class="mt-7 inline-flex w-full items-center justify-center rounded-lg bg-teal-700 px-5 py-3 font-semibold text-white transition hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
        Baixar aplicativo Android
      </a>
    </section>
  </main>
</template>
