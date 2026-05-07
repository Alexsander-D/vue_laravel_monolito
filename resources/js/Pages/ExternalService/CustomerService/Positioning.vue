<script setup>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import PositioningTable from "./Partials/PositioningTable.vue";

const props = defineProps({
    date: Object
});

</script>

<template>

    <BaseLayout title="Positioning">
        <div class="w-full mx-auto pt-2">
            <!-- Card -->
            <div class="bg-white rounded-xl shadow shadow-lg p-4 dark:bg-gray-900">
                <PositioningTable :date="props.date" />
            </div>
        </div>
    </BaseLayout>

</template>
