<script setup>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import EntrancesForm from "@/Pages/Internal/Partials/EntrancesForm.vue";
import { defineProps } from "vue";

const props = defineProps({
  tracking: {
    type: Array,
    required: true,
    default: () => [],
  },
  date: {
    type: Object,
  },
});
</script>

<template>
  <BaseLayout title="Internal">
    <div class="w-full mx-auto pt-1">
      <div class="bg-white rounded-xl shadow shadow-lg p-4 dark:bg-gray-900">
        <EntrancesForm :tracking="props.tracking" :date="props.date" class="mt-10 md:mt-3" />
      </div>
    </div>
  </BaseLayout>
</template>
