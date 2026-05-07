<script setup>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import SetQueueForm from "@/Pages/Internal/Partials/SetQueueForm.vue";
import { defineProps } from "vue";

const props = defineProps({
  allowedUsers: {
    type: Array,
    required: true,
    default: () => [],
  },
  userId: {
    type: Object,
    required: false,
    default: () => null,
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
        <SetQueueForm
          :allowedUsers="props.allowedUsers"
          :userId="props.userId"
          class="mt-10 md:mt-3"
        />
      </div>
      <!-- End Section -->
    </div>
    <!-- End Card Section -->
  </BaseLayout>
</template>
