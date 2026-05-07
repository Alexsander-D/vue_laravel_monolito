<script setup>
import SectionBorder from "@/Components/SectionBorder.vue";
</script>
<template>
  <div class="mb-2 text-center">
    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
      <slot name="title" />
    </h2>
    <p class="text-sm text-gray-600 dark:text-neutral-400 leading-tight">
      <slot name="description" />
    </p>
    <SectionBorder />
  </div>
</template>
