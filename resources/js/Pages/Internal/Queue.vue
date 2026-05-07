<script setup>
import Widget from "@/Components/Widget.vue";
import BaseLayout from "@/Layouts/BaseLayout.vue";
import QueueTable from "@/Pages/Internal/Partials/QueueTable.vue";
import { defineProps } from "vue";

const props = defineProps({
  queues: {
    type: Array,
    required: true,
    default: () => [],
  },
  widgets: {
    type: Array,
    required: true,
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
        <Widget :info="props.widgets" />
        <QueueTable :queue="props.queues" class="mt-10 md:mt-3" />
      </div>
      <!-- End Section -->
    </div>
    <!-- End Card Section -->
  </BaseLayout>
</template>
