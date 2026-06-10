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
  <Head title="Bem-vindo" />

  <div class="relative min-h-screen overflow-hidden">
    <!-- Background -->
    <div
      class="absolute inset-0 bg-cover bg-center"
      :style="{ backgroundImage: `url('${props.welcomeImage}')` }"
    />

    <!-- Overlay -->
    <div class="absolute inset-0 bg-black/70 backdrop-blur-[2px]" />

    <!-- Content -->
    <div
      class="relative z-10 flex min-h-screen flex-col items-center justify-center px-6 text-center"
    >
      <!-- Logo -->
      <!-- <img
        src="/images/logomarca.png"
        alt="Barbearia Carioca"
        class="w-64 md:w-80 drop-shadow-2xl"
      /> -->

      <!-- Texto -->
      <h1
        class="mt-6 text-4xl md:text-6xl font-bold text-yellow-400 tracking-wider"
      >
        Barbearia Carioca
      </h1>

      <p
        class="mt-4 max-w-xl text-gray-200 text-lg md:text-xl"
      >
        Tradição, estilo e atendimento de excelência.
      </p>

      <p
        class="mt-2 max-w-lg text-gray-400"
      >
        Faça login para acessar o sistema e gerenciar seus atendimentos.
      </p>

      <!-- Botão -->
      <Link
        :href="$page.props.auth.user ? route('dashboard') : route('login')"
        class="mt-10 inline-flex items-center gap-2 rounded-full
               bg-gradient-to-r from-yellow-500 to-amber-300
               px-8 py-4 text-lg font-semibold text-black
               shadow-lg transition-all duration-300
               hover:scale-105 hover:shadow-yellow-500/40"
      >
        {{
          $page.props.auth.user
            ? "Entrar no Sistema"
            : "Fazer Login"
        }}

        <svg
          class="h-5 w-5"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M9 5l7 7-7 7"
          />
        </svg>
      </Link>

      <!-- Rodapé -->
      <div
        class="absolute bottom-6 text-sm text-gray-500"
      >
        © {{ new Date().getFullYear() }} Barbearia Carioca
      </div>
    </div>
  </div>
</template>
