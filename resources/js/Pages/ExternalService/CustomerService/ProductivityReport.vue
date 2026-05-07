<script setup>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import { defineProps } from "vue";
import ProductivityReportForm from "./Partials/ProductivityReportForm.vue";

const props = defineProps({
    productivityData: {
        type: Object,
        required: true,
        default: () => [],
    },
});

</script>

<template>

    <BaseLayout title="ProductivityReport">
        <div class="w-full mx-auto pt-1">
            <div class="bg-white rounded-xl shadow shadow-lg p-4 dark:bg-gray-900">
                <ProductivityReportForm :productivityData="productivityData" />
            </div>
        </div>
    </BaseLayout>
    
</template>
