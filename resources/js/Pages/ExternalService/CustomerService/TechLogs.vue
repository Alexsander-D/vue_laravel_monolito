<script setup>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import { defineProps } from "vue";
import TechLogsTable from "./Partials/TechLogsTable.vue";

const props = defineProps({
    logInfo: {
        type: Array,
        default: () => [],
    },
});

</script>

<template>

    <BaseLayout title="TechLog">
        <div class="w-full mx-auto pt-2">
            <div class="bg-white rounded-xl shadow shadow-lg p-4 dark:bg-gray-900">
                <TechLogsTable :logInfo="props.logInfo" />
            </div>
        </div>
    </BaseLayout>

</template>
