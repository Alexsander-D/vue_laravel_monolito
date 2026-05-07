<script setup>
import ApiTokenManager from '@/Pages/API/Partials/ApiTokenManager.vue';
import BaseLayout from '@/Layouts/BaseLayout.vue';
import { defineProps } from "vue";

const props = defineProps({
    tokens: Array,
    availablePermissions: Array,
    defaultPermissions: Array,
});
</script>

<template>
    <BaseLayout title="UserSettings">
        <div class="w-full mx-auto pt-2">
            <!-- Card -->
            <div class="bg-white rounded-xl shadow shadow-lg p-4 dark:bg-gray-900">
                <ApiTokenManager :tokens="tokens" :available-permissions="availablePermissions"
                    :default-permissions="defaultPermissions" />
            </div>
        </div>
    </BaseLayout>
</template>
