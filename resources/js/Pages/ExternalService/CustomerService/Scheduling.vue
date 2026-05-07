<script setup>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import { defineProps } from "vue";
import SchedulingTable from "./Partials/SchedulingTable.vue";
import SchedulingForm from "./Partials/SchedulingForm.vue";

const props = defineProps({
    schedulingData: {
        type: Object,
        required: true,
        default: () => [],
    },
});

</script>

<template>

    <BaseLayout title="Scheduling">
        <div class="w-full mx-auto pt-2">
            <div class="bg-white rounded-xl border border-gray-700 shadow p-4 dark:bg-gray-900">
                <SchedulingTable :schedulingData="props.schedulingData" />
                <SchedulingForm :schedulingData="props.schedulingData" />
            </div>
        </div>
    </BaseLayout>
    
</template>
