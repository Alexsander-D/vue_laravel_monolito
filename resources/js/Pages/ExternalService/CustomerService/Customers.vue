<script setup>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import { defineProps } from "vue";
import CustomersForm from "@/Pages/ExternalService/CustomerService/Partials/CustomersForm.vue";

const props = defineProps({
  customersInfo: {
    type: Object,
    default: () => [],
  },
});

</script>

<template>

  <BaseLayout title="Customers">
    <div class="w-full mx-auto pt-1">
      <div class="bg-white rounded-xl shadow shadow-lg p-4 dark:bg-gray-900">
        <CustomersForm :customersInfo="props.customersInfo" />
      </div>
    </div>
  </BaseLayout>

</template>
