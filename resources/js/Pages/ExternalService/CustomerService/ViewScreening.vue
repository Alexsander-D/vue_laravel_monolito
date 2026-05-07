<script setup>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import { defineProps } from "vue";
import ViewScreeningTable from "./Partials/ViewScreeningTable.vue";
import Widget from "@/Components/Widget.vue";

const props = defineProps({
    customersInfo: {
        type: Array,
        default: () => [],
    },
    widgets: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <BaseLayout title="ViewScreening">
        <div class="w-full mx-auto pt-2">
            <!-- Card -->
            <div class="bg-white rounded-xl shadow shadow-lg p-4 dark:bg-gray-900">
                <Widget :info="props.widgets" />
                <ViewScreeningTable :customersInfo="props.customersInfo" />
            </div>
        </div>
    </BaseLayout>
</template>
