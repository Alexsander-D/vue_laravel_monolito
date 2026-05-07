<script setup>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import { defineProps } from "vue";
import MaterialForm from "./Partials/MaterialForm.vue";
import MaterialInfo from "./Partials/MaterialInfo.vue";

const props = defineProps({
    materialsData: {
        type: Object,
        required: true,
        default: () => [],
    },
});

</script>

<template>

    <BaseLayout title="SendMaterials">
        <div class="w-full mx-auto pt-2">
            <div class="bg-white rounded-xl border border-gray-700 shadow p-4 dark:bg-gray-900">
                <MaterialForm :materialsData="props.materialsData" />
                <MaterialInfo :materialsData="props.materialsData" />
            </div>
        </div>
    </BaseLayout>
    
</template>
