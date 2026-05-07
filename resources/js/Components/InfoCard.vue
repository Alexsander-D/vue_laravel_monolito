<template>
    <div
        class="flex flex-col bg-white border shadow-md rounded-xl dark:bg-neutral-800 border-neutral-400 cursor-pointer hover:shadow-lg transition h-full">
        <div class="p-4 md:p-5 flex flex-col justify-center h-full">
            <div class="flex justify-center">
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    {{ title }}
                </p>
            </div>
            <div class="flex justify-center mt-1">
                <h3 class="text-xl sm:text-2xl font-medium text-gray-800 dark:text-neutral-200">
                    {{ data }}
                </h3>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    title: {
        type: String,
        default: '—'
    },
    data: {
        type: [String, Number],
        default: '—'
    }
})
</script>