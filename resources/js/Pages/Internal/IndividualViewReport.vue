<script setup>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import IndividualViewReportTable from "@/Pages/Internal/Partials/IndividualViewReportTable.vue";
import { defineProps } from "vue";

const props = defineProps({
  date: {
    type: Object,
  },
});
</script>

<template>
  <BaseLayout title="Internal">
    <!-- Card Section -->
    <div class="w-full mx-auto pt-1">
      <!-- Card -->
      <div class="bg-white rounded-xl shadow shadow-lg p-4 dark:bg-gray-900">
        <!-- Content -->
        <IndividualViewReportTable :date="props.date" class="mt-10 md:mt-3" />
      </div>
      <!-- End Section -->
    </div>
    <!-- End Card Section -->
  </BaseLayout>
</template>
