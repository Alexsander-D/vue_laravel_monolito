<script setup>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import { defineProps } from "vue";
import ProductTransferbyAdminForm from "@/Pages/Internal/Partials/ProductTransferbyAdminForm.vue";

const props = defineProps({
  allowedUsers: { type: Array, default: () => [] },
});
</script>

<template>
  <BaseLayout title="Internal">
    <!-- Card Section -->
    <div class="w-full mx-auto pt-1">
      <!-- Card -->
      <div class="bg-white rounded-xl shadow shadow-lg p-4 dark:bg-gray-900">
        <!-- Content -->
        <ProductTransferbyAdminForm :allowedUsers="allowedUsers" class="mt-10 md:mt-3" />
      </div>
      <!-- End Section -->
    </div>
    <!-- End Card Section -->
  </BaseLayout>
</template>
