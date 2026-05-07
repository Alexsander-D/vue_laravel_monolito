<script setup>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import { defineProps } from "vue";
import ScreeningForm from "./Partials/ScreeningForm.vue";

const props = defineProps({
    screeningInfo: {
        type: Array,
        default: () => [],
    },
});

</script>


<template>

    <BaseLayout title="ScreeningCreate">
        <div class="w-full mx-auto pt-2">
            <div class="bg-white rounded-xl shadow shadow-lg p-4 dark:bg-gray-900">
                <ScreeningForm :screeningInfo="props.screeningInfo" />
            </div>
        </div>
    </BaseLayout>
    
</template>
