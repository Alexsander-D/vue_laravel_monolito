<script setup>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import { computed, defineProps } from "vue";
import ManageComponents from "@/Pages/Internal/Partials/ManageComponents.vue";
import ManageDefectsAndSolutions from "@/Pages/Internal/Partials/ManageDefectsAndSolutions.vue";

const props = defineProps({
  componentsInfo: {
    type: Object,
    default: () => [],
  },
  defectsSolutionsInfo: {
    type: Object,
    default: () => [],
  },
});

const componentsInfo = computed(() => {
  return props.componentsInfo.map((components) => ({
    id: components.id,
    component: components.component,
    family: components.family,
  }));
});
</script>

<template>
  <BaseLayout title="Defects And Solutions">
    <!-- Card Section -->
    <div class="w-full mx-auto pt-1">
      <!-- Card -->
      <div class="bg-white rounded-xl shadow shadow-lg p-4 dark:bg-gray-900">
        <!-- Content -->
        <ManageComponents :components="componentsInfo" class="mt-10 md:mt-3" />
        <ManageDefectsAndSolutions
          :components="componentsInfo"
          :defectsSolutions="props.defectsSolutionsInfo"
          class="mt-10 md:mt-3"
        />
      </div>
      <!-- End Section -->
    </div>
    <!-- End Card Section -->
  </BaseLayout>
</template>
