<script setup>
import { defineProps } from "vue";

const props = defineProps({
  type: {
    type: String,
    default: "button",
  },
});
</script>

<style scoped>
/* Classe personalizada */
.defaultTheme {
  /* border-color: var(--cor-secundaria); */
  background-color: var(--cor-secundaria);
  color: white;
  /* border-color: var(--cor-contraste); */
}

.defaultTheme:hover {
  /* border-color: var(--cor-secundaria); */
  background-color: var(--cor-principal);
  color: white;
  /* border-color: var(--cor-contraste); */
}

.defaultTheme:focus {
  /* border-color: var(--cor-secundaria); */
  background-color: rgb(37, 34, 201);
  color: white;
  /* border-color: var(--cor-contraste); */
}
</style>

<template>
  <button
    :type="type"
    class="defaultTheme inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 transition ease-in-out duration-150"
  >
    <slot />
  </button>
</template>
