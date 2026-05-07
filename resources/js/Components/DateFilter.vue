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
  <div class="grid grid-cols-12 gap-2 sm:gap-6 mt-8 items-center">
    <div class="col-span-1 text-center">
      <label class="font-medium inline-block text-sm mt-2.5 dark:text-neutral-200">
        Data:
      </label>
    </div>
    <div class="col-span-5">
      <div class="flex items-center gap-2">
        <TextInput
          type="date"
          :value="startDate"
          class="mt-1 block w-full"
          autocomplete="off"
          @blur="submitForm"
          @keyup.enter="submitForm"
          @input="$emit('update:startDate', $event.target.value)"
        />
      </div>
    </div>
    <div class="col-span-1 text-center">
      <label class="font-medium inline-block text-sm mt-2.5 dark:text-neutral-200">
        Até:
      </label>
    </div>
    <div class="col-span-5">
      <div class="flex items-center gap-2">
        <TextInput
          type="date"
          :value="endDate"
          class="mt-1 block w-full"
          autocomplete="off"
          @blur="submitForm"
          @keyup.enter="submitForm"
          @input="$emit('update:endDate', $event.target.value)"
        />
      </div>
    </div>
  </div>
</template>
