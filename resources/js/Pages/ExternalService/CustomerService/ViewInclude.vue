<script setup>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import { defineProps } from "vue";
import ViewIncludeTable from "./Partials/ViewIncludeTable.vue";

const props = defineProps({
    customersInfo: {
        type: Array,
        default: () => [],
    },
});

</script>

<template>

    <BaseLayout title="ViewInclude">
        <div class="w-full mx-auto pt-2">
            <!-- Card -->
            <div class="bg-white rounded-xl shadow shadow-lg p-4 dark:bg-gray-900">
                <ViewIncludeTable :customersInfo="props.customersInfo" />
            </div>
        </div>
    </BaseLayout>

</template>
