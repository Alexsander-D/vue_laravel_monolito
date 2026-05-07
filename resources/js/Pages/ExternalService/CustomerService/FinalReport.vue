<script setup>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import { defineProps } from "vue";
import FinalReportForm from "./Partials/FinalReportForm.vue";
import FinalReportTable from "./Partials/FinalReportTable.vue";

const props = defineProps({
    finalReportData: {
        type: Object,
        required: true,
        default: () => ({}),
    },
    userRole: {
        type: String,
        required: true,
        default: '',
    },
});

</script>

<template>

    <BaseLayout title="FinalReport">
        <div class="w-full mx-auto pt-2">
            <div class="bg-white rounded-xl shadow shadow-lg p-4 dark:bg-gray-900">
                <FinalReportForm :finalReportData="props.finalReportData" :userRole="props.userRole" />
                <FinalReportTable :finalReportData="props.finalReportData" />
            </div>
        </div>
    </BaseLayout>

</template>