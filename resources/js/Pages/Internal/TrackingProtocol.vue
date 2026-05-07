<script setup>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import TrackingForm from "@/Pages/Internal/Partials/TrackingForm.vue";
</script>

<template>
  <BaseLayout title="Internal">
    <!-- Card Section -->
    <div class="w-full mx-auto pt-1">
      <!-- Card -->
      <div class="bg-white rounded-xl shadow shadow-lg p-4 dark:bg-gray-900">
        <!-- Content -->
        <TrackingForm class="mt-10 md:mt-3" />
      </div>
      <!-- End Section -->
    </div>
    <!-- End Card Section -->
  </BaseLayout>
</template>
