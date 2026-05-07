<script setup>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import { defineProps } from "vue";
import MaterialTable from "./Partials/MaterialTable.vue";

const props = defineProps({
    materialsData: {
        type: Object,
        required: true,
        default: () => [],
    },
});

</script>

<template>

    <BaseLayout title="Materials">
        <div class="w-full mx-auto pt-2">
            <div class="bg-white rounded-xl shadow shadow-lg p-4 dark:bg-gray-900">
                <MaterialTable :materialsData="props.materialsData" />
            </div>
        </div>
    </BaseLayout>

</template>
