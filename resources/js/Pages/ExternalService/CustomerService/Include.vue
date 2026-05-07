<script setup>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import { defineProps } from "vue";
import IncludeForm from "./Partials/IncludeForm.vue";

const props = defineProps({
    dataInclude: {
        type: Object,
        default: () => [],
    },
});

</script>

<template>

    <BaseLayout title="Include">
        <div class="w-full mx-auto pt-2">
            <div class="bg-white rounded-xl shadow shadow-lg p-4 dark:bg-gray-900">
                <IncludeForm :dataInclude="props.dataInclude" />
            </div>
        </div>
    </BaseLayout>

</template>
