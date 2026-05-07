<script setup>
import Footer from "@/Components/Footer.vue";
import { Head } from "@inertiajs/vue3";
import { ref } from "vue";
import axios from "axios";

const verificationLinkSent = ref(false);

async function logout() {
  try {
    await axios.post(route("logout"));

    window.location.href = route("login");
  } catch (error) {
    console.error("Erro ao realizar logout:", error);
  }
}

async function verificationLink() {
  try {
    verificationLinkSent.value = true;

    await axios.post(route("verification.send"));
    
    await logout();
  } catch (error) {
    console.error("Erro ao enviar e-mail de verificação:", error);
  }
}
</script>

<template>
  <Head title="E-mail Verification" />

  <div class="flex items-center justify-center min-h-screen relative">
    <button
      class="absolute top-4 left-4 inline-flex justify-center items-center gap-x-3 text-center bg-black dark:bg-white shadow-sm hover:shadow-black border border-transparent text-white text-sm font-medium rounded-full focus:outline-none focus:shadow-blue-700/50 py-3 px-6"
      @click="logout"
    >
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
        <path d="M3 9l9-7 9 7v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9z" />
        <path d="M9 22V12h6v10" />
      </svg>
    </button>

    <div
      class="max-w-[50rem] flex flex-col items-center justify-center px-4 sm:px-6 lg:px-8"
    >
      <main id="content" class="text-center py-4">
        <h1 class="text-4xl font-bold text-gray-800 dark:text-white">
          Confirme seu E-mail
        </h1>
        <p class="mt-3 mb-4 text-gray-600 dark:text-neutral-400 whitespace-nowrap">
          Por favor, verifique sua caixa de entrada e clique no link de verificação
          enviado para o seu e-mail.
        </p>

        <div
          class="mt-2 flex flex-col justify-center items-center gap-2 sm:flex-row sm:gap-3"
        >
          <button
            class="inline-flex justify-center items-center gap-x-3 text-center bg-gradient-to-tl from-blue-600 to-violet-600 shadow-lg hover:shadow-blue-700/50 border border-transparent text-white text-sm font-medium rounded-full focus:outline-none focus:shadow-blue-700/50 py-3 px-6"
            @click="verificationLink"
            :disabled="verificationLinkSent"
          >
            <span v-if="verificationLinkSent">Enviando E-mail...</span>
            <svg
              v-if="!verificationLinkSent"
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
          </button>
        </div>
      </main>

      <Footer />
    </div>
  </div>
</template>
