<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { defineProps } from "vue";

const props = defineProps({
  welcomeImage: String,
});
</script>

<style scoped>
/* Classe personalizada */
.colorBase {
  color: var(--cor-secundaria);
}
</style>

<template>
  <Head title="Welcome" />
  <!-- Hero -->
  <div
    class="relative bg-gradient-to-b from-violet-600/10 via-transparent overflow-hidden min-h-screen"
  >
    <div class="w-full mx-auto px-4 py-8 space-y-8 relative z-10">
      <!-- Title -->
      <div class="max-w-3xl text-center mx-auto">
        <div class="colorBase text-7xl flex items-center justify-center">
          <img src="/pos-venda/images/logomarca.png" alt="Vex" class="w-48 h-48" />
        </div>
      </div>
      <!-- End Title -->

      <div class="max-w-4xl text-center mx-auto bg-black/50 backdrop-blur-sm rounded">
        <p class="text-lg text-white">
          Plataforma desenvolvida para atender às necessidades do pós-venda, integrando
          sistemas e proporcionando dados para decisões assertivas e eficientes.
        </p>
      </div>

      <div class="text-center">
        <Link
          v-if="$page.props.auth.user"
          :href="route('dashboard')"
          class="inline-flex justify-center items-center gap-x-3 text-center bg-gradient-to-tl from-blue-600 to-violet-600 shadow-lg hover:shadow-blue-700/50 border border-transparent text-white text-sm font-medium rounded-full focus:outline-none focus:shadow-blue-700/50 py-3 px-6"
        >
          Continuar
          <svg
            class="shrink-0 size-4"
            xmlns="http://www.w3.org/2000/svg"
            width="24"
            height="24"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <path d="m9 18 6-6-6-6" />
          </svg>
        </Link>
        <Link
          v-else
          class="inline-flex justify-center items-center gap-x-3 text-center bg-gradient-to-tl from-blue-600 to-violet-600 shadow-lg hover:shadow-blue-700/50 border border-transparent text-white text-sm font-medium rounded-full focus:outline-none focus:shadow-blue-700/50 py-3 px-6"
          :href="route('login')"
        >
          Continuar
          <svg
            class="shrink-0 size-4"
            xmlns="http://www.w3.org/2000/svg"
            width="24"
            height="24"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <path d="m9 18 6-6-6-6" />
          </svg>
        </Link>
      </div>
      <!-- End Buttons -->
    </div>

    <!-- Optional Background Image or Video -->
    <div
      class="absolute inset-0 bg-cover bg-center bg-no-repeat"
      :style="{ backgroundImage: `url('${props.welcomeImage}')` }"
    ></div>
  </div>
  <!-- End Hero -->

  <!-- Inconsistencies Button - Positioned at the bottom -->
  <div class="fixed bottom-4 w-full flex justify-center px-4">
    <a
      class="group inline-flex items-center bg-white/90 hover:bg-white/70 border border-white/10 p-3 px-6 rounded-full shadow-md focus:outline-none w-auto sm:w-auto md:w-auto lg:w-auto xl:w-auto flex"
      href="https://mail.google.com/mail/u/0/?fs=1&to=spm@grupomultilaser.com.br&su=SUPORTE%20-%20Vex&tf=cm"
      target="_blank"
    >
      <p class="me-2 text-black text-sm text-center">
        Em caso de inconsistências, entre em contato com o suporte.
      </p>
    </a>
  </div>
</template>
