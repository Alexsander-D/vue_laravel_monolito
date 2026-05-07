<!-- MultiselectComponent.vue -->
<template>
  <multiselect
    v-model="localValue"
    :options="options"
    @update:modelValue="updateModelValue"
    placeholder="Selecione uma opção"
  />
</template>

<script setup>
import Multiselect from "vue-multiselect";
import { defineProps, defineEmits, ref, watch } from "vue";

const props = defineProps({
  modelValue: {
    type: [Array, String, Object],
    default: () => [],
  },
  options: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(["update:modelValue", "selected"]);
const localValue = ref(props.modelValue);

watch(
  () => props.modelValue,
  (newValue) => {
    if (!newValue || (Array.isArray(newValue) && newValue.length === 0)) {
      localValue.value = null;
    } else {
      localValue.value = newValue;
    }
  },
  { deep: true }
);

function updateModelValue(value) {
  emit("update:modelValue", value);
  emit("selected", value);
}
</script>

<style>
@import "/resources/css/Components/Select.css";
</style>
