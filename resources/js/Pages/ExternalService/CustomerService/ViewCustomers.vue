<script setup>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import { defineProps } from "vue";
import CustomersTable from "@/Pages/ExternalService/CustomerService/Partials/CustomersTable.vue";

const props = defineProps({
    customersInfo: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>

    <BaseLayout title="ViewCustomers">
        <div class="w-full mx-auto pt-2">
            <div class="bg-white rounded-xl shadow shadow-lg p-4 dark:bg-gray-900">
                <CustomersTable :customersInfo="customersInfo" />
            </div>
        </div>
    </BaseLayout>

</template>
