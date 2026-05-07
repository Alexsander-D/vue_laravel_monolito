<script setup>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import ProductEntryForm from "./Partials/ProductEntryForm.vue";
import { defineProps } from "vue";

const props = defineProps({
    productsData: {
        type: Object,
        required: true,
        default: () => [],
    },
});
</script>

<template>

    <BaseLayout title="ProductEntry">
        <div class="w-full mx-auto pt-2">
            <div class="bg-white rounded-xl shadow shadow-lg p-4 dark:bg-gray-900">

                <ProductEntryForm :productsData="props.productsData" class="mt-10 md:mt-3" />

            </div>
        </div>
    </BaseLayout>
    
</template>
