<template>
  <div class="py-2 mx-full">
    <div class="grid sm:grid-cols-4 gap-3">
      <div v-for="data in info" :key="data.title"
        class="flex flex-col bg-white border shadow-md rounded-xl dark:bg-neutral-800 border-neutral-400 colorBase cursor-pointer hover:shadow-lg transition"
        @click="redirect(data)">
        <div class="p-4 md:p-5 flex flex-col justify-center h-full">
          <div class="flex justify-center">
            <p class="text-xs uppercase tracking-wide">
              {{ data.title }}
            </p>
          </div>

          <div class="flex justify-center mt-1">
            <h3 class="text-xl sm:text-2xl font-medium">
              {{ data.value }}
            </h3>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps } from "vue";
import { router } from "@inertiajs/vue3";

defineProps({
  info: {
    type: Object,
    required: true,
    default: () => [],
  },
  selectedStatus: String,
});

const redirect = (data) => {
  if (!data.route) return;

  let params = {};
  if (data.filter) params = data.filter;

  router.get(route(data.route), params, { preserveState: true, preserveScroll: true });
};
</script>

<style scoped>
.colorBase {
  color: var(--cor-contraste);
  background-color: var(--cor-fundo);
}
</style>
