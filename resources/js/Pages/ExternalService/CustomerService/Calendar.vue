<script setup>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import { defineProps } from "vue";
import ViewCalendar from "./Partials/ViewCalendar.vue";


const props = defineProps({
    calendarData: {
        type: Object,
        required: true,
    },
})
</script>

<template>
    <BaseLayout title="Calendar">
        <div class="w-full mx-auto pt-2">
            <!-- Card -->
            <div class="bg-white rounded-xl shadow shadow-lg p-4 dark:bg-gray-900">
                <ViewCalendar :calendarData="props.calendarData" />
            </div>
        </div>
    </BaseLayout>
</template>