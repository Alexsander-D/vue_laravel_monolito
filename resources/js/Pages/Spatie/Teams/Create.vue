<script setup>
import BaseLayout from '@/Layouts/BaseLayout.vue';
import CreateTeamForm from '@/Pages/Spatie/Teams/Partials/CreateTeamForm.vue';
</script>

<template>
    <BaseLayout title="UserSettings">
        <div class="w-full mx-auto pt-2">
            <!-- Card -->
            <div class="bg-white rounded-xl shadow shadow-lg p-4 dark:bg-gray-900">
                <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                    <CreateTeamForm />
                </div>
            </div>
        </div>
    </BaseLayout>
</template>
