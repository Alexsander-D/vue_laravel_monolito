<script setup>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import ManageProducts from "@/Pages/Registration/Products/Partials/ManageProducts.vue";
</script>

<template>
  <BaseLayout title="Products">
    <!-- Card Section -->
    <div class="w-full mx-auto pt-1">
      <!-- Card -->
      <div class="bg-white rounded-xl shadow shadow-lg p-4 dark:bg-gray-900">
        <!-- Content -->
        <ManageProducts class="mt-10 md:mt-3" />
      </div>
      <!-- End Section -->
    </div>
    <!-- End Card Section -->
  </BaseLayout>
</template>
