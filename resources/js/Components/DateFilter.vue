<script setup>
import { defineProps, defineEmits } from "vue";
import TextInput from "@/Components/TextInput.vue";

const props = defineProps({
  startDate: String,
  endDate: String,
});

const emit = defineEmits(["update:startDate", "update:endDate", "submit"]);

const submitForm = () => {
  emit("submit");
};
</script>

<template>
  <div class="mt-8 w-full">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end">
      <div class="flex-1">
        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Data inicial
        </label>
        <TextInput
          type="date"
          :value="startDate"
          class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-yellow-500 focus:outline-none focus:ring-2 focus:ring-yellow-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
          autocomplete="off"
          @blur="submitForm"
          @keyup.enter="submitForm"
          @input="$emit('update:startDate', $event.target.value)"
        />
      </div>

      <div class="flex items-center gap-2 text-sm font-medium text-gray-600 dark:text-gray-300">
        <span class="hidden lg:inline">até</span>
      </div>

      <div class="flex-1">
        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Data final
        </label>
        <TextInput
          type="date"
          :value="endDate"
          class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-yellow-500 focus:outline-none focus:ring-2 focus:ring-yellow-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
          autocomplete="off"
          @blur="submitForm"
          @keyup.enter="submitForm"
          @input="$emit('update:endDate', $event.target.value)"
        />
      </div>
    </div>
  </div>
</template>
