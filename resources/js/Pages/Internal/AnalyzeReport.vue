<script setup>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import AnalyzeReportForm from "@/Pages/Internal/Partials/AnalyzeReportForm.vue";
import { defineProps } from "vue";

const props = defineProps({
  reportInfo: {
    type: Object,
    required: true,
    default: () => [],
  },
  defectsSolutionsInfo: {
    type: Object,
    default: () => [],
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
        <AnalyzeReportForm
          :reportInfo="props.reportInfo"
          :defectsSolutionsInfo="props.defectsSolutionsInfo"
        />
      </div>
      <!-- End Section -->
    </div>
    <!-- End Card Section -->
  </BaseLayout>
</template>
