<script setup>
import Widget from "@/Components/Widget.vue";
import BaseLayout from "@/Layouts/BaseLayout.vue";
import { defineProps } from "vue";
import ViewProductivity from "./Partials/ViewProductivity.vue";

const props = defineProps({
    productivityData: {
        type: Object,
        required: true,
        default: () => ({
            screeningId: null,
            screeningStatus: '',
            reports: [],
            widgets: [],
        }),
    },
});

</script>

<template>

    <BaseLayout title="Productivity">
        <div class="w-full mx-auto pt-1">
            <div class="bg-white rounded-xl shadow shadow-lg p-4 dark:bg-gray-900">
                <Widget :info="props.productivityData.widgets" />
                <ViewProductivity :productivityData="productivityData" class="mt-10 md:mt-3" />
            </div>
        </div>
    </BaseLayout>
    
</template>
