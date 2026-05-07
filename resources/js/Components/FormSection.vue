<script setup>
import { computed, useSlots } from "vue";
import SectionTitle from "@/Components/SectionTitle.vue";
import { defineEmits } from "vue";

defineEmits(["submitted"]);

const hasActions = computed(() => !!useSlots().actions);
</script>

<style scoped>
.defaultTheme {
  background-color: var(--cor-fundo);
}
</style>

<template>
  <div class="mt-5 md:mt-0 md:col-span-2 rounded-md border dark:border-gray-700">
    <form @submit.prevent="$emit('submitted')">
      <div
        class="px-4 py-5 sm:p-6 shadow"
        :class="hasActions ? 'sm:rounded-tl-md sm:rounded-tr-md' : 'sm:rounded-md'"
      >
        <SectionTitle>
          <template #title>
            <slot name="title" />
          </template>
          <template #description>
            <slot name="description" />
          </template>
        </SectionTitle>
        <slot name="form" />
      </div>

      <div
        v-if="hasActions"
        class="defaultTheme flex items-center justify-end px-4 py-3 text-end sm:px-6 shadow sm:rounded-bl-md sm:rounded-br-md"
      >
        <slot name="actions" />
      </div>
    </form>
  </div>
</template>
